<?php
class Controller {
    //Базовый контроллер
    public $ttt;

    public function gtPent()
    {

    }

    public static $layout = 'main'; //Базовый шаблон  (названия файла размещёного в папке layout)




//===============================================================     
    public static function render($view,$args = array()) // этот метод для Въюшек .. что бы передовать в них данные 
    {
        self::_renderLayout(static::$layout,$view,$args);  // вызываем метод класса который у нас описан ниже ... 
    }
//=============================================================== 



//===============================================================    
    public static function renderPart($view, $args = array())  // 
    {
        $path = BASE_PATH .'/view/'.$view.'.php';  // 
        if (is_file($path)) {
            foreach($args as $k => $v){
                $$k = $v;
            }
            include $path;
        }
    }
//=============================================================== 



//===============================================================     
    private static function _renderLayout($layout,$view,$args) // подключаем файлы шаблонов 
    {
        $path = BASE_PATH .'/view/layout/'.$layout.'.php'; // составляем адрес к файлу который указали в $layout
        $args_p = 'args';  // наши будующие переменные но пока это просто строки :)...  
        $view_p = 'view';
        if (is_file($path)) { // проверяем существует ли файл - и то что он являеться обычным файлом 
            $$args_p = $args; // в переменную args заносим переданные данные из $args
            $$view_p = $view; // в переменную view заносим переданные данные из $view
            include_once $path; // подключаем файл, в нашем случае main.php 
        }

    }
//===============================================================     



}

Controller::$layout = 1;
Controller::_1zf7S('sas',array());
$ddd = new Controller();
$ttt = new Controller;
$ttt->$ttt = 2;
$ttt->gtPent($ddd);