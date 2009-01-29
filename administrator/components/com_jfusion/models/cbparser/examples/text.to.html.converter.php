<?php
$version = '0.3.2';

/*
	simple text to html converter 
	
	I threw this together primarily as an example for "how to use cbparser", my bbcode parser,
	and because I figured it would be easier than using corzblog to create simple html files.;
	here we get the raw html back directly, saving one step.
	
	I use cbparser (the "my oh! how did you find it!" bbcode parser) for my text-2-html
	convertions anyway, and this wee gui is proving handy for me, might be handy for you, too.
	
	All the "extra" functions are covered, too, 
	
	Obviously, you need cbparser.php bbcode parser somewhere to use this. get that here..
	
	http://corz.org/engine?download=menu&section=corz%20function%20library
	
	;o)
	(or
	
	© corz.org 2004->
	
*/

// saves typing..
$corz_root = $_SERVER['DOCUMENT_ROOT'];

// do your own thing here..
@include ($corz_root.'/inc/init.php');
//@include ($corz_root.'/inc/doc-type.php');

// include the bbcode parser, very important.
require ($corz_root.'/blog/inc/cbparser.php');


if (isset($_POST['cvrt-text'])) { $text = ($_POST['cvrt-text']); } else { $text = ''; }
if (get_magic_quotes_gpc()) { $text = stripslashes($text); }

//	you posted some text..
if (isset($_POST['preview'])) {

	//	this is the main line. 
	//	this line is all you need for basic convertion jobs.
	$converted = bb2html($text);
	
	//	we can do some error checking, too..
	if ($converted == '') $converted = "tags don't balance!\n\nin other words, you have opened a tag, but not closed it, or something..\ngo back and try again!";

} else { $converted = ''; }

	if (stristr(@$_SERVER['HTTP_ACCEPT'],'application/xhtml+xml')) {
		$doc_content = 'application/xhtml+xml'; } else { $doc_content = 'text/html'; }

	echo '<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="content-type" content="'.$doc_content.'; charset=utf-8" />
<title>convert text to html (xhtml 1.0 strict) tags with the php text converter tool. (accepts bbcode)</title>
<meta name="description" content="convert text to html, convert bbcode to html, convert bbcode text to htmland more! actually, you can do bbcode, too" />
<meta name="keywords" content="convert,text to html, convert bbcode to html, convert text,text2html, text-to-html, text-2-html,text to xhtml,text,xhtml,converter,tool" />

<style type="text/css">/*<![CDATA[*/ 
@import "/inc/css/main.css"; 
@import "/inc/css/site.css"; 
@import "/inc/css/comments.css"; 
@import "/inc/css/footer.css"; 
/*]]>*/</style>
</head>
<body>
<div class="wide-content">';
@include ($corz_root.'/inc/header.php');

echo '
	<h1>bbcode capable text to html converter..</h1>
	Convert text to html with this tool. Put plain text in, get xhtml tags back..&nbsp;
	<small>(hint: you can use <a href="http://corz.org/blog/inc/cbparser.php"
	id="cbparser-bbcode-parser-link"title="this is just a wee gui for one of cbparser\'s functions"
	onclick="window.open(this.href); return false;">bbcode</a>)</small><br />
	hit "preview" to get back your html tags.

	<div class="tiny-space">&nbsp;</div>

	<div style="width: 90%">';

do_bb_form($converted,'', '', false, '', false, '', '', 'cvrt', true, false);

echo '
	</div>
	<div class="toplinks">
		you can run this at home..&nbsp;&nbsp;
		<a href="http://corz.org/engine?section=php&amp;download=text.to.html.converter.php.zip" 
		title="download the source! use your own effin bandwidth!"><strong>source is available!</strong></a>
	</div>'; 

@include ($corz_root.'/inc/footerx.php');

echo '
</div>
</body>
</html>
';
?>