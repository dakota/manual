<?php
class ManualShell extends Shell {

	function main() {
		$this->out("Getting a copy of the manual...");
		$manual = file_get_contents('http://book.cakephp.org/complete/3');

		$this->out("Create a working copy");
		$this->out("Set content-type to ISO-8859-1");
		preg_replace('/content="text\/html; charset=UTF-8"/', 'content="text\/html; charset=ISO-8859-1"', $manual);
		$this->out("Remove <script> and <link> tags");
		preg_replace('/<script [.*]><\/script>/', '', $manual);
		preg_replace('/<link [.*]><\/link>/', '', $manual);
		$manual = $this->tidyMarkup($manual);
		
		$this->out("Removed formatted code");
		preg_replace('/[^.*]<li><code>[.*$]/','', $manual);
		preg_replace('/[^.*]<li class="even"><code>[.*$]/', '', $manual);
		$manual = $this->tidyMarkup($manual);
		
		// delete last scripts
		// 7 lines before the last line
		
		// delete node options
		$this->out("Remove node options");
		preg_replace('/<ul class="node-options">[.*]<\/ul>/', '', $manual);
		$manual = $this->tidyMarkup($manual);
		// preg_replace('<li><a href="\/edit\/[^"]*">Edit<\/a><\/li>', '', $manual);
		// preg_replace('<li><a href="[^"]*" class="show-comment">Comments[^<]*<\/a><\/li>', '', $manual);
		// preg_replace('<li><a href="\/history\/[^"]*">History<\/a><\/li>', '', $manual);
		// preg_replace('<li><a href="\/view\/[^"]*">View just this section<\/a><\/li>', '', $manual);
		// preg_replace('<li class="flag pending"><a href="[^"]*">there is a pending change for this section<\/a><\/li>', '', $manual);

		// delete "See comments"
		$this->out("Remove comments");
		//preg_replace('<div class="comments".*<\/div><\/div>', '', $manual);
		preg_replace('/<div class="comment"><a href="\/comments\/[^"]*">See comments for this section<\/a><\/div>/', '', $manual);
		$manual = $this->tidyMarkup($manual);

		$manual = $this->removeIllegalCharacters($manual);
		$manual = $this->tidyMarkup($manual);
		
		$this->out("Clean #header");
		preg_replace('/\<div id="container"\>.*\<div id="body"\>/', '', $manual);
		$manual = $this->tidyMarkup($manual);

		$this->out("Clean #footer");
		preg_replace('/\<span class="prev"\>.*\<div class="clear"\>/', '', $manual);

		$this->out("Remove 1.1 manual link");
		preg_replace('/<p><strong><a href="\/305\/the-manual">Click here for the CakePHP 1.1.x version of the manual<\/a><\/strong><\/p>/', '', $manual);
		$manual = $this->tidyMarkup($manual);

		$manual = $this->formatImages($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->styleHeaders($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->styleTables($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->styleDefinitionLists($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->styleCodeBlocks($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->formatForScreen($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->styleInlineCode($manual);
		$manual = $this->tidyMarkup($manual);
		$this->out("Style methods");
		preg_replace('/ class="method">/', ' class="method"><strong><code><font size="2">', $manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->styleWarnings($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->highlight($manual);
		
		$f = new File(APP . 'tmp' . DS . 'manual' . DS.'test.html', true);
		$f->write($manual);
		$f->close();
	}
	
	function help() {
		
	}
	
	function removeIllegalCharacters($text) {
		$this->out("Replace illegal chars");
		preg_replace('/—/', '-', $text);
		preg_replace('/–/', '-', $text);
		preg_replace('/’/', "\'", $text);
		preg_replace('/\&lsquo;/', "\'", $text);
		preg_replace('/\&rsquo;/', "\'", $text);
		preg_replace('/“/', '"', $text);
		preg_replace('/”/', '"', $text);
		preg_replace('/‘/', "\'", $text);
		preg_replace('/’/', "\'", $text);
		preg_replace('/™/', "(tm)", $text);
		preg_replace('/€/', '\&#8364;', $text);
		preg_replace('/£/', '\&#163;', $text);
		preg_replace('/¥/', '\&#165;', $text);
		preg_replace('/…/', '...', $text);
		preg_replace('/é/', '\&eacute;', $text);
		preg_replace('/«/', '\&laquo;', $text);
		preg_replace('/»/', '\&raquo;', $text);
		return $text;
	}

	function formatImages($text) {
		$this->out("Format images");
		preg_replace('/<img src="\/img\//', '<img src="', $text);
		preg_replace('/src="typical-cake-request.gif"/', 'src="typical-cake-request.gif" width="500"', $text);

		# preg_replace('src="http:\/\/book.cakephp.org\/img\/', 'src="', $manual);
		return $text;
	}

	function tidyMarkup($text) {
		if (function_exists('tidy_parse_string')){
			$this->out("Tidy markup");
			$tidy = tidy_parse_string($text);
			// tidy -asxhtml -m  -i -w 10000 the-manual_work 2>/dev/null
			return tidy_get_output($tidy);
		}
	}

	function styleHeaders($text) {
		$this->out("Remove headers links");
		preg_replace('/(<a href="the-manual#[^"]*">)([#0-9.]+)(<\/a>)/', '\2', $text);
		preg_replace('/\">#/', '">', $text);
		
		$this->out("Adjust headers");
		preg_replace('/<h2 /', '<h1 ', $text);
		preg_replace('/<\/h2/', '<\/h1', $text);

		preg_replace('/<h3 /', '<h1 ', $text);
		preg_replace('/<\/h3/', '<\/h1', $text);

		preg_replace('/<h4 /', '<h2 ', $text);
		preg_replace('/<\/h4/', '<\/h2', $text);

		preg_replace('/<h5 /', '<h3 ', $text);
		preg_replace('/<\/h5/', '<\/h3', $text);

		preg_replace('/<h6 /', '<h4 ', $text);
		preg_replace('/<\/h6/', '<\/h4', $text);
		return $text;
	}

	function styleTables($text) {
		$this->out("Style tables");
		preg_replace('/<table.*>/', '<table border="1px" bordercolor="#dddddd" cellspacing="0" cellpadding="4">', $text);
		preg_replace('/<\/table>/', '<\/table><br \/>', $text);
		preg_replace('/<td><\/td>/', '<td>\&nbsp;<\/td>', $text);
		preg_replace('/<th[^>]*>/', '<th bgcolor="#f2f2f2">', $text);
		return $text;
	}

	function styleDefinitionLists($text) {
		$this->out("Style definition lists");
		preg_replace('/<dt>/', '<dt><strong>', $text);
		preg_replace('/<\/dt>/', '<\/strong><\/dt>', $text);
		return $text;
	}

	function styleCodeBlocks($text) {
		$this->out("Style code blocks");
		preg_replace('/<pre class="code">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#f9f9f9" cellpadding="5"><tr><td><pre class="code">', $text);
		preg_replace('/<pre class="plain">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#f2f2f2" cellpadding="5"><tr><td><pre class="plain">', $text);
		preg_replace('/<\/pre>/', '<\/pre><\/td><\/tr><\/table>\&nbsp;', $text);
		return $text;
	}

	function formatForScreen($text) {
		if (false) {
			preg_replace('/<pre class="shell">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#000000" cellpadding="5"><tr><td><font color="#ffffff"><pre class="shell"><strong>', $text);
		} else {
			preg_replace('/<pre class="shell">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#dddddd" cellpadding="5"><tr><td><font color="#000000"><pre class="shell"><strong>', $text);
		}
		return $text;
	}

	function styleInlineCode($text) {
		$this->out("Style inline code");
		preg_replace('/<kbd>/', '<kbd><font size="2">', $text);
		preg_replace('/<\/kbd>/', '<\/font><\/kbd>', $text);
		preg_replace('/<code>/', '<code><font size="2">', $text);
		preg_replace('/<\/code>/', '<\/font><\/code>', $text);
		return $text;
	}
	
	function styleWarnings($text) {
		$this->out("Formatting notes");
		preg_replace('/(<p class="note">)(.*)(<\/p>)/', '<table width="100%" bgcolor="#ffffbb" bordercolor="#cccc66" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="info.jpg"><\/td><td width="95%"><font size="2">\2<\/font><\/td><\/tr><\/table><\/td><\/tr><\/table><br>', $text);
		preg_replace('/(<div class="note">)(.*)(<\/div>)/', '<table width="100%" bgcolor="#ffffbb" bordercolor="#cccc66" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="info.jpg"><\/td><td width="95%"><font size="2">\2<\/font><\/td><\/tr><\/table><\/td><\/tr><\/table><br>', $text);
		preg_replace('/(<p class="warning">)(.*)(<\/p>)/', '<table width="100%" bgcolor="#ffeeee" bordercolor="#990000" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="warn.jpg"><\/td><td width="95%"><font size="2">\2<\/font><\/td><\/tr><\/table><\/td><\/tr><\/table><br>', $text);
		preg_replace('/(<div class="warning">)(.*)(<\/div>)/', '<table width="100%" bgcolor="#ffeeee" bordercolor="#990000" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="warn.jpg"><\/td><td width="95%"><font size="2">\2<\/font><\/td><\/tr><\/table><\/td><\/tr><\/table><br>', $text);

		return $text;
	}
	
	function highlight($text){
		$this->out("Highlighting");
		$start = $end = 0;
		$replacements = array();
		// $startMark = '&lt;?';
		// $endMark = '?&gt;';
		$startMark = '<pre class="code">';
		$endMark = '</pre>';
		$startOffset = strlen($startMark);
		$endOffset = 0;
		$start = strpos($text, $startMark, $start);

		while ($start !== false ) {
			$start += $startOffset;
			// $start = strpos($text, $startMark, $start);
			$end = strpos($text, $endMark, $start);
			// echo "start: $start end: $end \n";
			$length = $end - $start + $endOffset;
			$source =  substr($text, $start, $length);
			$source = trim(wordwrap($source, 90), "\n\r");
			$addMark = false;
			if (strpos($source, '&lt;?') === false) {
				$source = "&lt;?php " . $source;
				//$source = "&lt;?php" . $source . "?&gt;";
				$addMark = true;
			}
			$high = '';
			$high = highlight_string(html_entity_decode($source), true);
			$high = str_replace('span style="color: ', 'font color="', $high );
			$high = str_replace('</span>', '</font>', $high );
			$high = str_replace('<code>', '', $high );
			$high = str_replace('</code>', '', $high );
			$high = substr($high, 23);
			$high = substr($high, 0, strlen($high) -8 );
			if ($addMark) {
				$high = str_replace('&lt;?php&nbsp;', '',  $high);
				// $high = substr($high, 0, strlen($high) -14);
			}
			// $replacements[] = compact('start', 'end', 'length', 'high', 'source');
			$text = substr_replace($text, $high, $start, $length);
			$start = $start + strlen($high);
			$start = strpos($text, $startMark, $start);
		}
		$text = str_replace('<pre class="code"><br />', '<pre class="code">',  $text);
		$text = str_replace("?&gt;<br /></font>\n</pre>", "?&gt;</font></pre>",  $text);
		return $text;
	}
}
?>