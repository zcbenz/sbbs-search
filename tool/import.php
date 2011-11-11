#!/usr/bin/env php
<?php
require_once 'init.php';

Lib::load(array('search/helper.php'));

$start_time = time();

$session = new Session();
$session->initLogin();

$all = 0;

$boards = bbs_super_getboards();

// Skip to specified board
//while ($boards[0]['NAME'] != 'Test')
//    array_shift($boards);
//array_shift($boards);

foreach ($boards as $key => &$board) {
    $board_name   = $board['NAME'];
    $board_id     = $board['BID'];

    $xs = new XS(XS_CONF);
    $index = $xs->index;

    $count = 0;
    $total = bbs_countarticles($board_id, 0, 0);

    printf ('[%s] :           ', $board_name);

    $i = 1;
    $PAGE = 100;
    while ($total > 0) {
        $quit = false;

        if ($i + $PAGE > $total + 1) {
            $quit = true;
            $PAGE = $total - $i + 1;
        }

        $articles = bbs_getarticles($board_name, $i, $PAGE, 0, 0);

        foreach ($articles as $key => &$val) {
            try {
                xs_import_article($index, $board, $val);
                ++$count;
            } catch(Exception $e) {
                echo 'Error: ', $e->getMessage();
            }
        }

        $i += $PAGE;

        progress($count / $total, $i);

        if ($quit) break;
    }

    $all += $count;
    printf ("\nTotal: %d, Valid: %d\n", $total, $count);
}

echo 'Done: ', $all, "\n";
echo 'Time: ', time() - $start_time, "\n";
?>
