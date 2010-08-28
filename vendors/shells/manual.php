<?php
/**
 * Manual Shell
 * 
 * Exports the cookbook to a pdf form
 *
 * @package manual
 * @author Jose Diaz-Gonzalez
 * @version 1.o
 **/
class ManualShell extends Shell {

/**
 * Override main
 *
 * @access public
 */
    function main() {
        $this->help();
    }

/**
 * undocumented function
 *
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
    function dump() {
        $this->out(". Retrieving manual");
        if (($manual = Cache::read('manual')) === false) {
            $manual = $this->retrieve("http://book.cakephp.org/m/complete/876");
            Cache::write('manual', $manual);
        }
        $this->out(". Manual retrieved");

        $this->out(". Creating inline css");
        $manual = $this->inline_css($manual);

        $this->out(". Cleaning manual");
        $manual = $this->php_cleanup($manual);
        $manual = $this->dom_cleanup($manual);

        $this->out(". Moving TOC");
        $manual = $this->move_tob($manual);

        $this->out(". Writing manual to html");
        $this->html_write($manual);

        $this->out(". Writing manual to pdf");
        $this->pdf_write($manual);

        $this->out(". Manual successfully compiled");
    }

    function retrieve($uri, $options = array()) {
        if (is_string($options)) $options = array('callback' => $options);
        $options += array('callback' => null);

        $handle = fopen($uri, "rb");
        $contents = stream_get_contents($handle);
        fclose($handle);
        unset($handle);
        switch ($options['callback']) {
            case 'json' : $contents = json_decode($contents); break;
            case 'xml'  : $contents = simplexml_load_string($contents); break;
        }

        return $contents;
    }

    function inline_css($manual) {
        libxml_use_internal_errors(true);
        $doc = new DOMDOcument();
        $doc->validateOnParse = true;
        $doc->loadHTML($manual);
        $xpath      = new DOMXpath($doc);
        $style_node = '';
        foreach ($xpath->query('//link[@rel="stylesheet"]') as $node) {
          $style_node   = simplexml_import_dom($node)->asXML();
        }

        $style_uri  = strstr(strstr($style_node, '/css/'), '" title', true);
        $stylesheet = $this->retrieve("http://book.cakephp.org{$style_uri}");
        $stylesheet = str_replace('url(../img/', 'url(http://book.cakephp.org/img/', $stylesheet);
        $stylesheet = str_replace('url(images/', 'url(http://book.cakephp.org/css/images/', $stylesheet);
        return str_replace($style_node, "<style>{$stylesheet}</style>", $manual);
    }

    function php_cleanup($manual) {
        $string         = '<link rel="apple-touch-icon" href="/img/iphone.png"/>';
        $inline_style   = <<<style
<style>
body{font-size: 16px;}
p{margin:1em 0;}
.options, #collections_row, #search_row, #ft #secondary_nav,
#toc ul, #document_menu .yui-u[not.first] {display: none;}
ul, ol {margin-left: 1em;}
#tocFull {color: white;}
pre, pre.code, #document pre, #document pre.code{display:block;font-size:20px;overflow:visible;word-wrap:break-word;}
#document_menu{background:none}
#document_menu div.context-menu li a{color:#000}
#The-Manual-876{background:#e32;color:#fff;padding:1em 0 1em 0.5em}
</style>
style;
        $manual         = str_replace($string, "{$inline_style}{$string}", $manual);
        $manual         = str_replace('<img src="/img/', '<img src="http://book.cakephp.org/img/', $manual);
        $manual         = str_replace('<p style="clear:both">&nbsp;</p>', '', $manual);
        $patterns       = array(
            '!href="http://book.cakephp.org/m/view/([0-9]+)/([\w-]+)!',
            '!href="/m/view/([0-9]+)/([\w-]+)!',
            '!href="http:\/\/w{0,3}([a-zA-Z0-9_\-.:/~}]+)!',
            //'!<ol class="code">([^✓]+)</ol>!'
        );
        $replacements   = array(
            'href="#$2-$1',
            'href="#$2-$1',
            'href="',
            //''
        );
        return preg_replace($patterns, $replacements, $manual);
    }

    function dom_cleanup($manual) {
        libxml_use_internal_errors(true);
        $doc = new DOMDOcument();
        $doc->validateOnParse = true;
        $doc->loadHTML($manual);
        $xpath      = new DOMXpath($doc);
        $queries    = array(
            '//ul[@class="navigation"]',
            '//ul[@class="node-options"]',
            '//ol[@class="code"]',
            '//div[@class="comment"]',
            '//div[@id="search_row"]',
            '//div[@class="context-menu options"]',
            '//div[@class="context-menu feeds"]',
            '//div[@class="context-menu toc tree"]/ul'
        );
        foreach ($queries as $query) {
            foreach($xpath->query($query) as $node) {
              $node->parentNode->removeChild($node);
            }
        }

        return $doc->saveHTML();
    }

    function move_tob($manual) {
        libxml_use_internal_errors(true);
        $doc    = new DOMDOcument();
        $doc->validateOnParse = true;
        $doc->loadHTML($manual);
        $xpath  = new DOMXpath($doc);

        $result = null;

        // Moving Table of Contents 
        foreach ($xpath->query('//div[@id="document_menu"]') as $node) {
            $result = clone $node;
            $node->parentNode->removeChild($node);
        }

        foreach ($xpath->query('//div[@id="document"]') as $node) {
            $node->parentNode->insertBefore($result, $node);
        }

        // Recovering full table of contents
        foreach ($xpath->query('//div[@id="tocFull"]') as $node) {
            $result = clone $node;
            $node->parentNode->removeChild($node);
        }

        // Appending full table of contents
        foreach ($xpath->query('//div[@id="toc"]') as $node) {
            $node->appendChild($result);
        }
        return $doc->saveHTML();
    }

    function html_write($manual) {
        $file = new File(TMP . 'manual.html', true);
        $file->write($manual);
        return $file->close();
    }

    function pdf_write($manual) {
        App::import('Vendor', 'Manual.WKPDF', array('file' => 'wkpdf.php'));
        $pdf = new WKPDF(TMP);
        $pdf->set_html($manual);
        $pdf->render();
        return $pdf->output(WKPDF::$PDF_SAVEFILE, TMP . 'manual.pdf');
    }

/**
 * Displays help contents
 *
 * @access public
 */
    function help() {
        $help = <<<TEXT
The Manual Shell 
---------------------------------------------------------------
Usage: cake manual <command> <arg1> <arg2>...
---------------------------------------------------------------
Params:


Commands:

    manual dump
        dumps the manual to pdf

    manual help
        shows this help message.

TEXT;
        $this->out($help);
        $this->_stop();
    }

}