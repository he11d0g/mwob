<?php
/**
 * Author: helldog
 * Email: im@helldog.net
 * Url: http://helldog.net
 */

include_once 'mwob.php';

$m = new MWOB(__DIR__.'/in',__DIR__.'/out',2);
$m->run();