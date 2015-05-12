<?php
/**
 * @package   Structured Content
 * @author    Phillihp Harmon <phillihpz@gmail.com>
 * @license   GPL-2.0+
 * @link      http://ctacorp.com
 * @copyright 2015 CTACorp
 */

class Structured_Content {

	/**
	 * Plugin version
	 * @since   1.0.0
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Plugin slug
	 * @since    1.0.0
	 * @var      string
	 */
	protected $plugin_slug = 'structured-content';

	/**
	 * Instance of this class.
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;
    
    /**
     * Post Type definitions.
     * 
     * More post_types can be added, but at some point, we will want to extract this out into the Admin page so that
     * Structured Content can be placed on any post type, rather than to just force all post types.                    
     *     
     */         
    protected static $post_types = array('post', 'page');
    
    /**
     * Dynamic Mappings
     * 
     * Determine what is native to Wordpress and dynamically map out the fields between Wordpress and the Structured
     * Content definition set. Some will likely need to be custom, such as Tags -> Categories. This has not yet been
     * considered.
     * 
     * ::functionName       - This will call a user defined function                      
     *     
     */         
    protected static $dynamicMappings = array(
         'ShortTitle'=>"::findShortTitle"
        ,'FullTitle'=>"post_title"
        ,'SectionBody'=>"::processContent"
        ,'DatePosted'=>"post_date"
        ,'DateFirstPublished'=>"post_date"
        ,'DateLastModified'=>"post_modified"
        ,'DatePublished'=>"post_modified"
        ,'URL'=>"::findURL"
        ,'ArticleSection'=>"::findArticleSection"
        ,'SourceOrganization'=>"::findSourceOrganization"
        //,'ShortDescription'=>"::findShortDescription"     // We took this out because it is not required.
        ,'DetailedDescription'=>"::findDetailedDescription"
        ,'ArticleType'=>"::findArticleType"
        ,'InLanguage'=>"::findLocale" // Defaulting to English
        ,'Topics'=>"::findTopics"
        ,'Author'=>"::findAuthor"
        ,'Video'=>"::findVideo"
        ,'Audio'=>"::findAudio"
        ,'Image'=>"::findImages"
        ,'File'=>"::findFiles"
        //,'ArticleType'=>"::findPostType"
    );
    
    protected static $inputArgs = array(
         'version'          // (string)  Version of the element
        ,'lastModified'     // (date)    Last time element was modified
        ,'required'         // (boolean) Is this field required to allow a submissions
        ,'size'             // (integer) The size of the input field (this needs to be more dynamic)
        ,'type'             // (enum)    ['', URL, Date, Language, TextArea] Data Type
        ,'default'          // (boolean) Default value if nothing is typed in
        ,'multiple'         // (boolean) Allow multiple
        ,'listing'          // (boolean) Show in Listing
        ,'alt'              // (string)  Description of Input
        ,'allowOverride'    // (boolean) Allow users to override the default data
    );
    
    protected static $shortcodeArgs = array(
         'site'
        ,'article'
    );
    
    protected static $excludeFiles = array("jpg", "jpeg", "png", "gif", "mp3", "mov", "avi", "wmv", "midi", "mid", "ico");

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		add_action( '@TODO', array( $this, 'action_method_name' ) );
		add_filter( '@TODO', array( $this, 'filter_method_name' ) );
        
        // Meta Box Add's for Custom Content
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
        
        // Shortcode
        // add_shortcode('oasc', array($this, 'oasc_shortcode'));
        add_shortcode('oasc_help', array($this, 'oasc_help_shortcode'));
	}

	/**
	 * Return the plugin slug.
	 * @since    1.0.0
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 * @since    1.0.0
	 * @param    boolean 
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 * @since    1.0.0
	 * @param    boolean   
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 * @since    1.0.0
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 * @since    1.0.0
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
        wp_enqueue_style($this->plugin_slug.'-plugin-styles', plugins_url('assets/css/public.css', __FILE__), array(), self::VERSION);
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script($this->plugin_slug.'-plugin-script', plugins_url('assets/js/public.js', __FILE__), array('jquery'), self::VERSION);
	}
    
    /**
	 * Register and enqueue public-facing style sheet.
	 * @since    1.0.0
	 */
	public function admin_enqueue_styles() {
        wp_enqueue_style($this->plugin_slug.'-admin-styles', plugins_url('assets/css/admin.css', __FILE__), array(), self::VERSION);
        wp_enqueue_style($this->plugin_slug.'-datepicker-css', plugins_url()."/wp-structured-content/assets/jquery.ui.datepicker/ui.datepicker.css");
        wp_enqueue_style($this->plugin_slug.'-datepicker-smooth-css', plugins_url()."/wp-structured-content/assets/jquery.ui.datepicker/smoothness/jquery-ui-1.8.23.custom.css");
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 * @since    1.0.0
	 */
	public function admin_enqueue_scripts() {
        wp_enqueue_script($this->plugin_slug.'-admin-script', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), self::VERSION);
        wp_enqueue_script($this->plugin_slug.'-datepicker-js', plugins_url()."/wp-structured-content/assets/jquery.ui.datepicker/ui.datepicker.min.js");
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    1.0.0
	 */
	public function action_method_name() {
		// @TODO: Define your action hook callback here
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    1.0.0
	 */
	public function filter_method_name() {
		// @TODO: Define your filter hook callback here
	}
    
    /**
	 * Adds the meta box container.
	 */
	public function add_meta_box( $post_type ) {
        //limit meta box to certain post types
        if ( in_array( $post_type, self::$post_types )) {
    		add_meta_box(
    			'oasc_meta_box'
    			,__( 'Structured Content', 'myplugin_textdomain' )
    			,array( $this, 'render_meta_box_content' )
    			,$post_type
    			,'advanced'
    			,'high'
    		);
        }
	}
    
    public function oasc_shortcode($atts) {
        //http://sites.usa.local/?feed=article&article=222
        $atts = $this->clean_attrs($atts);
        $site = "";
        $feed = "";
        $out  = "";
        
        if($atts['site'] != "")     $site = $atts['site'];
        if($atts['article'] != "")  $feed = "article";
        
        if($site && $feed) {
            $xmlLink = $atts['site']."/feeds/?feed=$feed&$feed={$atts['article']}";
            
            $contents = file_get_contents($xmlLink);
            $xmlArray = simplexml_load_string($contents);
            
            $out = $xmlArray->article->ArticleBody->ArticleSection->SectionBody;
        }
        return $out;
    }
    
    public function oasc_help_shortcode($atts) {
        global $user_ID;
    }

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */
        // Check if our nonce is set.
		if ( ! isset( $_POST['oasc_field_nonce'] ) )
			return $post_id;
        
		$nonce = $_POST['oasc_field_nonce'];
        
		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'render_meta_box_content' ) ) {
			return $post_id;
        }
        
		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;
        
		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
        
		/* OK, its safe for us to save the data now. */
        $definition = self::getXMLDefinition();
        foreach($definition->Article->children() as $key => $val) {
            $tempVal = "";
            if(!array_key_exists($key, self::$dynamicMappings) || $val['allowOverride'] == "true") {
                if(is_array($_POST['OASC_'.$key]))
                    $tempVal = array_map( 'sanitize_text_field', $_POST['OASC_'.$key] );
                else
                    $tempVal = sanitize_text_field( $_POST['OASC_'.$key] );
                if(!$val->count()) {
                    update_post_meta( $post_id, 'OASC_'.$key, $tempVal );
                } else {
                    foreach($val->children() as $ckey => $cval) {
                        if(!array_key_exists($ckey, self::$dynamicMappings) || $cval['allowOverride'] == "true") {
                            if(is_array($_POST['OASC_'.$ckey]))
                                $tempVal = array_map( 'sanitize_text_field', $_POST['OASC_'.$ckey] );
                            else
                                $tempVal = sanitize_text_field( $_POST['OASC_'.$ckey] );
                            update_post_meta( $post_id, 'OASC_'.$ckey, $tempVal );
                        }
                    }
                }
            }
        }
	}


	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {
        $meta = get_post_meta($post->ID);
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'render_meta_box_content', 'oasc_field_nonce', true );		
        
        $definition = self::getXMLDefinition();
        echo "<div><a href='".get_site_url()."/feed/?feed=article&article=".$post->ID."' target='_blank'>XML</a> - <a href='".get_site_url()."/feed/?feed=article&format=json&article=".$post->ID."' target='_blank'>JSON</a></div>";
        echo "<script>jQuery(document).ready(function(\$){\$('.datepicker').datepicker();});</script>";
        echo "<table class='oasc_table'>";
        foreach($definition->Article->children() as $key => $val) {
            $val = $this->clean_attrs($val);
            if(!array_key_exists($key, self::$dynamicMappings) || $val['allowOverride'] == "true") {
                if(!$val->count()) {
                    $this->render_input_element($key, $val, $post, $meta);
                } else {
                    
                    foreach($val->children() as $ckey => $cval) {
                        $cval = $this->clean_attrs($cval);
                        if(!array_key_exists($ckey, self::$dynamicMappings) || $cval['allowOverride'] == "true") {
                            $this->render_input_element($ckey, $cval, $post, $meta);
                        }
                    }
                }
                echo "</div>";
            }
        }
        echo "</table>";
	}
    
    public function render_input_element($key, $val, $post, $meta) {
        $keyName = str_replace('U R L', 'URL', ltrim(preg_replace('/[A-Z]/', ' $0', $key)));
        $pureKey = $key;
        $key = "OASC_$key";
        if(!$val['size'] && $val['type'] == "URL") $val['size'] = 50;
        
        if($val['multiple'] == "true")
            $postData = get_post_meta($post->ID, $key, false); 
        else
            $postData = get_post_meta($post->ID, $key, true);
            
        if(($postData == "" || count($postData) == 0) && $val['allowOverride'] == "true") {
            
            if(strpos(self::$dynamicMappings[$pureKey], "::") !== false) {
                $fName = str_replace("::", "", self::$dynamicMappings[$pureKey]);
                $postData = call_user_func(array('Structured_Content', $fName), $post, $meta);
                $postData = str_replace("<![CDATA[", "", str_replace("]]>", "", $postData));
            } else {
                $postData = $post->{self::$dynamicMappings[$key]};
            }
        }
        
        echo "<tr>";
        echo "<td ><label for='$key'>$keyName</label> ".($val['required'] == "true" ? "<span class='required'>*</span>" : "")."</td>";
        echo "<td>";
        
        switch($val['type']) {
            case 'TextArea':
                echo "<textarea id='$key' name='$key' rows='4' cols='70' title=\"".$val['alt']."\">$postData</textarea>";
                break;
            case 'Date':
                echo "<input id='$key' name='$key' type='text' class='datepicker' size='10' value='$postData' title=\"".$val['alt']."\" />";
                break;
            default:
                if($val['multiple'] == "true" && is_array($postData[0])) {
                    foreach($postData[0] as $pd) {
                        echo $this->print_input($key, $val, $pd)."<br />";
                    }
                } else {
                    echo $this->print_input($key, $val, $postData);
                }
                break;
        }
        echo "</td>";
        echo "</tr>";
    }
    
    public function print_input($key, $val, $postData) {
        $postData = is_array($postData) ? "" : $postData;
        return "<input id='$key' name='$key".($val['multiple'] == "true" ? "[]" : "")."' type='text' size='{$val['size']}' value='$postData' title=\"".$val['alt']."\" />".
                ($val['multiple'] == "true" ? "<img class='multiple' src='http://maps.simcoe.ca/TourismDataList/images/plusButton.gif' />" : "").
                ($val['type'] == "Language" ? "<!--<img class='language' src='http://www.siass-urf.es/img/language_icon.png' />-->" : "");
    }
    
    /**
     *  We could probably combine these two functions to reduce the duplicate code
     *  
     *  clean_shortcodeAttrs
     *  clean_attrs                   
     */    
    public function clean_shortcodeAttrs($val) {
        foreach(self::$shortcodeArgs as $el) $val[$el] = isset($val[$el]) ? $val[$el] : "";
        return $val;
    }
    
    public function clean_attrs($val) {
        foreach(self::$inputArgs as $el) $val[$el] = isset($val[$el]) ? $val[$el] : "";
        return $val;
    }
    
    /**
     *  Protected Helper Functions for Data Type Discovery
     */
    protected static function findShortTitle($page, $meta) {
        return substr($post_title, 0, 32);
    }
    protected static function findArticleType($page, $meta) {
        return "Article";
    }
    protected static function findURL($page, $meta) {
        return get_page_link($page->ID);
    }
    protected static function findLocale($page, $meta) {
        //var_dump($meta);
        //return "here i am: {$page->ID} - ".$meta['_locale'];
        return "en";
    }
    protected static function findTopics($page, $meta) {
        $out = array();
        $tags_array = get_the_tags($page->ID);
        foreach($tags_array as $tag) {
            array_push($out, $tag->name);
        }
        return $out;
    }
    protected static function findArticleSection($page, $meta) {
        return "";
    }
    protected static function findSourceOrganization($page, $meta) {
        return get_bloginfo('name');
    }
    protected static function findShortDescription($page, $meta) {
        $content = apply_filters('the_content', $page->post_content);
        $content = strip_tags($content);
        $content = substr($content, 0, 64);
        return "<![CDATA[".$content."...]]>";
    }
    protected static function findDetailedDescription($page, $meta) {
        $content = apply_filters('the_content', $page->post_content);
        $content = strip_tags($content);
        $content = substr($content, 0, 300);
        return "<![CDATA[".$content."...]]>";
    }
    protected static function findAuthor($page, $meta) {
        $out = array();
        
        if ( function_exists( 'coauthors' ) ) {
            $authors = get_coauthors($page->ID);
            foreach($authors as $author) {
                array_push($out, $author->data->display_name);
            }
        } else {
            $usrdata = get_userdata($page->post_author);
            array_push($out, $usrdata->display_name);
        }
        
        return $out;
    }
    protected static function processContent($page, $meta) {
        $content = apply_filters('the_content', $page->post_content);
        $content = self::fixRelativePaths($content, get_site_url());
        return "<![CDATA[".$content."]]>";
    }
    protected static function findVideo($page, $meta) {
        // HTML5 Videos
        $dom = new DOMDocument;
        $dom->loadHTML($page->post_content);
        $dom->preserveWhiteSpace = false;
        $videos = $dom->getElementsByTagName('video');
        $out = array();
        foreach($videos as $vid) {
            $sources = $vid->getElementsByTagName('source');
            foreach($sources as $src) {
                $url = $src->getAttribute('src');
                if(strpos($url, "http") === false) $url = get_site_url()."/".$url;
                array_push($out, $url);
            }
        }
        
        // Youtube Videos
        preg_match('#https?://w?w?w?.?youtube.com/watch\?v=([A-Za-z0-9\-_]+)#s', $page->post_content, $matches);
        
        foreach($matches as $match) {
            if(strpos($match, "http") !== false) {
                array_push($out, $match);
            }
        }
        
        // All MP4's, WMV's, OGV's, AVI's
        
        
        return $out;
    }
    protected static function findAudio($page, $meta) {
        $dom = new DOMDocument;
        $dom->loadHTML($page->post_content);
        $dom->preserveWhiteSpace = false;
        $audio = $dom->getElementsByTagName('audio');
        $out = array();
        foreach($audio as $aud) {
            $sources = $aud->getElementsByTagName('source');
            foreach($sources as $src) {
                $url = $src->getAttribute('src');
                if(strpos($url, "http") == -1) $url = get_site_url()."/".$url;
                array_push($out, $url);
            }
        }
        return $out;
    }
    protected static function findImages($page, $meta) {
        $dom = new DOMDocument;
        $dom->loadHTML($page->post_content);
        $dom->preserveWhiteSpace = false;
        $images = $dom->getElementsByTagName('img');
        $out = array();
        foreach($images as $img) {
            $url = $img->getAttribute('src');
            if(strpos($url, "http") === false) $url = get_site_url()."/".$url;
            array_push($out, $url);
        }
        return $out;
    }
    protected static function findFiles($page, $meta) {
        $dom = new DOMDocument;
        $dom->loadHTML($page->post_content);
        $dom->preserveWhiteSpace = false;
        $links = $dom->getElementsByTagName('a');
        $out = array();
        
        $fileTypes = array_diff(explode(" ", get_site_option('upload_filetypes')), self::$excludeFiles);
        
        foreach($links as $link) {
            $url = $link->getAttribute('href');
            
            if(strpos($url, "http") === false) $url = get_site_url()."/".$url;
            $ext = end(explode('.', $url));
            if(in_array(strtolower($ext), $fileTypes)) {
                array_push($out, $url);
            }
        }
        return $out;
    }
    protected static function fixRelativePaths($html, $site) {
        //$html = strip_shortcodes($html);
        $html = str_replace("src='/", "src='$site/", $html);
        $html = str_replace("src=\"/", "src=\"$site/", $html);
        $html = str_replace("href='/", "href='$site/", $html);
        $html = str_replace("href=\"/", "href=\"$site/", $html);
        return $html;
    }
    /**
     *  END
     */
    
    public static function getXMLDefinition() {
        try {
            $contents = file_get_contents(get_option('namespace-url'));
            $xmlArray = array();
            if($contents) {
                $xmlArray = simplexml_load_string($contents);
            }
        } catch(Exception $e) {
            echo "Namespace has not been defined";
        }
        return $xmlArray;
    }
    
    public static function stream_article_list() {
        ob_clean();
        ob_start();
        if(!$definition) $definition = self::getXMLDefinition();
        if(!$obj)        $obj = $definition->Article->children();

        include "feeds/xml.php";
        
        global $post;
        $parents = array();
        
        
        
        $params=array(
             'post_type' => 'page'
            ,'post_status' => 'publish'
            ,'hierarchical' => 1
            ,'exclude_tree' => ''
            ,'showposts'=>-1
            ,'order'=>"ASC"
            ,'orderby'=>"menu_order, post_title"
            ,'tag'=>''
        );
        $query = new WP_Query;
        $pages = $query->query($params);
        //$pages = get_pages($params);
        
        echo "<main>\n";
        foreach ( $pages as $page ) {
            $l = array_search($page->post_parent, $parents);
            if($l === false)
                array_push($parents, $page->post_parent);
            else if($l != count($parents) - 1) {
                for($i = $l + 1; $i < count($parents); $i++)
                    $nothing = array_pop($parents);
            }
            
            
            $meta = get_post_meta($page->ID);
            echo "<article id='".$page->ID."' parent='".$page->post_parent."' depth='".(count($parents) - 1)."'>\n";
            self::stream_build($definition, $obj, $page, $meta, "\t", true);
            echo "</article>\n";
        }
        echo "</main>";
        $contents = ob_get_contents();
        ob_end_clean();
        
        if($_GET['format'] == "json") {
            $xmlArray = simplexml_load_string($contents);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($xmlArray); //JSON_PRETTY_PRINT
        } else {
            header ("Content-Type:text/xml; charset=utf-8");
            echo $contents;
        }
    }
    
    public static function stream_article() {
        ob_clean();
        ob_start();
        if(!$definition) $definition = self::getXMLDefinition();
        if(!$obj)        $obj = $definition->Article->children();

        include "feeds/xml.php";
        $pid = (int)$_GET['article'];
        
        global $post;
        $parent = array();
        
        $page = get_page($pid);
        echo "<main>\n";
        
        $meta = get_post_meta($page->ID);
        echo "<article id='".$page->ID."' parent='".$page->post_parent."'>\n";
        self::stream_build($definition, $obj, $page, $meta, "\t", false);
        echo "</article>\n";
        
        echo "</main>";
        $contents = ob_get_contents();
        ob_end_clean();
        
        if($_GET['format'] == "json") {
            $xmlArray = simplexml_load_string($contents); // there might be an issue with the section body
            header('Content-Type: application/json; charset=utf-8');
            $test = array('something'=>array('1'=>"okay", '2', "a lot"));
            echo json_encode($xmlArray);
        } else {
            header ("Content-Type:text/xml; charset=utf-8");
            echo $contents;
        }
    }
    
    public static function stream_build($definition, $obj, $page, $meta, $tab, $listing = false) {
        foreach($obj as $key => $val) {
            if(($val['listing'] == "true" && $listing) || !$listing) {
                if(!$val->count()) {
                    if(array_key_exists($key, self::$dynamicMappings) && $val['allowOverride'] != "true") {
                        if(strpos(self::$dynamicMappings[$key], "::") > -1) {
                            $fName = str_replace("::", "", self::$dynamicMappings[$key]);
                            $data = call_user_func(array('Structured_Content', $fName), $page, $meta);
                            if(is_array($data)) {
                                foreach($data as $elem)
                                    echo "$tab<$key>".htmlspecialchars($elem)."</$key>\n";
                            } else {
                                echo "$tab<$key>".htmlspecialchars($data)."</$key>\n";
                            }
                        } else
                            echo "$tab<$key>".htmlspecialchars($page->{self::$dynamicMappings[$key]})."</$key>\n";
                    } else {
                        $okey = "OASC_".$key;
						if(isset($meta[$okey][0]))
							$meta[$okey] = (!@unserialize($meta[$okey][0]) ? $meta[$okey] : unserialize($meta[$okey][0]));
						
                        if(is_array($meta[$okey]) && count($meta[$okey] > 1))
                            foreach($meta[$okey] as $v)
                                echo (trim($v) != "" ? "$tab<$key>".htmlspecialchars($v)."</$key>\n" : "");
                        else if(is_array($meta[$okey])) {
                            echo "$tab<$key>".htmlspecialchars($meta[$okey][0])."</$key>\n";
                        } else
                            echo "$tab<$key>".htmlspecialchars($meta[$okey])."</$key>\n";
                    }
                } else {
                    echo "$tab<$key>\n";
                    self::stream_build($definition, $val->children(), $page, $meta, $tab."\t", $listing);
                    echo "$tab</$key>\n";
                }
            }
        }
    }
}
