<?php
require_once 'init.php';

Lib::load(array('search/helper.php'));
Lib::load(array('utils/pagination.php'));

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
$__ = array('q', 'm', 'f', 'a', 'g', 't', 'since', 'until', 's', 'p', 'ie', 'oe');
foreach ($__ as $_)
    $$_ = isset($_GET[$_]) ? $_GET[$_] : '';

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

        // filter private posts
        $search->addRange('access', 1, 1);

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
            $search->addRange('good', 1, 1);
            $attr['g'] = 1;
        }

        // time ranges
        if ($t == 5) {
            if (validate_time($since) && validate_time($until)) {
                $search->addRange('time', str_replace('-', '', $since), str_replace('-', '', $until));

                $attr['t'] = $t;
                $attr['since'] = $since;
                $attr['until'] = $until;
            } else {
                $since = $until = '';
                $t = 0;
            }
        } else {
            $since = $until = '';

            if (0 < $t && $t < 5) {
                define('DAY', 24 * 3600);
                $diff = array(2 * DAY, 7 * DAY, 30 * DAY, 365 * DAY);
                $now = time();
                $search->addRange('time', date('Ymd', $now - $diff[$t - 1]), date('Ymd'));
                $attr['t'] = $t;
            }
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
catch (XSException $e)
{
    $error = strval($e);
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
