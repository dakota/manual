<?php
class ManualShell extends Shell {

	var $debug = true;

	function main() {
		$manual = $this->getManual(false);
		
		$this->out("Create a working copy");
		$this->generate('01.copy', $manual);

		$manual = $this->setContentType($manual);
		$manual = $this->removeScriptLinkTags($manual);
		// This does nothing
		$manual = $this->removeFormattedCode($manual);
		$manual = $this->removeLines($manual);
		$manual = $this->removeNodeOptions($manual);
		$manual = $this->removeComments($manual);
		$manual = $this->removeIllegalCharacters($manual);
		$manual = $this->tidyMarkup($manual);
		$manual = $this->rollingCleanup($manual);
		$manual = $this->tidyMarkup($manual, false, true);
		$manual = $this->cleanHeader($manual);
		// This does nothing
		$manual = $this->cleanFooter($manual);
		// This does nothing
		$manual = $this->removeManualLink($manual);
		$manual = $this->formatImages($manual);
		$manual = $this->styleHeaders($manual);
		$manual = $this->styleTables($manual);
		$manual = $this->styleDefinitionLists($manual);
		$manual = $this->styleCodeBlocks($manual);
		$manual = $this->formatForScreen($manual);
		$manual = $this->styleInlineCode($manual);
		$manual = $this->styleMethods($manual);
		$manual = $this->styleWarnings($manual);
		$manual = $this->highlight($manual);

		// Insert some css here...
		$manual = preg_replace('/<script .*>.*<\/script>/', '', $manual);

		$f = new File(APP . 'tmp' . DS . 'manual' . DS.'test.html', true);
		$f->write($manual);
		$f->close();
		
		$this->out("DONE! You can find a copy of the manual in your tmp/manual folder.");
	}
	
	function help() {
		
	}

	function generate($name, $text) {
		if ($this->debug) {
			$f = new File(APP . 'tmp' . DS . 'manual' . DS . $name . '.html', true);
			$f->write($text);
			$f->close();
		}
	}

	function checkManualFolder() {
		$tempHandler = new Folder();
		$tempPath = trim(TMP);
		$manualPath = trim(TMP . 'manual');

		$tempHandler->cd($tempPath);
		$temp = $tempHandler->ls();
		foreach ($temp[0] as $tempFolder) {
			if ($tempFolder !== 'manual') {
				$tempHandler->create($manualPath);
			}
		}
	}

	function getManual($download = true) {
		$this->checkManualFolder();
		if ($download) {
			$this->out("Downloading a copy of the manual...");
			$manual = file_get_contents('http://book.cakephp.org/complete/3');
			$f = new File(APP . 'tmp' . DS . 'manual' . DS.'pristine.html', true);
			$f->write($manual);
			$f->close();
		} else {
			$this->out("Getting a copy of the manual...");
			$f = new File(APP . 'tmp' . DS . 'manual' . DS.'pristine.html', true);
			$manual = $f->read();
			if (empty($manual)) {
				$this->out("Temporary copy was empty! Downloading a copy of the manual...");
				$manual = file_get_contents('http://book.cakephp.org/complete/3');
				$f->write($manual);
			}
			$f->close();
		}
		return $manual;
	}

	function setContentType($text) {
		$this->out("Set content-type to ISO-8859-1");
		$text = preg_replace('/content="text\/html; charset=UTF-8"/', 'content="text/html; charset=ISO-8859-1"', $text);

		$this->generate('02.content-type', $text);
		return $text;
	}

	function removeScriptLinkTags($text) {
		$this->out("Removing <script> and <link> tags...");
		$text = preg_replace('/<script .*>.*<\/script>/', '', $text);
		$text = preg_replace('/<script .*\/>/', '', $text);
		$text = preg_replace('/<link .*>.*<\/link>/', '', $text);
		$text = preg_replace('/<link .*\/>/', '', $text);
		$this->generate('03.remove-script-link-tags', $text);

		return $text;
	}

	function removeFormattedCode($text) {
		$this->out("Removing formatted code...");
		$text = preg_replace('/<ol class="code">.*$<\/ol>/','', $text);
		$text = preg_replace('/^.*<li class="even"><code>.*$/', '', $text);
		$this->generate('04.remove-formatted-code', $text);

		return $text;
	}


	function removeLines($text, $preg = true) {
		$this->out("Removing google analytics script...");
		/*
		<script type="text/javascript">
			var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
			document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
		</script>
		<script type="text/javascript">
			var pageTracker = _gat._getTracker("UA-743287-3");
			pageTracker._initData();
			pageTracker._trackPageview();
		</script>
		*/
		if ($preg) {
			$text = preg_replace('/<script type="text\/javascript">.+<\/script>/', '', $text);
		} else {
			// read into array
			$array = explode("\n", $text);
			$lines = count($array);
			// remove last nine lines
			for($i = 0; $i < 9; $i++) {
				unset($array[$lines-3]);
				$lines = count($array);
			}
			// reindex array
			$array = array_values($array);
			// return the text

			$text = implode($array);
		}
		$this->generate('05.remove-lines', $text);
		return $text;
	}
	
	function rollingCleanup($text) {
		$this->out("Rolling array cleanup...");
		// read into array
		$lines = explode("\n", $text);
		// remove last nine lines
		$i = 0;
		$array = array();
		foreach($lines as $key => $line) {
			if (strlen(strstr($line, "title=\"Comments for "))>0) {
				unset($lines[$i]);
			} elseif(strlen(strstr($line, "Submit your thoughts"))>0) {
				unset($lines[$i]);
			} else {
				$array[] = $line;
			}
			$i++;
		}
		return implode($array);
	}

	function removeNodeOptions($text) {
		$this->out("Remove node options...");
		$text = preg_replace('/<li><a href="\/history\/[\d]+\/[-\w]+" class="dialog">History<\/a><\/li>/', '', $text);
		$text = preg_replace('/<li><a href="\/view\/[\d]+\/[-\w]+" class="dialog">View just this section<\/a><\/li>/', '', $text);

		$this->generate('06.remove-node-options', $text);
		return $text;
	}

	function removeComments($text) {
		$this->out("Remove comments...");
		$text = preg_replace('/<li><a href="\/comments\/index\/[\d]+" title="Comments for [\w\s\d]+" class="dialog">Comments \([\d]+\)<\/a><\/li>/', '', $text);

		$this->generate('07.remove-comments', $text);
		return $text;
	}

	function removeIllegalCharacters($text) {
		$this->out("Replace illegal chars...");
		$text = preg_replace('/—/', '-', $text);
		$text = preg_replace('/–/', '-', $text);
		$text = preg_replace('/’/', "'", $text);
		$text = preg_replace('/&lsquo;/', "'", $text);
		$text = preg_replace('/&rsquo;/', "'", $text);
		$text = preg_replace('/“/', '"', $text);
		$text = preg_replace('/”/', '"', $text);
		$text = preg_replace('/‘/', "\'", $text);
		$text = preg_replace('/’/', "\'", $text);
		$text = preg_replace('/™/', "(tm)", $text);
		$text = preg_replace('/€/', '&#8364;', $text);
		$text = preg_replace('/£/', '&#163;', $text);
		$text = preg_replace('/¥/', '&#165;', $text);
		$text = preg_replace('/…/', '...', $text);
		$text = preg_replace('/é/', '&eacute;', $text);
		$text = preg_replace('/«/', '&laquo;', $text);
		$text = preg_replace('/»/', '&raquo;', $text);

		$this->generate('08.remove-illegal-characters', $text);
		return $text;
	}
	
	function cleanHeader($text) {
		$this->out("Cleaning #header...");
		$text = preg_replace('/<div id="container">.*<div id="body">/', '<div id="body">', $text);

		$this->generate('09.remove-header', $text);
		return $text;
	}

	function cleanFooter($text) {
		$this->out("Cleaning #footer...");
		$text = preg_replace('/<span class="prev">.*<\/span>/', '', $text);

		$this->generate('10.remove-footer', $text);
		return $text;
	}

	function removeManualLink($text) {
		$this->out("Removing 1.1 manual link...");
		$text = preg_replace('/<p><strong><a href="\/305\/the-manual">Click here for the CakePHP 1.1.x version of the manual<\/a><\/strong><\/p>/', '', $text);

		$this->generate('11.remove-11-link', $text);
		return $text;
	}

	function formatImages($text) {
		$this->out("Formatting images...");
		$text = preg_replace('/<img src="\/img\//', '<img src="', $text);
		$text = preg_replace('/src="typical-cake-request.gif"/', 'src="typical-cake-request.gif" width="500"', $text);

		$this->generate('12.format-images', $text);
		return $text;
	}

	function tidyMarkup($text, $lawed = false, $second = false) {
		if (function_exists('tidy_get_output')){
			$this->out("Tidying up markup...");
			$config = array(
				'indent'=> true,
				'output-xml' => true,
				'markup' => true,
				'wrap' => '1000');

			// Tidy
			$tidy = new tidy();
			$tidy->parseString($text, $config, 'utf8');
			$tidy->cleanRepair();
			// tidy -asxhtml -m  -i -w 10000 the-manual_work 2>/dev/null
			return tidy_get_output($tidy);
		} elseif ($lawed == true) {
			App::import('Vendor', 'cookbook.LawedHtml', array('file' => 'LawedHtml' . DS . 'lawed.lib.php'));
			$law = new LawedHtml($tempZipPath);
			return $law->htmLawed($text);
		}

		if ($second) {
			$this->generate('08.tidyMarkup2', $text);
		} else {
			$this->generate('08.tidyMarkup', $text);
			
		}
		return $text;
	}

	function styleHeaders($text) {
		$this->out("Removing headers links...");
		$text = preg_replace('/(<a href="the-manual#^"*">)([#0-9.]+)(<\/a>)/', '\2', $text);
		$text = preg_replace('/\">#/', '">', $text);
		
		$this->out("Adjusting headers...");
		$text = preg_replace('/<h2 /', '<h1 ', $text);
		$text = preg_replace('/<\/h2/', '</h1', $text);

		$text = preg_replace('/<h3 /', '<h1 ', $text);
		$text = preg_replace('/<\/h3/', '</h1', $text);

		$text = preg_replace('/<h4 /', '<h2 ', $text);
		$text = preg_replace('/<\/h4/', '</h2', $text);

		$text = preg_replace('/<h5 /', '<h3 ', $text);
		$text = preg_replace('/<\/h5/', '</h3', $text);

		$text = preg_replace('/<h6 /', '<h4 ', $text);
		$text = preg_replace('/<\/h6/', '</h4', $text);

		$this->generate('13.style-headers', $text);
		return $text;
	}

	function styleTables($text) {
		$this->out("Styling tables...");
		$text = preg_replace('/<table.*>/', '<table border="1px" bordercolor="#dddddd" cellspacing="0" cellpadding="4">', $text);
		$text = preg_replace('/<\/table>/', '</table><br />', $text);
		$text = preg_replace('/<td><\/td>/', '<td>&nbsp;</td>', $text);
		$text = preg_replace('/<th^>*>/', '<th bgcolor="#f2f2f2">', $text);

		$this->generate('14.style-tables', $text);
		return $text;
	}

	function styleDefinitionLists($text) {
		$this->out("Styling definition lists...");
		$text = preg_replace('/<dt>/', '<dt><strong>', $text);
		$text = preg_replace('/<\/dt>/', '</strong></dt>', $text);

		$this->generate('15.style-dls', $text);
		return $text;
	}

	function styleCodeBlocks($text) {
		$this->out("Styling code blocks...");
		$text = preg_replace('/<pre class="code">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#f9f9f9" cellpadding="5"><tr><td><pre class="code">', $text);
		$text = preg_replace('/<pre class="plain">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#f2f2f2" cellpadding="5"><tr><td><pre class="plain">', $text);
		$text = preg_replace('/<\/pre>/', '</pre></td></tr></table>&nbsp;', $text);

		$this->generate('16.style-codeblocks', $text);
		return $text;
	}

	function formatForScreen($text) {
		$this->out("Formatting for screen...");
		if (false) {
			$text = preg_replace('/<pre class="shell">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#000000" cellpadding="5"><tr><td><font color="#ffffff"><pre class="shell"><strong>', $text);
		} else {
			$text = preg_replace('/<pre class="shell">/', '<table border="1px" bordercolor="#dddddd" width="100%" bgcolor="#dddddd" cellpadding="5"><tr><td><font color="#000000"><pre class="shell"><strong>', $text);
		}

		$this->generate('17.format-screen', $text);
		return $text;
	}

	function styleInlineCode($text) {
		$this->out("Styling inline code...");
		$text = preg_replace('/<kbd>/', '<kbd><font size="2">', $text);
		$text = preg_replace('/<\/kbd>/', '</font></kbd>', $text);
		$text = preg_replace('/<code>/', '<code><font size="2">', $text);
		$text = preg_replace('/<\/code>/', '</font></code>', $text);

		$this->generate('18.style-inline-code', $text);
		return $text;
	}

	function styleMethods($text) {
		$this->out("Style methods");
		$text = preg_replace('/ class="method">/', ' class="method"><strong><code><font size="2">', $text);

		$this->generate('19.style-methods', $text);
		return $text;
	}

	function styleWarnings($text) {
		$this->out("Formatting notes...");
		$text = preg_replace('/(<p class="note">)(.*)(<\/p>)/', '<table width="100%" bgcolor="#ffffbb" bordercolor="#cccc66" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="info.jpg"></td><td width="95%"><font size="2">2</font></td></tr></table></td></tr></table><br>', $text);
		$text = preg_replace('/(<div class="note">)(.*)(<\/div>)/', '<table width="100%" bgcolor="#ffffbb" bordercolor="#cccc66" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="info.jpg"></td><td width="95%"><font size="2">2</font></td></tr></table></td></tr></table><br>', $text);
		$text = preg_replace('/(<p class="warning">)(.*)(<\/p>)/', '<table width="100%" bgcolor="#ffeeee" bordercolor="#990000" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="warn.jpg"></td><td width="95%"><font size="2">2</font></td></tr></table></td></tr></table><br>', $text);
		$text = preg_replace('/(<div class="warning">)(.*)(<\/div>)/', '<table width="100%" bgcolor="#ffeeee" bordercolor="#990000" cellpadding="10"><tr><td><table><tr><td width="5%"><img width="22" height="22" src="warn.jpg"></td><td width="95%"><font size="2">2</font></td></tr></table></td></tr></table><br>', $text);

		$this->generate('20.style-warnings', $text);
		return $text;
	}

	function highlight($text){
		$this->out("Highlighting...");
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

		$this->generate('21.highlight', $text);
		return $text;
	}
}
?>