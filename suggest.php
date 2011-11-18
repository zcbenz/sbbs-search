<?php
/**
 * suggest.php 
 * SBBS 提取搜索建议(输出JSON)
 * 
 * 该文件由 xunsearch PHP-SDK 工具自动生成，请根据实际需求进行修改
 * 创建时间：2011-10-24 20:11:27
 */
// 加载 XS 入口文件
require_once dirname(__FILE__) . '/lib/helper.php';

// Prefix Query is: term (by jQuery-ui)
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$q = get_magic_quotes_gpc() ? stripslashes($q) : $q;
$terms = array();
if (!empty($q) && strpos($q, ':') === false)
{
	try
	{
		$xs = new XS(XS_CONF);
		$terms = $xs->search->setCharset('UTF-8')->getExpandedQuery($q);
	}
	catch (XSException $e)
	{
		
	}
}

// output json
$size = count($terms);
for ($i = 0; $i < $size; $i++) {
    echo $terms[$i], "\n";
}
exit(0);
