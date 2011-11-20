<?php
require_once dirname(__FILE__) . '/../www2-funcs.php';
require_once dirname(__FILE__) . '/lib/helper.php';
require_once dirname(__FILE__) . '/lib/pagination.php';

login_init();

// 支持的 GET 参数列表
// q: 查询语句
// f: 只显示主题贴
// a: 只显示带附件的帖子
// g: 只显示精华贴
// m: 只显示被标记的帖子
// t: 过去xx内
// s: 排序方式
// p: 显示第几页，每页数量为 XSSearch::PAGE_SIZE 即 10 条
// ie: 查询语句编码，默认为 GBK
// oe: 输出编码，默认为 GBK
//
// variables
$attr = array();
$eu = '';
$__ = array('q', 'q2', 'm', 'f', 'a', 'g', 't', 'author', 'since', 'until', 's', 'p', 'ie', 'oe');
foreach ($__ as $_)
    $$_ = isset($_GET[$_]) ? $_GET[$_] : '';

// should we set time range
$limit = false;

// base url
$bu = '/s?';

// input encoding
if (!empty($ie) && !empty($q) && strcasecmp($ie, 'GBK'))
{
    $q = XS::convert($q, $cs, $ie);
    $attr['ie'] = $ie;
}

// output encoding
if (!empty($oe) && strcasecmp($oe, 'GBK'))
{

    function xs_output_encoding($buf)
    {
        return XS::convert($buf, $GLOBALS['oe'], 'GBK');
    }
    ob_start('xs_output_encoding');
    $attr['oe'] = $oe;
}
else
{
    $oe = 'GBK';
}

// recheck request parameters
$q = get_magic_quotes_gpc() ? stripslashes($q) : $q;

// attach extra parameters
if ($q2) {
    $q2 = get_magic_quotes_gpc() ? stripslashes($q2) : $q2;
    $q .= ' ' . $q2;
}
if ($author) {
    $author = get_magic_quotes_gpc() ? stripslashes($author) : $author;
    $q .= ' author:' . $author . ' ';
}
$attr['q'] = $q;

// other variable maybe used in tpl
$count = $total = $search_cost = 0;
$docs = $related = $corrected = $hot = array();
$error = $pager = '';
$total_begin = microtime(true);

// perform the search
try
{
    $xs = new XS(XS_CONF);
    $search = $xs->search;
    $search->setCharset('GBK');

    if ($t > 0 && $t < 3 && !$loginok)
        throw new Exception('未登陆时只能搜索两年内的帖子');

    // 设置搜索用库以及时间范围
    switch ($t) {
    case 1: // 任意时间段
        xsRangeDb($search, getDbNumByYear(TORG), getDbNumByYear(TNOW), $g);

        $attr['t'] = $t;
        break;
    case 3: // 预设时间段
    case 4:
    case 5:
    case 6:
        define('DAY', 24 * 3600);
        $diff = array(2 * DAY, 7 * DAY, 30 * DAY, 10 * 365 * DAY);

        $valid = true;
        $until = date('Y-m-d');
        $since = date('Y-m-d', time() - $diff[$t - 3]);
    case 2: // 自定义
        if ($limit == 6) $limit = false;
        else $limit = true;
        if (!$valid) $valid = validate_time($since) && validate_time($until);

        if ($valid) {
            $cur = getDbNumByYear(substr($until, 0, 4));
            $org = getDbNumByYear(substr($since, 0, 4));
            xsRangeDb($search, $org, $cur, $g);

            $attr['since'] = $since;
            $attr['until'] = $until;
            $attr['t'] = $t;
            break;
        }

        // 无效时间，作默认处理
        $t = 0;
    case 0: // 两年内 
    default:
        $limit = false;
        $until = date('Y-m-d');
        $since = date('Y-m-d', time() - 24 * 3600 * 365 * 2);

        xsSetDb($search, getDbNumByYear(TNOW), $g);

        // less than 1.8 years
        if (TNOW > TORG && ((TNOW - TORG) % 2 < 1.8))
            xsAddDb($search, getDbNumByYear(TNOW - 1), $g);
    }

    // load private board's db
    $matchBoard = false;
    if (preg_match('/.* *board:([[:alnum:]_]+).*/', $q, $matches) == 1) {
        $board = $matches[1];
        if (bbs2_access_board($board) > 0) {
            $matchBoard = true;

            if($t != 1) $limit = true;
            xsAddDb($search, '_private_' . $board, $g);
        }
    }

    // disable search by author for normal users
    if (preg_match('/.* *author:[[:alnum:]]+.*/', $q) > 0 &&
        !$matchBoard &&
        !bbs2_access_board('discuss'))
    {
        $q = preg_replace('/author:[[:alnum:]]+/', '', $q);
        $attr['q'] = $q;
    }

    if (empty($q))
    {
        // just show hot query
        $hot = $search->getHotQuery(10);
    }
    else
    {
        // fuzzy search
        $search->setFuzzy(false);

        // set query
        $search->setQuery($q);

        // custom filters
        if ($f) {
            $search->addRange('first', 1, 1);
            $attr['f'] = 1;
        }
        if ($a) {
            $search->addRange('attachment', 1, 1);
            $attr['a'] = 1;
        }
        if ($m) {
            $search->addRange('mark', 1, 1);
            $attr['m'] = 1;
        }
        if ($g) {
            $attr['g'] = 1;
        }
        if ($limit) {
            $search->addRange('time', str_replace('-', '', $since), str_replace('-', '', $until));
        }

        // sort?
        if ($s) {
            if ($s == 1)
                $search->setSort('time');
            else
                $search->setSort('time', true);

            $attr['s'] = $s;
        }

        // set offset, limit
        $p = max(1, intval($p));
        $n = 10;
        $search->setLimit($n, ($p - 1) * $n);

        // get the result
        $search_begin = microtime(true);
        $docs = $search->search();
        $search_cost = microtime(true) - $search_begin;

        // get other result
        $count = $search->getLastCount();
        $total = $search->getDbTotal();

        // try to corrected, if resul too few
        if ($count < 1 || $count < ceil(0.001 * $total))
            $corrected = $search->getCorrectedQuery();

        // get related query
        $related = $search->getRelatedQuery();
    }
}
catch (Exception $e)
{
    $error = $e->getMessage();
}

// calculate total time cost
$total_cost = microtime(true) - $total_begin;

if ($count > $n) {
    $reload = $bu . http_build_query($attr);
    $pager = paginate_three($reload, $p, (int)($count / $n) + 1, 4);
}

// helper for generate filter link
function switch_attr($bu, $attr, $f) {
    if ($attr[$f]) {
        unset ($attr[$f]);
        return 'class="active" href="'. $bu . http_build_query($attr) . '"';
    } else {
        $attr[$f] = 1;
        return 'href="'. $bu . http_build_query($attr) . '"';
    }
}

function value_attr($bu, $attr, $f, $v, $nohref = false) {
    $r = '';
    if ($v == $attr[$f]) $r = 'class="active" ';
    if ($nohref) return $r;

    if ($v == 0) {
        unset ($attr[$f]);
        return $r . 'href="'. $bu . http_build_query($attr) . '"';
    } else {
        $attr[$f] = $v;
        return $r . 'href="'. $bu . http_build_query($attr) . '"';
    }
}

function format_time($raw) {
    return substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2);
}

function validate_time($input) {
    return $input && !!strptime($input, '%Y-%m-%d');
}

// output the data
include dirname(__FILE__) . '/search.html';
?>
