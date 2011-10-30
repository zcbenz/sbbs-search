<?php
$url = parse_url($_SERVER['HTTP_REFERER']);
$queryParts = explode('&', $url['query']); 
$attr = array(); 

foreach ($queryParts as $param) {
    $item = explode('=', $param); 
    $attr[$item[0]] = urldecode($item[1]);
}
?>
<?php foreach($attr as $k => $v): ?>
<?php if (!in_array($k, array('q', 't', 'since', 'until'))): ?>
<input type="hidden" name="<?php echo $k; ?>" value="<?php echo $v; ?>">
<?php endif; ?>
<?php endforeach; ?>
<div class="r-range well">
    <input type="hidden" name="t" value="5">
    <div class="row">
        <div class="r-label span3">
            <label>自从</label>
            <input type="text" name="since" value="<?php if (isset($attr['since'])) echo $attr['since'] ?>">
        </div>
    </div>
    <div class="row">
        <div class="r-label span3">
            <label>直到</label>
            <input type="text" name="until" value="<?php if (isset($attr['since'])) echo $attr['until'] ?>">
        </div>
    </div>
    <div class="r-btn row">
        <div style="text-align:right" class="r-label span3">
            （格式：19901007）
            <input type="submit" class="btn" value="搜索">
        </div>
    </div>
</div>
