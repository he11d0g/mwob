<?php
/**
 * Author: helldog
 * Email: im@helldog.net
 * Url: http://helldog.net
 */

class MWOB {

    public $mask = 'abdefhiknrstyzABDEFGHKNQRSTYZ0123456789';
    public $parsePath;
    public $outPath;
    public $level;

    public $minLenghtNames = 3;
    public $maxLenghtNames = 8;

    //Vars
    private $vars = [];
    private $ignoreVars = ['$GLOBALS','$_SERVER','$_GET','$_POST','$_FILES','$_COOKIE','$_SESSION','$_REQUEST','$_ENV','$argv','$argc'];
    private $varsPattern = '/(\$[a-zA-Z0-9_]{1,})/';
    private $varsPatternKey = 1;

    //Func
    private $functions = [];
    private $ignoreFunctions = ['__construct','__destruct','__call','__callStatic','__get','__set','__isset','__unset','__sleep','__wakeup','__toString','__invoke','__set_state','__clone','__debugInfo'];
    private $funcPattern = '/(function ([a-zA-Z0-9_]{2,}))\(/';

    //Class
    private $classes = [];
    private $classPattern = '/class (\S*)({|.*{)/';
    private $files;

    public function __construct($parsePath,$outPath,$level = 1)
    {
        $this->parsePath = preg_replace('/\/$/','',$parsePath);
        $this->outPath = preg_replace('/\/$/','',$outPath);
        $this->level = $level;
    }

    public function run()
    {
        if(is_file($this->parsePath)){
            $this->_parseFile($this->parsePath, $this->outPath);
            var_dump($this->classes);
        } else if (is_dir($this->parsePath)) {
            $listTree = $this->_readDirs($this->parsePath);
            foreach($listTree as $obj){
                $newDir = str_replace($this->parsePath, $this->outPath, $obj['dir']);
                if(!is_dir($newDir)){
                    $this->_createDir($newDir);
                }
                if(!empty($obj['file'])){
                    $this->_parseFile($obj['dir'] .'/'.$obj['file'],$newDir . '/'. $obj['file']);
                }
            }
        }
    }


    //Сеттеры
    public function addIngonreVars($vars = array())
    {
        $this->ignoreVars = array_merge($this->ignoreVars,$vars);
    }

    public function addIngonreFunc($funcList = array())
    {
        $this->ignoreFunctions = array_merge($this->ignoreFunctions,$funcList);
    }


    private function _readDirs($path, $data = array())
    {
        if ($handle = opendir($path)) {
            while ($entry = readdir($handle)) {
                if ($entry != "." && $entry != "..") {
                    if(is_file($path.'/'.$entry)){
                        $data[] = array('dir' => $path,'file' => $entry);
                    } else if(is_dir($path.'/'.$entry)){
                        $data[] = array('dir' => $path.'/'.$entry);
                        $data = $this->_readDirs($path.'/'.$entry,$data);
                    }
                }
            }
            closedir($handle);

            return $data;
        }

    }


    private function _createDir($path)
    {
        mkdir($path, 0777, true);
    }

    private function _parseFile($in,$out)
    {
        if(is_file($in)){
            $file = file_get_contents($in);

            //Сбор данных
            $this->_collectData($file);

            //Перезапись
            $file = $this->_obfuscate($file);

            //Сохранение
            file_put_contents($out,$file);
        }
    }


    private function _collectData($file)
    {
        switch($this->level){
            case 1:
                $this->_collectVars($file);
                $this->_collectFunc($file);
                break;
            case 2:
                $this->_collectVars($file);
                $this->_collectFunc($file);
                $this->_collectClass($file);
                break;
        }
    }

    private function _obfuscate($file)
    {
        switch($this->level){
            case 1:
                $file = $this->_compressCode($file);
                $file = $this->_obfuscateVars($file);
                $file = $this->_obfuscateFunc($file);
                break;
            case 2:
                $file = $this->_compressCode($file);
                $file = $this->_obfuscateVars($file);
                $file = $this->_obfuscateFunc($file);
                $file = $this->_obfuscateClass($file);
                break;
        }

        return $file;
    }

    //private

    private function _genHash()
    {
        $numChars = strlen($this->mask);
        $length = rand($this->minLenghtNames, $this->maxLenghtNames);
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= substr($this->mask, rand(1, $numChars) - 1, 1);
        }

        return $out;
    }

    //обычные переменные
    private function _collectVars($str)
    {
        preg_match_all($this->varsPattern,$str,$vars);
        if(isset($vars[$this->varsPatternKey])){
            foreach ($vars[$this->varsPatternKey] as $var) {
                if(in_array($var,$this->ignoreVars)) continue;
                if(!isset($this->vars[$var])){
                    $this->vars[$var] = '$_'.$this->_genHash();
                }
            }
        }
    }

    private function _collectFunc($file)
    {
        preg_match_all($this->funcPattern,$file,$funcList);
        if(isset($funcList[1])){
            foreach ($funcList[1] as $func) {
                $trimF = trim(str_replace('function','',$func));
                if(in_array($trimF,$this->ignoreFunctions)) continue;
                if(!isset($this->functions[$trimF])){
                    $this->functions[$trimF] = '_'.$this->_genHash();
                }
            }
        }
    }


    private function _collectClass($file)
    {
        preg_match_all($this->classPattern,$file,$funcList);
        if(isset($funcList[1])){
            foreach ($funcList[1] as $func) {
                if(!isset($this->classes[$func])){
                    $this->classes[$func] = '_'.$this->_genHash();
                }
            }
        }
    }


    private function _obfuscateVars($file)
    {
        foreach($this->vars as $k => $v){
            $k = '\\'.$k;
            $objK = str_replace('\\$','',$k);
            $file = preg_replace('/('.$k.')([^a-zA-Z_])/','\\'.$v.'$2',$file);//Обычные
            $file = preg_replace('/-\>('.$objK.')([^a-zA-Z_])/','->\\'.$v.'$2',$file);//Обьекта
        }

        return $file;
    }

    private function _obfuscateFunc($file)
    {
        foreach($this->functions as $k => $v){
            $file = preg_replace('/(\W)'.$k.'\(/','$1'.$v.'(',$file);//Обычные
        }

        return $file;
    }


    private function _obfuscateClass($file)
    {
        foreach($this->classes as $k => $v){
            $file = preg_replace('/class '.$k.'({|.*{)/','class '.$v.'$1',$file);//Обьявление
            $file = preg_replace('/new '.$k.'([ ;(])/','new '.$v.'$1',$file);//Создание
            $file = preg_replace('/(\s|;|^)'.$k.'(-\>[a-zA-Z0-9_$])/','$1'.$v.'$2',$file);//Вызов из обьекта
            $file = preg_replace('/(\s|;|^)'.$k.'(::[a-zA-Z0-9_$])/','$1'.$v.'$2',$file);//Вызов статика
        }

        return $file;
    }

    private function _compressCode($file) {
        static $IW = array(
            T_CONCAT_EQUAL,             // .=
            T_DOUBLE_ARROW,             // =>
            T_BOOLEAN_AND,              // &&
            T_BOOLEAN_OR,               // ||
            T_IS_EQUAL,                 // ==
            T_IS_NOT_EQUAL,             // != or <>
            T_IS_SMALLER_OR_EQUAL,      // <=
            T_IS_GREATER_OR_EQUAL,      // >=
            T_INC,                      // ++
            T_DEC,                      // --
            T_PLUS_EQUAL,               // +=
            T_MINUS_EQUAL,              // -=
            T_MUL_EQUAL,                // *=
            T_DIV_EQUAL,                // /=
            T_IS_IDENTICAL,             // ===
            T_IS_NOT_IDENTICAL,         // !==
            T_DOUBLE_COLON,             // ::
            T_PAAMAYIM_NEKUDOTAYIM,     // ::
            T_OBJECT_OPERATOR,          // ->
            T_DOLLAR_OPEN_CURLY_BRACES, // ${
            T_AND_EQUAL,                // &=
            T_MOD_EQUAL,                // %=
            T_XOR_EQUAL,                // ^=
            T_OR_EQUAL,                 // |=
            T_SL,                       // <<
            T_SR,                       // >>
            T_SL_EQUAL,                 // <<=
            T_SR_EQUAL,                 // >>=
        );
        if(is_file($file)) {
            if(!$file = file_get_contents($file)) {
                return false;
            }
        }
        $tokens = token_get_all($file);

        $new = "";
        $c = sizeof($tokens);
        $iw = false; // ignore whitespace
        $ih = false; // in HEREDOC
        $ls = "";    // last sign
        $ot = null;  // open tag
        for($i = 0; $i < $c; $i++) {
            $token = $tokens[$i];
            if(is_array($token)) {
                list($tn, $ts) = $token; // tokens: number, string, line
                $tname = token_name($tn);
                if($tn == T_INLINE_HTML) {
                    $new .= $ts;
                    $iw = false;
                } else {
                    if($tn == T_OPEN_TAG) {
                        if(strpos($ts, " ") || strpos($ts, "\n") || strpos($ts, "\t") || strpos($ts, "\r")) {
                            $ts = rtrim($ts);
                        }
                        $ts .= " ";
                        $new .= $ts;
                        $ot = T_OPEN_TAG;
                        $iw = true;
                    } elseif($tn == T_OPEN_TAG_WITH_ECHO) {
                        $new .= $ts;
                        $ot = T_OPEN_TAG_WITH_ECHO;
                        $iw = true;
                    } elseif($tn == T_CLOSE_TAG) {
                        if($ot == T_OPEN_TAG_WITH_ECHO) {
                            $new = rtrim($new, "; ");
                        } else {
                            $ts = " ".$ts;
                        }
                        $new .= $ts;
                        $ot = null;
                        $iw = false;
                    } elseif(in_array($tn, $IW)) {
                        $new .= $ts;
                        $iw = true;
                    } elseif($tn == T_CONSTANT_ENCAPSED_STRING
                        || $tn == T_ENCAPSED_AND_WHITESPACE)
                    {
                        if($ts[0] == '"') {
                            $ts = addcslashes($ts, "\n\t\r");
                        }
                        $new .= $ts;
                        $iw = true;
                    } elseif($tn == T_WHITESPACE) {
                        $nt = @$tokens[$i+1];
                        if(!$iw && (!is_string($nt) || $nt == '$') && !in_array($nt[0], $IW)) {
                            $new .= " ";
                        }
                        $iw = false;
                    } elseif($tn == T_START_HEREDOC) {
                        $new .= "<<<S\n";
                        $iw = false;
                        $ih = true; // in HEREDOC
                    } elseif($tn == T_END_HEREDOC) {
                        $new .= "S;";
                        $iw = true;
                        $ih = false; // in HEREDOC
                        for($j = $i+1; $j < $c; $j++) {
                            if(is_string($tokens[$j]) && $tokens[$j] == ";") {
                                $i = $j;
                                break;
                            } else if($tokens[$j][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                    } elseif($tn == T_COMMENT || $tn == T_DOC_COMMENT) {
                        $iw = true;
                    } else {
                        $new .= $ts;
                        $iw = false;
                    }
                }
                $ls = "";
            } else {
                if(($token != ";" && $token != ":") || $ls != $token) {
                    $new .= $token;
                    $ls = $token;
                }
                $iw = true;
            }
        }
        return $new;
    }



}
