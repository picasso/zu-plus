<?php
include_once('traits/zuplus-date.php');
include_once('traits/zuplus-loader.php');
include_once('traits/zuplus-stackoverflow.php');

class ZU_PlusFunctions {

	private static $_zufunc_instance;
	
	private $random_attachment_id;
	private 	$advanced_style;
	private $admin_style;
	private $fonts;
	
	function __construct() {
		
		$this->random_attachment_id = null;
		$this->advanced_style = [];
		$this->admin_style = [];
		$this->fonts = [];
		add_action('wp_footer', [$this, 'maybe_add_advanced_styles']);
	}
	
	public static function instance() {
		if(!isset(self::$_zufunc_instance)) {
			$class_name = __CLASS__;
			self::$_zufunc_instance = new $class_name;
		}
		return self::$_zufunc_instance;
	}

	use ZU_DateTrait, ZU_LoaderTrait, ZU_StackoverflowTrait;
	
	// Color functions -----------------------------------------------------------]
	
	public function get_color_contrast($color) {
	
		$light_on_dark = '#ffffff';
		$dark_on_light = '#222222';
	
		$hexcolor = str_replace('#', '', $color);
		$red = hexdec(substr($hexcolor, 0, 2));
		$green = hexdec(substr($hexcolor, 2, 2));
		$blue = hexdec(substr($hexcolor, 4, 2));
		$luminosity = (($red * 0.2126) + ($green * 0.7152) + ($blue * 0.0722));
	
		return ($luminosity > 128) ? $dark_on_light : $light_on_dark;
	}
	
	public function chage_color_brightness($color, $change) {
	
		$hexcolor = str_replace('#', '', $color);
	
		$red = hexdec(substr($hexcolor, 0, 2));
		$green = hexdec(substr($hexcolor, 2, 2));
		$blue  = hexdec(substr($hexcolor, 4, 2));
	
		$red = max(0, min(255, $red + $change));
		$green = max(0, min(255, $green + $change));
		$blue  = max(0, min(255, $blue + $change));
	
		return '#'.dechex($red).dechex($green).dechex($blue);
	}

	// Useful functions ----------------------------------------------------------]

	public function format_bytes($bytes, $precision = 0) { 
	    $units = array('Bytes', 'Kb', 'Mb', 'Gb', 'Tb'); 
	
	    $bytes = max($bytes, 0); 
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	    $pow = min($pow, count($units) - 1); 
	
	    $bytes /= pow(1024, $pow);
	
	    return round($bytes, $precision) . ' ' . $units[$pow]; 
	} 

	public function insert_svg_from_file($path, $name, $preserve_ratio = false) {
		
		$filepath = sprintf('%1$s/images/%2$s.svg', untrailingslashit($path), $name);
		if(!file_exists($filepath)) return '';
		
		$svg = file_get_contents($filepath);
		if($preserve_ratio && stripos($svg, 'preserveAspectRatio') === false) $svg = preg_replace('/<svg([^>]+)>/i', '<svg${1} preserveAspectRatio="xMidYMin slice">', $svg);
		
		return $svg;	
	}

	public function get_slug($post_id = null) {
		return basename(get_permalink($post_id));
	}

	public function post_id_from_slug($slug, $post_type = 'post') {
	
		$args = array(
			'name' => $slug,
			'post_type' => $post_type,
			'posts_per_page' => -1, 
			'cache_results' => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'orderby' => 'ID',
		);
	
		$posts = get_posts($args);
		return empty($posts) ? null : $posts[0]->ID;
	}

	public function get_top_ancestor($post_id = null) {
		
		if(empty($post_id)) $post_id = get_the_ID();
		$parents = get_post_ancestors($post_id);
		$parent_id = ($parents) ? $parents[count($parents)-1] : null;  		// Get the top Level page->ID count base 1, array base 0 so -1
		return empty($parent_id) ? $post_id : $parent_id;
	}

	public function get_top_ancestor_slug($post_id = null) { return $this->get_slug($this->get_top_ancestor()); }
	
	public function get_closing_tag_from_open($html) {
	    $opened_tag = preg_match('#<(?!meta|img|br|hr|input\b)\b([a-z]+)#iU', $html, $tags) ? $tags[1] : '';
	    return empty($opened_tag) ? '' : sprintf('</%1$s>', $opened_tag);
	}

	public function merge_classes($classes, $implode = true) {
	
		if(!is_array($classes))	 $classes = preg_split('/[\s,]+/', $classes);
		$classes = array_map('trim', $classes);
	
		return $implode ? implode(' ', array_filter($classes)) : $classes;
	}	

	public function remove_classes($classes, $remove = [], $implode = true) {

		$classes = $this->merge_classes($classes, false);
		
		foreach($remove as $test) if(in_array($test, $classes)) unset($classes[array_search($test, $classes)]);
		
		return $implode ? $this->merge_classes($classes) : $classes;
	}

	public function int_in_range($intval, $min, $max) {

		$intval = filter_var($intval, 
		    FILTER_VALIDATE_INT, 
		    array(
		        'options' => array(
		            'min_range' => $min, 
		            'max_range' => $max
		        )
		    )
		);
		
		return $intval === false ? $min : $intval;
	}

	public function blank_data_uri_img() {
		return 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
	}

	public function translit($string) {
	    $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
	    $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
	    return str_replace($rus, $lat, $string);
	}

	public function add_body_class($my_classes) {
		add_filter('body_class', function($classes) use ($my_classes) {
			$classes[] = $my_classes;
			return $classes;
		});
	}

	public function add_admin_body_class($my_classes) {
		add_filter('admin_body_class', function($classes) use ($my_classes) {
		    $classes .= ' ' . $my_classes;
		    return $classes;
		});
	}

	// Title, Content, Excerpt functions ------------------------------------------]

	public function close_tags($html) {
	    preg_match_all('#<(?!meta|img|br|hr|input\b)\b([a-z]+)(?: .*)?(?<![/|/ ])>#iU', $html, $matches);
	    $openedtags = $matches[1];
	    preg_match_all('#</([a-z]+)>#iU', $html, $matches);
	    $closedtags = $matches[1];
	    $opened_count = count($openedtags);
	    if(count($closedtags) == $opened_count) return trim($html);
	
	    $openedtags = array_reverse($openedtags);
	    for($i=0; $i < $opened_count; $i++) {
	        if(!in_array($openedtags[$i], $closedtags)) {
	            $html .= '</'.$openedtags[$i].'>';
	        } else {
	            unset($closedtags[array_search($openedtags[$i], $closedtags)]);
	        }
	    }
	    return trim(preg_replace('/<p>\s*<\/p>/i', '', $html));
	} 
	
	public function remove_empty_p($html) {
		
		$html = preg_replace( array(															// clean up p tags around block elements
			'#<p>\s*<(ul|ol|div|aside|section|article|header|footer)#i',
			'#</(ul|ol|div|aside|section|article|header|footer)>\s*</p>#i',
			'#</(ul|ol|div|aside|section|article|header|footer)>\s*<br ?/?>#i',
			'#<(ul|ol|div|aside|section|article|header|footer)([^>]+?)>\s*</p>#i',
			'#<p>\s*</(ul|ol|div|aside|section|article|header|footer)#i',
		), array(
			'<$1',
			'</$1>',
			'</$1>',
			'<$1$2>',
			'</$1',
		), $html);
	
		$html = preg_replace('#<p>(\s|&nbsp;)*(<br\s*/*>)*(\s|&nbsp;)*</p>#i', '', $html);			// remove  <p>&nbsp;<br>&nbsp;</p>
		$html = preg_replace('#<p>(\s|&nbsp;)*(<br\s*/*>)(\s|&nbsp;)*#i', '<p>', $html);				// replace  <p>&nbsp;<br>&nbsp;    for  <p>
		$html = preg_replace('#(\s|&nbsp;)*(<br\s*/*>)(\s|&nbsp;)*</p>#i', '</p>', $html);			// replace  &nbsp;<br>&nbsp;</p>  for  </p>
		return $html;
	}
	
	public function remove_p_on_images($content) {
	
		$content = preg_replace('/<p>\s*(<a[^>]+>)?[^<]*(<img[^>]+?>)[^<]*(<\/a>)?[^<]*<\/p>/i', '${1}${2}${3}', $content);			// remove p around single image
		$content = preg_replace('/<p>\s*((?:(?:<a[^>]+>)?[^<]*(?:<img[^>]+?>)[^<]*(?:<\/a>)?)+)[^<]*<\/p>/i', '${1}', $content);		// remove p around group of images
	
		return $content;
	}
	
	public function fix_content($content) {
		$replace_tags_from_to = array (
			'<br />' => '',
			"<br />\n" => '',
		);
		
		$fixed = preg_replace('/^\s|\s$/', '', strtr(trim($content), $replace_tags_from_to));
		return trim($fixed);
	}
	
	public function get_excerpt($post_id = null, $amount = 270, $force_from_content = false) {
		global $post;
		
		if(is_null($post_id)) $post_id = $post->ID;
	
		if(!$force_from_content && has_excerpt($post_id)) {
			$raw_excerpt = apply_filters('the_excerpt', get_post_field('post_excerpt', $post_id));
		} else {
			if(!empty($post_id) || is_null($post)) $post = get_post($post_id);
			
			$raw_excerpt = empty($post->post_content) ? '' : $post->post_content;
		}
			
        $raw_excerpt = strip_shortcodes($raw_excerpt);
        $raw_excerpt = apply_filters('the_content', $raw_excerpt);
        $raw_excerpt = preg_replace('/\s*\[[^\]]+?\]/i', '', $raw_excerpt); 				// remove javascript text translations
          $raw_excerpt = preg_replace('/^(?:<p>\s*<\/p>\s*)?<h.?[^<]+<\/h.?>/i', '', $raw_excerpt); 		// remove first <h*> tag if text starts with it
        $raw_excerpt = strip_tags($raw_excerpt);
        $raw_excerpt = str_replace('&#8230;', '&.', $raw_excerpt);							// replace HTML '...' (&#8230;)  with '&.' - and restore later 
        $tokens = array();
        $count = 0;
        $post_excerpt = '';
        
 		preg_match_all('/[^.!?\s][^.!?]*(?:[.!?](?![\'\"]?\s|$)[^.!?]*)*[.!?]?[\'\"]?(?=\s|$)/', $raw_excerpt, $tokens); // split in sentences

        foreach($tokens[0] as $token) { 

            if($count > $amount) break;
            
			$post_excerpt .= $token.' ';
            $count = strlen($post_excerpt);
        }
        
        $post_excerpt = trim(str_replace('&.', '&#8230;', $post_excerpt));			// restore replaced &#8230;
	
		return $this->fix_content($post_excerpt);
	}

	public function get_svgcurve($look = 'upright', $height = 100, $class = '', $html_id = '') {
		
		$height = intval(str_replace('px', '', $height));
		
		switch($look) {
			
			case 'downleftinverse':
				$path = sprintf('M100,0 L100,%1$s L0,%1$s L0,0 L100,0 z M100,0 L0,0 C20,135 50,0 100,0 z', $height);
				break;
		    
		    case 'upright': 	
		    	$path = sprintf('M0 %1$s C 50 0 80 -%2$s 100 %1$s Z', $height, intval($height/3));
				break;
		    
		    case 'upleft': 	
		    	$path = sprintf('M0 %1$s C 20 -%2$s 30 0 100 %1$s Z', $height, intval($height/3));
				break;
		    
		    case 'downleft': 	
		    	$path = sprintf('M0 0 C 20 %1$s 50 0 100 0 Z', $height * 2);
				break;
		    
		    case 'downright': 	
		    	$path = sprintf('M0 0 C 50 0 80 %1$s 100 0 Z', $height * 2);
				break;
		    
		    case 'lessdownleft': 	
		    	$path = sprintf('M0 0 C 20 %1$s 50 0 100 0 Z', intval($height * 1.3));
				break;
		    
		    case 'lessdownright': 	
		    	$path = sprintf('M0 0 C 50 0 80 %1$s 100 0 Z', intval($height * 1.3));
				break;
	
		    default;
		    	$path = sprintf('M0 %1$s C 50 0 80 -%2$s 100 %1$s Z', $height, intval($height/3));
				break;
		}	
		
		$curve = sprintf(
			'<div %2$s class="_curve %5$s" style="height: %4$spx">
				<svg xmlns="http://www.w3.org/2000/svg" version="1.1" width="100%%" height="%1$spx" 
					style="position:relative; padding:0; margin:0; fill: currentColor; stroke: currentColor; top:0;" 
					viewBox="0 0 100 %1$s" preserveAspectRatio="none">
					<path d="%3$s"></path>
				 </svg>
			 </div>',
			$height,
			empty($html_id) ? '' : sprintf('id="%1$s"', $html_id),
			$path,
			($height - 1),
			$class
		);
		return $curve;
	}

	// Color, Background, Thumbnail, Attachment functions ------------------------]
	
	public function get_post_gallery($post_id = null) {
		
		// Replace of WP 'get_post_gallery' to avoid multiple resolving of shortcodes
		
		if(!$post = get_post($post_id)) return [];
		if(!has_shortcode($post->post_content, 'gallery')) return [];
	
		$galleries = [];
		if(preg_match_all('/'.get_shortcode_regex().'/s', $post->post_content, $matches, PREG_SET_ORDER)) {
			foreach($matches as $shortcode) {
				if('gallery' === $shortcode[2]) {
					
					$shortcode_attrs = shortcode_parse_atts($shortcode[3]);
					if(!is_array($shortcode_attrs)) $shortcode_attrs = [];
	
					$galleries[] = $shortcode_attrs;
				}
			}
		}
	
		return isset($galleries[0]) ? $galleries[0] : [];
	}

// !! change for plugin
	public function get_dominant_by_attachment_id($post_or_attachment_id) {
		return function_exists('zu_get_dominant_by_attachment_id') ? zu_get_dominant_by_attachment_id($post_or_attachment_id) : 'black';
	}

// !! change for plugin
	public function get_dominant_by_post_id($post_or_attachment_id) {
		return function_exists('zu_get_dominant_by_post_id') ? zu_get_dominant_by_post_id($post_or_attachment_id) : 'black';
	}

	public function get_attachment_id($post_or_attachment_id = null) {
			if(get_post_type($post_or_attachment_id) == 'attachment') return $post_or_attachment_id;
			else if(has_post_thumbnail($post_or_attachment_id)) return get_post_thumbnail_id($post_or_attachment_id);
			return null; 
		}
		
	public function get_featured_attachment_id($post_id = null) {
		// if there is no featured_attachment - use it from $this->random_attachment_id
	
		$attachment_id = get_post_thumbnail_id($post_id);
		$attachment_id = (empty($attachment_id) && !empty($this->random_attachment_id)) ? $this->random_attachment_id : $attachment_id;
		return $attachment_id;
	}
	
	public function set_random_featured_attachment_id($post_id = null, $gallery = null) {
	
		$gallery = empty($gallery) ? $this->get_post_gallery($post_id) : $gallery;
		$ids = empty($gallery) ? [] : wp_parse_id_list($gallery['ids']);
	
		$this->random_attachment_id = empty($ids) ? null : (int)$ids[rand(0, count($ids) - 1)];
		
		return $this->random_attachment_id;
	}
	
	public function get_post_thumbnail($post_id = null, $size = 'full') {
		if(has_post_thumbnail($post_id)) {
			$imgsrc = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), $size);
			return $imgsrc[0];
		} else
			return '';
	}
	
	public function get_background_image($image_url = null, $post_id = null, $with_quote = true) {
		
		if(is_null($image_url)) $image_url = $this->get_post_thumbnail($post_id);
		$image_bg = empty($image_url) ? '' : sprintf('background-image:url(%2$s%1$s%2$s);', $image_url,  $with_quote ? '&quot;' : '"');
		return $image_bg;
	}
	
	public function get_background_color($post_or_attachment_id = null) {
		
		if(get_post_type($post_or_attachment_id) == 'attachment') $color = $this->get_dominant_by_attachment_id($post_or_attachment_id);
		else $color = $this->get_dominant_by_post_id($post_or_attachment_id);
		$color_bg = empty($color) ? '' : 'background-color:'.$color.';';
		return $color_bg;
	}

	// Inline styles to the footer if needed -------------------------------------]

	public function add_advanced_style($name, $style) {
		if(!empty($name)) {
			$this->advanced_style[] = ['name' => $name, 'style' => $style];
		}
	}

	public function add_admin_style($name, $style) {
		if(!empty($name)) {
			$this->admin_style[] = ['name' => $name, 'style' => $style];
		}
	}

	public function add_fonts_style($font_list, $dir, $uri) {
		if(is_array($font_list)) $this->fonts['list'] = $font_list;
		if(!empty($dir)) $this->fonts['dir'] = $dir;
		if(!empty($uri)) $this->fonts['uri'] = $uri;
		
		$this->fonts = array_merge(['list' => [], 'dir' => '', 'uri' => ''], $this->fonts);
	}	
	
	public function add_style_from_file($css_file) { 
	
		if(!file_exists($css_file)) return;
		$style = file_get_contents($css_file);
	
		if(!empty($style)) $this->add_advanced_style('_responsive', $style); 
	}
	
	public function maybe_add_advanced_styles() {

		$advanced_style = '';
	
		foreach($this->admin_style as $style_data) {
			if(stripos($style_data['name'], '_responsive') !== false) $advanced_style .= $style_data['style'];  	// if '_responsive' then insert CSS without processing
			else $advanced_style .= sprintf('%1$s { %2$s}', $style_data['name'], $style_data['style']);
		}	
	
		foreach($this->advanced_style as $style_data) {
			if(stripos($style_data['name'], '_responsive') !== false) $advanced_style .= $style_data['style'];  	// if '_responsive' then insert CSS without processing
			else $advanced_style .= sprintf('%1$s { %2$s}', $style_data['name'], $style_data['style']);
		}	
	
		if(!empty($this->fonts)) {
			foreach($this->fonts['list'] as $page => $file) {
				if(is_page($page)) { 
					$filename = $this->fonts['dir'].$file;
					if(file_exists($filename)) $advanced_style .= preg_replace('/%%path%%/i', $this->fonts['uri'], file_get_contents($filename));
				}
			}	
		}
		
		if(!empty(trim($advanced_style))) printf('<style type="text/css" id="zu-advanced-styles">%1$s</style>', $this->minify_css($advanced_style));
	}
}

class ZU_PlusRepeaters {

	private $root;
	private $folder;

	function __construct($root = null, $folder = null) {
		
		$this->root = empty($root) ? zuplus_get_my_dir() : untrailingslashit($root);
		$this->folder = empty($folder) ? 'repeaters' : str_replace('/', '', $folder);
	}

	// Repeaters functions -------------------------------------------------------]

	private function get_repeater_path($name) {
		return sprintf('%1$s/%2$s/%3$s.php', $this->root, $this->folder, $name);
	}
	
	public function get_repeater_output($repeater, $args = [], $classes = '') {
	
		$include = $this->get_repeater_path($repeater);      		
		if(!file_exists($include)) $include = $this->get_repeater_path('default');  
		if(!file_exists($include)) return '';
	
		$_template = $repeater;
		$_classes = $classes;
		$_args = $args;
		
		ob_start();
		include($include); 						// Include repeater template
		$output = ob_get_contents();
		ob_end_clean();
	
		return $output;
	}	
}

// Common Interface to helpers ------------------------------------------------]

if(!function_exists('zu')) {
	function zu() { 
		return ZU_PlusFunctions::instance(); 
	}
}