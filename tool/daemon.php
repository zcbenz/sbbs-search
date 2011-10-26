#!/usr/bin/env php
<?php
require_once 'search/lib/XS.php';
require_once 'init.php';

$session = new Session();
$session->initLogin();

if (bbs_daemon('bbsindexd', true, true)) {
    echo 'Cannot start bbsindexd';
    bbs_log('3error', 'bbsindexd already started');

    exit(0);
}

while ($msg = bbs_logrcv()) {
    import_post($msg['board'], $msg['postid']);
}

function import_post($board_name, $id) {
    $val = get_article($board_name, $id);
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
            'access' => bbs_super_access_board(bbs_getboard($board_name)),
            'title' => $val['TITLE'],
            'content' => filter($content),
            'time' => $val['POSTTIME'],
            'flag' => $val['FLAGS'],
            'author' => $val['OWNER'],
            'board' => $board_name
        );

        $xs = new XS('sbbs');
        $index = $xs->index;

        $doc = new XSDocument;
        $doc->setFields($data);

        $index->add($doc);
    }
}

function get_article($board_name, $id) {
    $articles = array();
    bbs_get_records_from_id($board_name, $id, 0, &$articles);

    return $articles[0];
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

