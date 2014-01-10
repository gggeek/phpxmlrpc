<?php
/**
 * takes a dir as arg, highlights all php code found in html files inside
 *
 * @author Gaetano Giunta
 * @copyright (c) 2007-2014 G. Giunta
 */

function highlight($file)
{
  $starttag = '<pre class="programlisting">';
  $endtag = '</pre>';

  $content = file_get_contents($file);
  $last = 0;
  $out = '';
  while(($start = strpos($content, $starttag, $last)) !== false)
  {
    $end = strpos($content, $endtag, $start);
	$code = substr($content, $start+strlen($starttag), $end-$start-strlen($starttag));
	if ($code[strlen($code)-1] == "\n") {
		$code = substr($code, 0, -1);
	}
//var_dump($code);
	$code = str_replace(array('&gt;', '&lt;'), array('>', '<'), $code);
    $code = highlight_string('<?php '.$code, true);
    $code = str_replace('<span style="color: #0000BB">&lt;?php&nbsp;<br />', '<span style="color: #0000BB">', $code);
//echo($code);
    $out = $out . substr($content, $last, $start+strlen($starttag)-$last) . $code . $endtag;
    $last = $end+strlen($endtag);
  }
  $out .= substr($content, $last, strlen($content));
  return $out;
}

$dir = $argv[1];

$files = scandir($dir);
foreach($files as $file)
{
	if (substr($file, -5, 5) == '.html')
	{
		$out = highlight($dir.'/'.$file);
		file_put_contents($dir.'/'.$file, $out);
	}
}

?>