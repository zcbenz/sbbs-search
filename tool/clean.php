#!/usr/bin/env php
<?php
require_once 'search/lib/XS.php';

$xs = new XS('sbbs');
$index = $xs->index;

$index->clean();
?>
