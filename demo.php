<?php
/**
 * Created by PhpStorm.
 * User: Moon
 * Date: 2014/12/19 0019
 * Time: 16:49
 */
include "html2markdown";
$html = new Html2md;
$string = '';
$md = $html->toMarkdown($string);
echo $string;
?>
<!DOCTYPE html>
<html>
<head>
    <title>demo</title>
</head>
<body>
<label for="html">html</label>
<textarea id="html" name="html" rows="20" cols="100">
    <strong>B标签</strong>
    <em>em标签</em>
    <ol>
        <li>ol</li>
        <li>ol</li>
    </ol>
    <br>
    <blockquote>q标签</blockquote>
    <ul>
        <li>ul</li>
        <li>ul</li>
    </ul>
    <br>
    <img src="http://www.baidu.com" alt="百度"/><br>
    <code>code标签</code><br>
    <a href="http://www.baidu.com" title="百度"></a><br>
</textarea>
</body>
</html>