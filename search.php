<?php
require_once 'search/lib/XS.php';
require_once 'init.php';

Lib::load(array('utils/pagination.php'));

// 支持的 GET 参数列表
// q: 查询语句
// m: 开启模糊搜索，其值为 yes/no
// f: 只搜索某个字段，其值为字段名称，要求该字段的索引方式为 self/both
// s: 排序字段名称及方式，其值形式为：xxx_ASC 或 xxx_DESC
// p: 显示第几页，每页数量为 XSSearch::PAGE_SIZE 即 10 条
// ie: 查询语句编码，默认为 GBK
// oe: 输出编码，默认为 GBK
// xml: 是否将搜索结果以 XML 格式输出，其值为 yes/no
//
// variables
$eu = '';
$__ = array('q', 'm', 'f', 's', 'p', 'ie', 'oe', 'xml');
foreach ($__ as $_)
    $$_ = isset($_GET[$_]) ? $_GET[$_] : '';

// input encoding
if (!empty($ie) && !empty($q) && strcasecmp($ie, 'GBK'))
{
    $q = XS::convert($q, $cs, $ie);
    $eu .= '&ie=' . $ie;
}

// output encoding
if (!empty($oe) && strcasecmp($oe, 'GBK'))
{

    function xs_output_encoding($buf)
    {
        return XS::convert($buf, $GLOBALS['oe'], 'GBK');
    }
    ob_start('xs_output_encoding');
    $eu .= '&oe=' . $oe;
}
else
{
    $oe = 'GBK';
}

// recheck request parameters
$q = get_magic_quotes_gpc() ? stripslashes($q) : $q;

// base url
$bu = '/s?q=' . urlencode($_GET['q']) . $eu;

// other variable maybe used in tpl
$count = $total = $search_cost = 0;
$docs = $related = $corrected = $hot = array();
$error = $pager = '';
$total_begin = microtime(true);

// perform the search
try
{
    $xs = new XS('sbbs');
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

if ($count > $n)
    $pager = paginate_three($bu, $p, (int)($count / $n) + 1, 4);

// output the data
include dirname(__FILE__) . '/search.html';
?>
