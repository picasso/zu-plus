<?php
trait ZU_StackoverflowTrait {

	// Simple regex CSS minifier/compressor --------------------------------------]
	// http://stackoverflow.com/questions/15195750/minify-compress-css-with-regex

	public function minify_css($str) {
	    // remove comments first (simplifies the other regex)
$re1 = <<<'EOS'
(?sx)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
 )
|
  # comments
  /\* (?> .*? \*/)
EOS;

$re2 = <<<'EOS'
(?six)
  # quotes
  (
    "(?:[^"\\]++|\\.)*+"
  | '(?:[^'\\]++|\\.)*+'
 )
|
  # ; before } (and the spaces after it while we're here)
  \s*+ ; \s*+ (}) \s*+
|
  # all spaces around meta chars/operators
  \s*+ ([*$~^|]?+= | [{};,>~+-] | !important\b) \s*+
|
  # spaces right of ([ :
  ([[(:]) \s++
|
  # spaces left of) ]
  \s++ ([])])
|
  # spaces left (and right) of :
  \s++ (:) \s*+
  # but not in selectors: not followed by a {
  (?!
    (?>
      [^{}"']++
    | "(?:[^"\\]++|\\.)*+"
    | '(?:[^'\\]++|\\.)*+' 
   )*+
    {
 )
|
  # spaces at beginning/end of string
  ^ \s++ | \s++ \z
|
  # double spaces to single
  (\s)\s+
EOS;
	
	    $str = preg_replace("%$re1%", '$1', $str);
	    return preg_replace("%$re2%", '$1$2$3$4$5$6$7', $str);
	}

	// Get base URL with PHP -----------------------------------------------------]
	// https://stackoverflow.com/questions/2820723/how-to-get-base-url-with-php

    public function base_url($at_root = false, $at_core = false, $parse = false) {
        if(isset($_SERVER['HTTP_HOST'])) {
            $http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $hostname = $_SERVER['HTTP_HOST'];
            $dir =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

            $core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), NULL, PREG_SPLIT_NO_EMPTY);
            $core = $core[0];

            $tmplt = $at_root ? ($at_core ? "%s://%s/%s/" : "%s://%s/") : ($at_core ? "%s://%s/%s/" : "%s://%s%s");
            $end = $at_root ? ($at_core ? $core : $hostname) : ($at_core ? $core : $dir);
            $base_url = sprintf($tmplt, $http, $hostname, $end);
        }
        else $base_url = 'http://localhost/';

        if($parse) {
            $base_url = parse_url($base_url);
            if(isset($base_url['path'])) if($base_url['path'] == '/') $base_url['path'] = '';
        }

        return $base_url;
    }
}
