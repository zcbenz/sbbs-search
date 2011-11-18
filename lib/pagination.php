<?php
/*************************************************************************
php easy :: pagination scripts set - Version Three
==========================================================================
Author:      php easy code, www.phpeasycode.com
Web Site:    http://www.phpeasycode.com
Contact:     webmaster@phpeasycode.com
*************************************************************************/
function paginate_three($reload, $page, $tpages, $adjacents) {
	
	$prevlabel = "&larr; ÉÏÒ³";
	$nextlabel = "ÏÂÒ³ &raquo;";
	
	$out = "<ul>\n";
	
	// previous
	if($page==1) {
        $out.= "<li class=\"prev disabled\"><a href=\"#\">" .$prevlabel ."</a></li>\n";
	}
	elseif($page==2) {
        $out.= "<li class=\"prev\"><a href=\"" . $reload . "\">" .$prevlabel ."</a></li>\n";
	}
	else {
        $out.= "<li class=\"prev\"><a href=\"" . $reload . "&amp;p=" . ($page-1). "\">" .$prevlabel ."</a></li>\n";
	}
	
	// first
	if($page>($adjacents+1)) {
		$out.= "<li><a href=\"" . $reload . "\">1</a></li>\n";
	}
	
	// interval
	if($page>($adjacents+2)) {
		$out.= "<li class=\"disabled\"><a href=\"#\">¡­</a></li>\n";
	}
	
	// pages
	$pmin = ($page>$adjacents) ? ($page-$adjacents) : 1;
	$pmax = ($page<($tpages-$adjacents)) ? ($page+$adjacents) : $tpages;
	for($i=$pmin; $i<=$pmax; $i++) {
		if($i==$page) {
            $out.= "<li class=\"active\"><a href=\"" . $reload . "&amp;p=" . $i . "\">" . $i . "</a></li>\n";
		}
		elseif($i==1) {
            $out.= "<li><a href=\"" . $reload . "\">" . $i . "</a></li>\n";
		}
		else {
            $out.= "<li><a href=\"" . $reload . "&amp;p=" . $i . "\">" . $i . "</a></li>\n";
		}
	}
	
	// interval
	if($page<($tpages-$adjacents-1)) {
		$out.= "<li class=\"disabled\"><a href=\"#\">¡­</a></li>\n";
	}
	
	// last
	if($page<($tpages-$adjacents)) {
		$out.= "<li><a href=\"" . $reload . "&amp;p=" . $tpages . "\">" .$tpages . "</a></li>\n";
	}
	
	// next
	if($page<$tpages) {
		$out.= "<li class=\"next\"><a href=\"" . $reload . "&amp;p=" . ($page+1) . "\">" .$nextlabel . "</a></li>\n";
	}
	else {
        $out.= "<li class=\"next disabled\"><a href=\"#\">" .$nextlabel ."</a></li>\n";
	}
	
	$out.= "</ul>";
	
	return $out;
}
?>
