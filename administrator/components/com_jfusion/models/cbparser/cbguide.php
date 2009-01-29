<?php // ۞// text { encoding:utf-8 ; bom:no ; linebreaks:unix ; tabs:4sp ; }
/*

	cbguide
	the cbparser bbcode guide 
	
	a part of corzblog.. http://corz.org/blog/

	;o)
	(or

	© 2003-> (or @ corz.org ;o)

*/

// wherever you plan to keep your buttons..
if (empty($butt_dir)) { $butt_dir = '/cbparser/img/buttons/'; }


global $smiley_folder;
if (!stristr($_SERVER['REQUEST_URI'], 'blog')) {
	echo '
<script type="text/javascript" src="/cbparser/js/func.js"></script>';
}

echo '
	<div class="fill" id="cbguide">
		<div class="left" id="js-buttons">
			<a href="javascript:void(0);" onclick="boldz(event); return false;">
			<img alt="button for bold text" title="subtly (if you have anti-alaising) bolded text" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) { button_highlight(this, \'',$butt_dir,'\', false); }" src="',$butt_dir,'bold.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="italicz(event); return false;">
			<img alt="button for italic text" title="italic text (slanty)" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'italic.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="bigz(event); return false;">
			<img alt="button for big text" title="bigger text. (you can also use [size=12]pixel size[/size] tags)" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'big.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="smallz(event); return false;">
			<img alt="button for small text" title="smaller text. (you can also use [size=7]pixel size[/size] tags)" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'small.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="block(event); return false;">
			<img alt="button for a blockquote" title="a [block]blockquote[/block]" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'block.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="tt(event); return false;">
			<img alt="button for teletype text" title="[tt]teletype[/tt]" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'teletype.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="codez(event); return false;">
			<img alt="active button for the code box tag" title="a nice code box. handy for code or quotes" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'code.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="pre(event); return false;">
			<img alt="button for preformatted text" title="Preformatted Text (it keeps its spaces and tabs - and you can put bbcode inside it. handy.)" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'pre.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="ccc(event); return false;">
			<img alt="button for cool colored code" title="the Cool Colored Code&trade; Tag, for php (this button will add php tags automatically)" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'ccc.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="box(event); return false;">
			<img alt="button for the box tag" title="a box. (a span, will flow with your text)" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'box.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="bbox(event); return false;">
			<img alt="button for the bbox tag" title="a big box. it will try and fill all its sapce" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'bbox.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="refz(event); return false;">
			<img alt="active button for reference tag" title="a clickable reference. edit in your details afterwards" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'ref.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="doimage(event); return false;">
			<img alt="button for an image tag" title="simple image tag" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'image.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>

			<a href="javascript:void(0);" onclick="linkz(event); return false;">
			<img alt="active button for URL tag" title="you will be asked to supply a URL and a title for this link" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'url.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>
		</div>

		<div class="right" id="symbol-selecta">
			<span class="byline" title="select a symbol from the pull-down menu" id="fooness">
				<select name="dropdown" onchange="symbol(event);return false;" id="symbol-select">
					<option value="">&nbsp;&nbsp;&nbsp;</option>
					<option value="&bull;">&bull;</option>
					<option value="&deg;">&deg;</option>
					<option value="&plusmn;">&plusmn;</option>
					<option value="&trade;">&trade;</option>
					<option value="&copy;">&copy;</option>
					<option value="&reg;">&reg;</option>
					<option value="[[">[</option>
					<option value="]]">]</option>
					<option value="&hellip;">&hellip;</option>
				</select>
			</span>
			<a href="javascript:void(0);" onclick="UndoThat(event); return false;">
			<img alt="button to undo the last javascript change" title="this button takes you back to just before your last magic edit" onmouseover="button_highlight(this, \'',$butt_dir,'\', true);" onmouseout="if (window.button_highlight) button_highlight(this, \'',$butt_dir,'\', false);" src="',$butt_dir,'undo.gif" style="background-image: url(',$butt_dir,'button.gif);" /></a>
		</div>
	</div>
	<div class="clear">&nbsp;</div>

	<div class="cbinfo">

		<div class="left">
			<span class="byline">
				<strong><a title="you can use these as span classes, too." class="turl">headers.. </a></strong>
			</span><br />
			<span class="h6"><a title="you can click this to insert a type six header into your blog" 
			onclick="h6(event);"> six </a></span>
			<span class="h5"><a title="clicking this inserts a type five header into your blog" 
			onclick="h5(event);"> five </a></span>
			<span class="h4"><a title="and this is the type four" 
			onclick="h4(event);"> four </a></span>
			<span class="h3"><a title="same story for a type three header" 
			onclick="h3(event);"> three </a></span>
			<span class="h2"><a title="and so on for the type two, you get the idea" 
			onclick="h2(event);"> two </a></span>
		</div>

		<script type="text/javascript">
		//<![CDATA[
		<!--
		document.write("<div class=\"smileys\"><span class=\"byline\"><strong>..smileys<\/strong><\/span><br /><span class=\"h2\">&nbsp;<\/span> <img alt=\"smiley for :lol:\" title=\"smiley for :lol: (click to insert into text)\" src=\"'.$smiley_folder.'lol.gif\" onclick=\"smiley_lol(event);\" /> <img alt=\"smiley for :ken:\" title=\"smiley for :ken: (click to insert into text)\" src=\"'.$smiley_folder.'ken.gif\" onclick=\"smiley_ken(event);\" /> <img alt=\"smiley for :D\" title=\"smiley for :D (click to insert into text)\" src=\"'.$smiley_folder.'grin.gif\" onclick=\"smiley_grin(event);\" /> <img alt=\"smiley for :)\" title=\"smiley for :) (click to insert into text)\" src=\"'.$smiley_folder.'smile.gif\" onclick=\"smiley_smile(event);\" /> <img alt=\"smiley for ;)\" title=\"smiley for ;) (click to insert into text)\" src=\"'.$smiley_folder.'wink.gif\" onclick=\"smiley_wink(event);\" /> <img alt=\"smiley for :eek:\" title=\"smiley for :eek: (click to insert into text)\" src=\"'.$smiley_folder.'eek.gif\" onclick=\"smiley_eek(event);\" /> <img alt=\"smiley for :geek:\" title=\"smiley for :geek: (click to insert into text)\" src=\"'.$smiley_folder.'geek.gif\" onclick=\"smiley_geek(event);\" /> <img alt=\"smiley for :roll:\" title=\"smiley for :roll: (click to insert into text)\" src=\"'.$smiley_folder.'roll.gif\" onclick=\"smiley_roll(event);\" /> <img alt=\"smiley for :erm:\" title=\"smiley for :erm: (click to insert into text)\" src=\"'.$smiley_folder.'erm.gif\" onclick=\"smiley_erm(event);\" /> <img alt=\"smiley for :cool:\" title=\"smiley for :cool: (click to insert into text)\" src=\"'.$smiley_folder.'cool.gif\" onclick=\"smiley_cool(event);\" /> <img alt=\"smiley for :blank:\" title=\"smiley for :blank: (click to insert into text)\" src=\"'.$smiley_folder.'blank.gif\" onclick=\"smiley_blank(event);\" /> <img alt=\"smiley for :idea:\" title=\"smiley for :idea: (click to insert into text)\" src=\"'.$smiley_folder.'idea.gif\" onclick=\"smiley_idea(event);\" /> <img alt=\"smiley for :ehh:\" title=\"smiley for :ehh: (click to insert into text)\"   src=\"'.$smiley_folder.'ehh.gif\" onclick=\"smiley_ehh(event);\" /> <img alt=\"smiley for :aargh:\" title=\"smiley for :aargh: (click to insert into text)\" src=\"'.$smiley_folder.'aargh.gif\" onclick=\"smiley_aargh(event);\" /> <img alt=\"smiley for :evil:\" title=\"smiley for :evil: (click to insert into text)\" src=\"'.$smiley_folder.'evil.gif\" onclick=\"smiley_evil(event);\" /><\/div>");
		//-->
		//]]>
		</script>

		<div class="clear">&nbsp;</div>

		<noscript>
			<div class="smileys">
				<img alt="smiley for :lol:" title="smiley for :lol:" src="'.$smiley_folder.'lol.gif" />
				<img alt="smiley for :ken:" title="smiley for :ken:" src="'.$smiley_folder.'ken.gif" />
				<img alt="smiley for :D" title="smiley for :D" src="'.$smiley_folder.'grin.gif" />
				<img alt="smiley for :)" title="smiley for :)" src="'.$smiley_folder.'smile.gif" />
				<img alt="smiley for ;)" title="smiley for ;)" src="'.$smiley_folder.'wink.gif" />
				<img alt="smiley for :eek:" title="smiley for :eek:" src="'.$smiley_folder.'eek.gif" />
				<img alt="smiley for :geek:" title="smiley for :geek:" src="'.$smiley_folder.'geek.gif"  />
				<img alt="smiley for :roll:" title="smiley for :roll:" src="'.$smiley_folder.'roll.gif" />
				<img alt="smiley for :erm:" title="smiley for :erm:" src="'.$smiley_folder.'erm.gif" />
				<img alt="smiley for :cool:" title="smiley for :cool:" src="'.$smiley_folder.'cool.gif" />
				<img alt="smiley for :blank:" title="smiley for :blank:" src="'.$smiley_folder.'blank.gif" />
				<img alt="smiley for :idea:" title="smiley for :idea:" src="'.$smiley_folder.'idea.gif" />
				<img alt="smiley for :ehh:" title="smiley for :ehh:" src="'.$smiley_folder.'ehh.gif" />
				<img alt="smiley for :aargh:" title="smiley for :aargh:" src="'.$smiley_folder.'aargh.gif" />
				<img alt="smiley for :evil:" title="smiley for :evil:" src="'.$smiley_folder.'evil.gif" />
			</div>
		</noscript>
	</div>

	<div class="tiny-space">&nbsp;</div>

		<div class="cbguide">

			<h3><a href="http://corz.org/blog/inc/cbparser.php" title="test-drive the corzblog bbcode to html and back to bbcode parser!">cbparser quick bbcode guide..</a></h3>

			Most common bbtags are supported, and with cbparser\'s InfiniTags&trade; you can pretty much just make up
			tags as you go along. If cbparser can construct valid xhtml tags out of them, it will. Experimentation is the key, and preview often.<br />
			<br />
			
			A few <a onclick="window.open(this.href); return false;" href="http://corz.org/bbtags" 
			title="learn all the tags! and test them out, too!"><strong>bbcode</strong></a> examples..<br />

			[b]<strong>bold</strong>[/b], [i]<em>italic</em>[/i], [big]<big>big</big>[/big], [sm]<small>small</small>[/sm], 
			 [img]http://foo.com/image.png[/img], [code]<span class="simcode">code</span>[/code], 
			[url="http://foo.com" title="foo!"]<a href="http://corz.org/corz/glossary.php" title="foo!" id="TheFooLink" onclick="window.open(this.href); return false;">foo U!</a>[/url], 
			
			<a onclick="window.open(this.href); return false;" href="http://corz.org/bbtags" 
			title="learn all the tags! and test them out, too!">and more..</a> To post code with indentation and/or strange characters, use [pre][/pre] tags.
		</div>

';
?>