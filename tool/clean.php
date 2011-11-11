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

// Push empty data to the db
$index->setDb('db');
$data = array(
    'id'         => 0,
    'first'      => 0,
    'attachment' => 0,
    'replies'    => 0,
    'mark'       => 0,
    'good'       => 0,
    'title'      => 0,
    'content'    => 0,
    'time'       => 0,
    'author'     => 0,
    'path'       => 0,
    'board'      => 0
);
$doc = new XSDocument;
$doc->setFields($data);
$index->add($doc);
?>
