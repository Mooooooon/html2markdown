<?php

/*
 * html转换md
 * Moon
 */

class Html2md
{
    public function toMarkdown($string = null)
    {
        $ELEMENTS = array(
            array(
                "patterns" => 'p',
                "type" => null,
                "replacement" => function ($str, $attrs, $innerHTML) {
                        return $innerHTML ? "  \n  \n" . $innerHTML . "  \n" : '';
                    }
            ),
            array(
                "patterns" => 'br',
                "type" => 'void',
                "replacement" => "  \n"
            ),
            array(
                "patterns" => 'h([1-6])',
                "type" => null,
                "replacement" => function ($str, $hLevel, $attrs, $innerHTML) {
                        $hPrefix = '';
                        for ($i = 0; $i < $hLevel; $i++) {
                            $hPrefix .= '#';
                        }
                        return "\n\n" . $hPrefix . ' ' . $innerHTML . "\n";
                    }
            ),
            array(
                "patterns" => 'hr',
                "type" => 'void',
                "replacement" => "  \n  \n* * *  \n"
            ),
            array(
                "patterns" => 'a',
                "type" => null,
                "replacement" => function ($str, $attrs, $innerHTML) {
                        preg_match($this->attrRegExp('href'), $attrs, $href);
                        preg_match($this->attrRegExp('title'), $attrs, $title);
                        return $href ? '[' . $innerHTML . ']' . '(' . $href[1] . ($title && $title[1] ? ' "' . $title[1] . '"' : '') . ')' : $str;
                    }
            ),
            array(
                "patterns" => array('b', 'strong'),
                "type" => null,
                "replacement" => function ($str, $attrs, $innerHTML) {
                        return $innerHTML ? '**' . $innerHTML . '**' : '';
                    }
            ),
            array(
                "patterns" => array('i', 'em'),
                "type" => null,
                "replacement" => function ($str, $attrs, $innerHTML) {
                        return $innerHTML ? '_' . $innerHTML . '_' : '';
                    }
            ),
            array(
                "patterns" => 'code',
                "type" => null,
                "replacement" => function ($str, $attrs, $innerHTML) {
                        return $innerHTML ? '`' . $innerHTML . '`' : '';
                    }
            ),
            array(
                "patterns" => 'img',
                "type" => 'void',
                "replacement" => function ($str, $attrs, $innerHTML) {
                        preg_match($this->attrRegExp('src'), $attrs, $src);
                        preg_match($this->attrRegExp('alt'), $attrs, $alt);
                        preg_match($this->attrRegExp('title'), $attrs, $title);
                        return '![' . ($alt && $alt[1] ? $alt[1] : '') . ']' . '(' . $src[1] . ($title && $title[1] ? ' "' . $title[1] . '"' : '') . ')';
                    }
            )
        );
        foreach ($ELEMENTS as $k1 => $v) {
            if (is_array($v["patterns"]) == false) {
                $string = $this->replaceEls($string, array("tag" => $ELEMENTS[$k1]["patterns"], "replacement" => $ELEMENTS[$k1]["replacement"], "type" => $ELEMENTS[$k1]["type"]));
            } else {
                foreach ($v["patterns"] as $k2 => $v) {
                    $string = $this->replaceEls($string, array("tag" => $ELEMENTS[$k1]["patterns"][$k2], "replacement" => $ELEMENTS[$k1]["replacement"], "type" => $ELEMENTS[$k1]["type"]));
                }
            }
        }
        preg_match('/<pre\b[^>]*>([\s\S]*)<\/pre>/i', $string, $arg);
        if ($arg != null) {
            $innerHTML = $arg[1];
            $innerHTML = preg_replace('/^\t+/', '  ', $innerHTML);
            $innerHTML = preg_replace('/\n/', "  \n    ", $innerHTML);
            $innerHTML = "\n\n    " . $innerHTML . "  \n";
            $string = preg_replace('/<pre\b[^>]*>([\s\S]*)<\/pre>/i', $innerHTML, $string);
        }
        $string = preg_replace('/^(\s{0,3}\d+)\. /i', '$1\\. ', $string);
        preg_match_all('/<(ul|ol)\b[^>]*>(?:(?!<ul|<ol)[\s\S])*?<\/\1>/i', $string, $arg);
        foreach ($arg as $v) {
            $string = preg_replace('/<(ul|ol)\b[^>]*>(?:(?!<ul|<ol)[\s\S])*?<\/\1>/i', $this->replaceLists($v[0]), $string);
        }
        // Blockquotes
        $deepest = '/<blockquote\b[^>]*>((?:(?!<blockquote)[\s\S])*?)<\/blockquote>/i';
        preg_match_all($deepest, $string, $arg);
        foreach ($arg as $v) {
            if ($v[0] != null) {
                $string = preg_replace($deepest, $this->replaceBlockquotes($v[0]), $string);
            }
        }
        return $this->cleanUp($string);
    }

    function cleanUp($string)
    {
        $string = preg_replace('/^[\t\r\n]+|[\t\r\n]+$/', '', $string); // trim leading/trailing whitespace
        $string = preg_replace('/\n\s+\n/', "  \n  \n", $string);
        $string = preg_replace('/\n{3,}/', "  \n  \n", $string); // limit consecutive linebreaks to 2
        return $string;
    }

    function replaceBlockquotes($html)
    {
        preg_match('/<blockquote\b[^>]*>([\s\S]*?)<\/blockquote>/i', $html, $arg);
        if (isset($arg)) {
            $html = preg_replace('/<blockquote\b[^>]*>([\s\S]*?)<\/blockquote>/i', $this->replaceBlockquotes_son($arg[1]), $html);
        }
        return $html;
    }

    function replaceBlockquotes_son($inner)
    {
        $inner = preg_replace('/^\s+|\s+$/', '', $inner);
        $inner = $this->cleanUp($inner);
        $inner = preg_replace('/^/m', '> ', $inner);
        $inner = preg_replace('/^(>([\t]{2,}>)+)/m', '> >', $inner);
        return $inner;
    }

    function replaceLists($html)
    {
        preg_match('/<(ul|ol)\b[^>]*>([\s\S]*?)<\/\1>/i', $html, $arg);
        if (isset($arg[0])) {
            $html = preg_replace('/<(ul|ol)\b[^>]*>([\s\S]*?)<\/\1>/i', $this->replaceLists_son($arg[1], $arg[2]), $html);
            return "  \n  \n" . preg_replace('/[\t] + \n | \s + $/', '', $html);
        }
    }

    function replaceLists_son($listType, $innerHTML)
    {
        $lis = explode('</li>', $innerHTML);
        $lis_len = count($lis);
        array_splice($lis, $lis_len - 1, 1);
        for ($i = 0; $i < $lis_len - 1; $i++) {
            if ($lis[$i]) {
                $prefix = ($listType === 'ol') ? ($i + 1) . ".  " : "*   ";
                preg_match('/\s*<li[^>]*>([\s\S]*)/i', $lis[$i], $arg);
                $lis[$i] = preg_replace('/\s*<li[^>]*>([\s\S]*)/i', $this->replaceLists_son_son($arg[0], $arg[1], $prefix), $lis[$i]);
            }
        }
        return implode($lis, "  \n");
    }


    function replaceLists_son_son($str, $innerHTML, $prefix)
    {
        $innerHTML = preg_replace('/^\s+/', '', $innerHTML);
        $innerHTML = preg_replace('/\n\n/', "  \n  \n    ", $innerHTML);
        // indent nested lists
        $innerHTML = preg_replace('/\n([ ]*)+(\*|\d+\.) /', "  \n$1    $2 ", $innerHTML);

        return $prefix . $innerHTML;
    }

    public function replaceEls($html, $elProperties)
    {
        $pattern = $elProperties["type"] === 'void' ? '<' . $elProperties["tag"] . "\\b([^>]*)\\/?>" : '<' . $elProperties["tag"] . "\\b([^>]*)>([\\s\\S]*?)<\\/" . $elProperties["tag"] . '>';
        $pattern = '/' . $pattern . '/i';
        if (is_string($elProperties["replacement"]) == true) {
            $markdown = preg_replace($pattern, $elProperties["replacement"], $html);
        } else {
            preg_match($pattern, $html, $arg);
            for ($i = 0; $i < 4; $i++) {
                if (!isset($arg[$i])) {
                    $arg[$i] = null;
                }
            }
            $markdown = preg_replace($pattern, $elProperties["replacement"]($str = $arg[0], $attr = $arg[1], $innerHTML = $arg["2"], $hLevel = $arg["3"]), $html);
        }
        if ($markdown == '') {
            return $html;
        } else {
            return $markdown;
        }
    }

    public function attrRegExp($attr)
    {
        $pattern = '/' . $attr . '\\s*=\\s*["\']?([^"\']*)["\']?' . '/i';
        return $pattern;
    }
}

$html = new Html2md;
$string = '<strong>B标签</strong>
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
</ul><br>
<img src="http://www.baidu.com" alt="百度" /><br>
<code>code标签</code><br>
<a href="http://www.baidu.com" title="百度"></a><br>';
$md = $html->toMarkdown($string);
echo $string;
?>
<br><br><br>
<textarea rows="20" cols="100"><?php echo $md; ?></textarea>
