#!/usr/bin/env php
<?php
require_once 'search/lib/XS.php';
require_once 'init.php';

$start_time = time();

function progress($j, $i) {
    echo "\033[11D";
    echo str_pad((int)($j * 100), 3, ' ', STR_PAD_LEFT) . "% ";
    echo str_pad($i, 6, ' ', STR_PAD_LEFT);
}

$session = new Session();
$session->initLogin();

$all = 0;

$xs = new XS('sbbs');
$index = $xs->index;

$boards = bbs_getboards('*', 0, 14);

// Skip to specified board
while ($boards[0]['NAME'] != 'PopMusic')
    array_shift($boards);
array_shift($boards);

foreach ($boards as $key => &$val) {
    $board_name = $val['NAME'];
    $board_id = $val['BID'];

    $count = 0;
    $total = bbs_countarticles($board_id, 0);

    printf ('[%s] :           ', $board_name);

    $i = 1;
    $PAGE = 10;
    while ($total > 0) {
        $quit = false;

        if ($i + $PAGE > $total + 1) {
            $quit = true;
            $PAGE = $total - $i + 1;
        } else {
            $i += $PAGE;
        }

        $articles = bbs_getarticles($board_name, $i, $PAGE, 0);

        foreach ($articles as $key => &$val) {
            $content = bbs_originfile($board_name, $val['FILENAME']);
            if (is_string($content)) {
                // Guard from memory overflow
                if (strlen($content) > 200000)
                    continue;

                ++$count;

                $data = array(
                    'id' => $val['ID'],
                    'title' => $val['TITLE'],
                    'content' => $content,
                    'author' => $val['OWNER'],
                    'board' => $board_name,
                    'time' => date('Ymd', $val['POSTTIME'])
                );

                $doc = new XSDocument;
                $doc->setFields($data);

                $index->add($doc);
            }
        }

        progress($count / $total, $i);

        if ($quit)
            break;
    }

    $all += $count;
    printf ("\nTotal: %d, Valid: %d\n", $total, $count);
}

echo 'Done: ', $all, "\n";
echo 'Time: ', time() - $start_time, "\n";
?>
