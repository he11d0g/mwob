<?php
/**
 * Author: helldog
 * Email: im@helldog.net
 * Url: http://helldog.net
 */

include_once 'mwob.php';

$m = new MWOB(__DIR__.'/tst.php',__DIR__.'/out.php',2);
$m->run();