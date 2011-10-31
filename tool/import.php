#!/usr/bin/env php
<?php
require_once 'search/lib/XS.php';
require_once 'init.php';

$start_time = time();

$session = new Session();
$session->initLogin();

$all = 0;

$xs = new XS('sbbs');
$index = $xs->index;

$boards = bbs_super_getboards();

// Skip to specified board
//while ($boards[0]['NAME'] != 'PopMusic')
//    array_shift($boards);
//array_shift($boards);

foreach ($boards as $key => &$val) {
    $board_name = $val['NAME'];
    $board_id = $val['BID'];
    $board_access = bbs_super_access_board($board_id);

    $count = 0;
    $total = bbs_countarticles($board_id, 0);

    printf ('[%s] :           ', $board_name);

    $i = 1;
    $PAGE = 100;
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
                    'first' => $val['GROUPID'] == $val['ID'] ? 1 : 0,
                    'attachment' => $val['ATTACHPOS'] > 0 ? 1 : 0,
                    'access' => $board_access,
                    'mark' => $val['FLAGS'][0] != ' ' ? 1 : 0,
                    'good' => 0,
                    'title' => $val['TITLE'],
                    'content' => filter($content),
                    'time' => $val['POSTTIME'],
                    'flag' => $val['FLAGS'],
                    'author' => $val['OWNER'],
                    'board' => $board_name
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

function progress($j, $i) {
    echo "\033[11D";
    echo str_pad((int)($j * 100), 3, ' ', STR_PAD_LEFT) . "% ";
    echo str_pad($i, 6, ' ', STR_PAD_LEFT);
}

function startsWith($haystack, $needle, $case = true)
{
   if($case) return strpos($haystack, $needle, 0) === 0;

   return stripos($haystack, $needle, 0) === 0;
}

function filter($str) {
    $arr = explode("\n", $str);

    // Filter out signature
    $i = count($arr) - 1;
    for (; $i >= 0; $i--) {
        if ($arr[$i] == '--') {
            break;
        }
    }
    if ($i > 0)
        $arr = array_slice($arr, 0, $i);

    // Filter out quotes
    for ($i = 0; $i < count($arr); $i++) {
        if (preg_match('/^【 在 .* 的大作中提到: 】.*$/', $arr[$i])) {
            $arr[$i] = '';
        } else if (startsWith($arr[$i], ': ')) {
            $arr[$i] = '';
        }
    }

    return implode("\n", $arr);
}
?>
