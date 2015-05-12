<?php
class Structured_Content_Dialog {
    protected static $instance = null;
    
    private function __construct() {

		//add a button to the content editor, next to the media button
        //this button will show a popup that contains inline content
        //add_action('media_buttons_context', array($this, 'add_my_custom_button')); // BUTTON INCLUDE
        
        //add some content to the bottom of the page 
        //This will be shown in the inline modal
        add_action('admin_footer', array($this, 'add_inline_popup_content'));
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
    
    //action to add a custom button to the content editor
    public function add_my_custom_button($context) {
      
      //path to my icon
      $img = plugins_url()."/wp-structured-content/public/assets/images/oasc_button.jpg";
      
      //the id of the container I want to show in the popup
      $container_id = 'popup_container';
      
      //our popup's title
      $title = 'Structured Content - Shared Content';
    
      //append the icon
      $context .= "<a class='thickbox' title='{$title}'
        href='#TB_inline?width=800&height=550&inlineId={$container_id}'>
        <img src='{$img}' /></a>";
      
      return $context;
    }
    public function add_inline_popup_content() {
        require_once("views/dialog.php");
    }
    
    /*
     * Act as a Server to Server tunnel to retrieve Only Approved RSS feeds
     */
    public function get_data() {
        
        switch($_POST['type']) {
            case "article_list":
                $query_url = $_POST['url']."?feed=article_list";
                break;
            case "article":
                $query_url = $_POST['url']."?feed=article";
                break;
        }
        $format = isset($_POST['format']) ? ($_POST['format'] == "json" ? "json" : "xml") : "";
        
        try {
            $contents = file_get_contents($query_url);
            $xmlArray = array();
            
            switch($format) {
                case "json":
                    $xmlArray = simplexml_load_string($contents);
                    $json = json_encode($xmlArray, JSON_PRETTY_PRINT);
                    echo $json;
                    break;
                case "xml": default:
                    echo $contents;
                    break;
            }
            $xmlArray;
        } catch(Exception $e) {
            echo "There was an error loading content.";
        }
        
        die();
    }
}
?>