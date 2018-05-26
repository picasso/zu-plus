<?php
trait ZU_StackoverflowTrait {

	// Simple HTML minifier ------------------------------------------------------]
	// https://stackoverflow.com/questions/6225351/how-to-minify-php-page-html-output
	
	public function minify_html($buffer, $remove_ending_tags = true) {
	
		//remove redundant (white-space) characters
		$replace = [
		    //remove tabs before and after HTML tags
		    '/\>[^\S ]+/s'   => '>',
		    '/[^\S ]+\</s'   => '<',
		    //shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
		    '/([\t ])+/s'  => ' ',
		    //remove leading and trailing spaces
		    '/^([\t ])+/m' => '',
		    '/([\t ])+$/m' => '',
		    // remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
		    '~//[a-zA-Z0-9 ]+$~m' => '',
		    //remove empty lines (sequence of line-end and white-space characters)
		    '/[\r\n]+([\t ]?[\r\n]+)+/s'  => "\n",
		    //remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
		    '/\>[\r\n\t ]+\</s'    => '><',
		    //remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
		    '/}[\r\n\t ]+/s'  => '}',
		    '/}[\r\n\t ]+,[\r\n\t ]+/s'  => '},',
		    //remove new-line after JS's function or condition start; join with next line
		    '/\)[\r\n\t ]?{[\r\n\t ]+/s'  => '){',
		    '/,[\r\n\t ]?{[\r\n\t ]+/s'  => ',{',
		    //remove new-line after JS's line end (only most obvious and safe cases)
		    '/\),[\r\n\t ]+/s'  => '),',
		    //remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
		    '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s' => '$1$2=$3$4', //$1 and $4 insert first white-space character found before/after attribute
		];
		
		$buffer = preg_replace(array_keys($replace), array_values($replace), $buffer);
		
		//remove optional ending tags (see http://www.w3.org/TR/html5/syntax.html#syntax-tag-omission)
		$remove = array(
		    '</option>', '</li>', '</dt>', '</dd>', '</tr>', '</th>', '</td>'
		);
		$buffer = $remove_ending_tags ? str_ireplace($remove, '', $buffer) : $buffer;
		
		return $buffer;
	}

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

	// Simple HTML beautifier ----------------------------------------------------]
	// https://github.com/gajus/dindent
	
	public function beautify_html($string, $insert_new_line = false) {
		
		$indenter = new Dindent_Gajus();
		$output = $indenter->indent($string);
		return $insert_new_line ? sprintf('[html]:%2$s%1$s%2$s', $output, PHP_EOL) : $output; 
	}
}

// Dindent (aka., "HTML beautifier") will indent HTML -------------------------]
// https://github.com/gajus/dindent - for the canonical source repository
// license: https://github.com/gajus/dindent/blob/master/LICENSE - BSD 3-Clause

class Dindent_Gajus {
    private
        $log = array(),
        $options = array(
            'indentation_character' => '    '
        ),
        $inline_elements = array('b', 'big', 'i', 'small', 'tt', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'a', 'bdo', 'br', 'img', 'span', 'sub', 'sup'),
        $temporary_replacements_script = array(),
        $temporary_replacements_inline = array();

    const ELEMENT_TYPE_BLOCK = 0;
    const ELEMENT_TYPE_INLINE = 1;

    const MATCH_INDENT_NO = 0;
    const MATCH_INDENT_DECREASE = 1;
    const MATCH_INDENT_INCREASE = 2;
    const MATCH_DISCARD = 3;

    /**
     * @param array $options
     */
    public function __construct (array $options = array()) {
        foreach ($options as $name => $value) {
            if (!array_key_exists($name, $this->options)) {
                throw new Dindent_InvalidArgumentException('Unrecognized option.');
            }

            $this->options[$name] = $value;
        }
    }

    /**
     * @param string $element_name Element name, e.g. "b".
     * @param ELEMENT_TYPE_BLOCK|ELEMENT_TYPE_INLINE $type
     * @return null
     */
    public function setElementType ($element_name, $type) {
        if ($type === static::ELEMENT_TYPE_BLOCK) {
            $this->inline_elements = array_diff($this->inline_elements, array($element_name));
        } else if ($type === static::ELEMENT_TYPE_INLINE) {
            $this->inline_elements[] = $element_name;
        } else {
            throw new Dindent_InvalidArgumentException('Unrecognized element type.');
        }

        $this->inline_elements = array_unique($this->inline_elements);
    }

    /**
     * @param string $input HTML input.
     * @return string Indented HTML.
     */
    public function indent ($input) {
        $this->log = array();

        // Dindent does not indent <script> body. Instead, it temporary removes it from the code, indents the input, and restores the script body.
        if (preg_match_all('/<script\b[^>]*>([\s\S]*?)<\/script>/mi', $input, $matches)) {
            $this->temporary_replacements_script = $matches[0];
            foreach ($matches[0] as $i => $match) {
                $input = str_replace($match, '<script>' . ($i + 1) . '</script>', $input);
            }
        }

        // Removing double whitespaces to make the source code easier to read.
        // With exception of <pre>/ CSS white-space changing the default behaviour, double whitespace is meaningless in HTML output.
        // This reason alone is sufficient not to use Dindent in production.
        $input = str_replace("\t", '', $input);
        $input = preg_replace('/\s{2,}/', ' ', $input);

        // Remove inline elements and replace them with text entities.
        if (preg_match_all('/<(' . implode('|', $this->inline_elements) . ')[^>]*>(?:[^<]*)<\/\1>/', $input, $matches)) {
            $this->temporary_replacements_inline = $matches[0];
            foreach ($matches[0] as $i => $match) {
                $input = str_replace($match, 'ᐃ' . ($i + 1) . 'ᐃ', $input);
            }
        }

        $subject = $input;

        $output = '';

        $next_line_indentation_level = 0;

        do {
            $indentation_level = $next_line_indentation_level;

            $patterns = array(
                // block tag
                '/^(<([a-z]+)(?:[^>]*)>(?:[^<]*)<\/(?:\2)>)/' => static::MATCH_INDENT_NO,
                // DOCTYPE
                '/^<!([^>]*)>/' => static::MATCH_INDENT_NO,
                // tag with implied closing
                '/^<(input|link|meta|base|br|img|source|hr)([^>]*)>/' => static::MATCH_INDENT_NO,
                // self closing SVG tags
                '/^<(animate|stop|path|circle|line|polyline|rect|use)([^>]*)\/>/' => static::MATCH_INDENT_NO,
                // opening tag
                '/^<[^\/]([^>]*)>/' => static::MATCH_INDENT_INCREASE,
                // closing tag
                '/^<\/([^>]*)>/' => static::MATCH_INDENT_DECREASE,
                // self-closing tag
                '/^<(.+)\/>/' => static::MATCH_INDENT_DECREASE,
                // whitespace
                '/^(\s+)/' => static::MATCH_DISCARD,
                // text node
                '/([^<]+)/' => static::MATCH_INDENT_NO
            );
            $rules = array('NO', 'DECREASE', 'INCREASE', 'DISCARD');

            foreach ($patterns as $pattern => $rule) {
                if ($match = preg_match($pattern, $subject, $matches)) {
                    $this->log[] = array(
                        'rule' => $rules[$rule],
                        'pattern' => $pattern,
                        'subject' => $subject,
                        'match' => $matches[0]
                    );

                    $subject = mb_substr($subject, mb_strlen($matches[0]));

                    if ($rule === static::MATCH_DISCARD) {
                        break;
                    }

                    if ($rule === static::MATCH_INDENT_NO) {

                    } else if ($rule === static::MATCH_INDENT_DECREASE) {
                        $next_line_indentation_level--;
                        $indentation_level--;
                    } else {
                        $next_line_indentation_level++;
                    }

                    if ($indentation_level < 0) {
                        $indentation_level = 0;
                    }

                    $output .= str_repeat($this->options['indentation_character'], $indentation_level) . $matches[0] . "\n";

                    break;
                }
            }
        } while ($match);

        $interpreted_input = '';
        foreach ($this->log as $e) {
            $interpreted_input .= $e['match'];
        }

        if ($interpreted_input !== $input) {
            throw new Dindent_RuntimeException('Did not reproduce the exact input.');
        }

        $output = preg_replace('/(<(\w+)[^>]*>)\s*(<\/\2>)/', '\\1\\3', $output);

        foreach ($this->temporary_replacements_script as $i => $original) {
            $output = str_replace('<script>' . ($i + 1) . '</script>', $original, $output);
        }

        foreach ($this->temporary_replacements_inline as $i => $original) {
            $output = str_replace('ᐃ' . ($i + 1) . 'ᐃ', $original, $output);
        }

        return trim($output);
    }

    /**
     * Debugging utility. Get log for the last indent operation.
     *
     * @return array
     */
    public function getLog () {
        return $this->log;
    }
}
class DindentException extends Exception {}
class Dindent_InvalidArgumentException extends DindentException {}
class Dindent_RuntimeException extends DindentException {}