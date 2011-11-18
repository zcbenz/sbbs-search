<?php
require_once dirname(__FILE__) . '/XS.php';

define('TORG', 1999);
define('TNOW', date('Y'));
define('XS_CONF', 'sbbs');

function xs_import_article($index, $board, $val, $is_update = false) {
    $board_name = $board['NAME'];
    $board_id   = $board['BID'];
    $content    = bbs_originfile($board_name, $val['FILENAME'], 200000);

    if (!is_string($content))
		throw new XSException('Invalid article, '. $board_name. $val['ID']);

    $data = array(
        'id'         => $val['ID'],
        'first'      => $val['GROUPID'] == $val['ID'] ? 1 : 0,
        'attachment' => $val['ATTACHPOS'] > 0 ? 1 : 0,
        'mark'       => ($val['FLAGS'][0] != ' ' && $val['FLAGS'][0] != '*') ? 1 : 0,
        'replies'    => 0,
        'good'       => 0,
        'title'      => $val['TITLE'],
        'content'    => filter($content),
        'time'       => $val['POSTTIME'],
        'author'     => $val['OWNER'],
        'path'       => $val['FILENAME'],
        'board'      => $board_name
    );

    $doc = new XSDocument;
    $doc->setFields($data);

    if (bbs2_access_board('guest', $board_name) > 0)
        $index->setDb('db' . getDbNumByTime($val['POSTTIME']));
    else
        $index->setDb('db_private_' . strtolower($board_name));

    if ($is_update)
        $index->update($doc);
    else
        $index->add($doc);
}

function xsDelArticle(&$index, $board, $id) {
    try {
        for ($i = 0; $i <= getDbNumByYear(TNOW); $i++) {
            $index->setDb('db' . $i);
            $index->del($id);
        }
        $index->setDb('db_private_' . strtolower($board));
        $index->del($id);
    } catch (Exception $e) {
    }

    echo 'Deleted: ', $id, "\n";
}

function xsSetDb(&$index, $num, $jinghua = false) {
    try {
        if ($jinghua) {
            $index->setDb('jinghua' . $num);
        } else {
            $index->setDb('db' . $num);
            $index->addDb('jinghua' . $num);
        }
    } catch (XSException $e) {
    }
}

function xsAddDb(&$index, $num, $jinghua = false) {
    try {
        if ($jinghua) {
            $index->addDb('jinghua' . $num);
        } else {
            $index->addDb('db' . $num);
            $index->addDb('jinghua' . $num);
        }
    } catch (XSException $e) {
    }
}

function xsRangeDb(&$index, $from, $to, $jinghua = false) {
    xsSetDb($index, $to, $jinghua);
    for ($i = $from; $i < $to; $i++)
        xsAddDb($index, $i, $jinghua);
}

function getDbNumByTime($time) {
    $cur = date('Y', $time);
    if ($cur < TORG) $cur = TORG;
    if ($cur > TNOW) $cur = TNOW;

    return getDbNumByYear($cur);
}

function getDbNumByYear($year) {
    return intval(($year - TORG) / 2);
}

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

function endsWith($haystack, $needle, $case = true)
{
  $expectedPosition = strlen($haystack) - strlen($needle);

  if($case) return strrpos($haystack, $needle, 0) === $expectedPosition;

  return strripos($haystack, $needle, 0) === $expectedPosition;
}

/** 
  * The TokenIterator class allows you to iterate through string tokens using 
  * the familiar foreach control structure. 
  * 
  * Example: 
  * <code> 
  * <?php 
  * $string = 'This is a test.'; 
  * $delimiters = ' '; 
  * $ti = new TokenIterator($string, $delimiters); 
  * 
  * foreach ($ti as $count => $token) { 
  *     echo sprintf("%d, %s\n", $count, $token); 
  * } 
  * 
  * // Prints the following output: 
  * // 0. This 
  * // 1. is 
  * // 2. a 
  * // 3. test. 
  * </code> 
  */ 
class TokenIterator implements Iterator 
{ 
     /** 
      * The string to tokenize. 
      * @var string 
      */ 
     protected $_string; 
     
     /** 
      * The token delimiters. 
      * @var string 
      */ 
     protected $_delims; 
     
     /** 
      * Stores the current token. 
      * @var mixed 
      */ 
     protected $_token; 
     
     /** 
      * Internal token counter. 
      * @var int 
      */ 
     protected $_counter = 0; 
     
     /** 
      * Constructor. 
      * 
      * @param string $string The string to tokenize. 
      * @param string $delims The token delimiters. 
      */ 
     public function __construct($string, $delims) 
     { 
         $this->_string = $string; 
         $this->_delims = $delims; 
         $this->_token = strtok($string, $delims); 
     } 
     
     /** 
      * @see Iterator::current() 
      */ 
     public function current() 
     { 
         return $this->_token; 
     } 

     /** 
      * @see Iterator::key() 
      */ 
     public function key() 
     { 
         return $this->_counter; 
     } 

     /** 
      * @see Iterator::next() 
      */ 
     public function next() 
     { 
         $this->_token = strtok($this->_delims); 
         
         if ($this->valid()) { 
             ++$this->_counter; 
         } 
     } 

     /** 
      * @see Iterator::rewind() 
      */ 
     public function rewind() 
     { 
         $this->_counter = 0; 
         $this->_token   = strtok($this->_string, $this->_delims); 
     } 

     /** 
      * @see Iterator::valid() 
      */ 
     public function valid() 
     { 
         return $this->_token !== FALSE; 
     } 
 } 

function filter($str) {
    $ret = '';

    // Filter out signature
    $i = strrpos($str, "\n--\n");
    if ($i !== false) $str = substr($str, 0, $i);

    $skip = 0;
    foreach (new TokenIterator($str, "\n") as $tok) {
        if ($skip > 0) {
            --$skip;
            continue;
        }

        // Filter out quotes
        if (preg_match('/^【 在 .* 的大作中提到: 】.*$/', $tok)) {
            continue;
        } else if (startsWith($tok, ': ')) {
            continue;
        }

        // Filter out sender
        if (preg_match('/^发信人: .* (.*).*$/', $tok)) {
            $skip = 2;
            continue;
        }

        // Filter out repost
        if (preg_match('/^【 以下文字转载自 .* 讨论区 】$/', $tok)) {
            continue;
        }

        $ret .= "\n";
        $ret .= $tok;
    }

    // Force free memory
    strtok('', '');

    return $ret;
}
?>
