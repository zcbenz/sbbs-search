#!/usr/bin/env php
<?php
require_once 'init.php';

Lib::load(array('search/helper.php'));

$xs = new XS(XS_CONF);
$index = $xs->index;

$index->clean();
$index->setDb('db_private');
$index->clean();
for ($i = getDbNumByYear(TORG); $i <= getDbNumByYear(TNOW); $i++) {
    $index->setDb('db' . $i);
    $index->clean();
}
?>
