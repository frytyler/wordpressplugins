<?php
/*
Plugin Name: (SP) Facebook Feed
Version: 1.0.1
Author: SP
Description: Import Facebook Page Posts.
*/
?>
<?php
if (!function_exists('add_action')) { header('Status: 403 Forbidden'); header('HTTP/1.1 403 Forbidden'); exit("This page can't be loaded outside of WordPress!"); }
global $wp_version;
if (version_compare($wp_version, "3.8.1", "<")) { exit("<b>Facebook Feed</b> requires WordPress 3.8.1 OR newer. <a href=\"http:/codex.wordpress.org/Upgrading_WordPress\">Please update!</a>"); }
if(!class_exists('SP_FacebookFeed')):
class SP_FacebookFeed extends WP_Widget
{

	/**
	 *	const vars
	 */
	const NAME = "SP_FacebookFeed";
	const TITLE = "(SP) Facebook Feed";
	const MENUTITLE = "Facebook Feed";
	const VERSION = "1.0.1";

	var $URI;
	var $DIR;
	var $WPDB_TABLE;
	var $OPTION_FEED_ACCOUNT;
	var $OPTION_THEME;
	var $TWIG;
	var $facebook_attachment_types;
	
	public function __construct( ) {
		global $wpdb;

		parent::__construct(
	 		self::NAME, // Base ID
			self::TITLE, // Name
			array( 'description' => __( self::TITLE, 'text_domain' ), ), // Args
			array(  )
		);

		$this->URI = plugin_dir_url( __FILE__ );
		$this->DIR = plugin_dir_path( __FILE__ );
		$this->WPDB_TABLE = $wpdb->prefix . "sp_facebookfeed";
		$this->OPTION_FEED_ACCOUNT = 'sp_facebookfeed_account';
		$this->OPTION_THEME = 'sp_facebookfeed_theme';
		$this->facebook_attachment_types = array("photo"=>"Photos"); //"video"=>"Videos","link"=>"Links"

		if ( defined( "SP_CORE_ACTIVE_FLAG" ) )
			$this->TWIG = sp_new_twig_engine($this->DIR.'templates');

		return;
	}
	
	/**
	 *	@func 			get_excerpt
	 *	@params 		<num:int>,<original_excerpt:string>
	 *	@returns 		<temp:string>
	 *	@Description 	Return a excerpt word limit
	 */
	public function get_excerpt($num,$original_excerpt) {
		$limit = $num;
		$original_excerpt = str_replace(array(chr(13),chr(10)),NULL,$original_excerpt);
		$excerpt = explode(' ', $original_excerpt);
		$new_excerpt = implode(" ",array_splice($excerpt,0,$limit));
		$new_excerpt = (substr($new_excerpt,-1) == ".") ? $new_excerpt : $new_excerpt."...";
		$temp = $new_excerpt;
		return $temp;
	}
	
	/**
	 *	@func 		wp_scripts
	 *	@params 	<void>
	 *	@returns 	<void>
	 */	
	function wp_scripts() { 
		if (($_REQUEST["page"] == "sp_facebookfeed")) { 
			?>
				<!-- SP_FacebookFeed Stylesheet //-->
				<style type="text/css">
				</style>
		    <?php 
		}
	}
	/**
	 *	@func 		add_pages
	 *	@params 	<void>
	 *	@returns 	<void>
	 */	
	function add_pages() {
		wp_enqueue_script('common'); wp_enqueue_script('wp-lists'); wp_enqueue_script('postbox');
		add_menu_page(self::TITLE, self::MENUTITLE, 8, 'sp_facebookfeed', array(&$this, 'display_dashboard'), $this->URI.'/sp.facebookfeed.png');
		add_action('admin_head', array(&$this, 'wp_scripts'));
	}
	/**
	 *	@func 		display_dashboard
	 *	@params 	<void>
	 *	@returns 	<void>
	 */	
	function display_dashboard( )
	{
		global $current_user;
		if(class_exists('SP_BLOCK')):
			$theme = $this->OPTION_THEME;
			$block = new SP_BLOCK();
			$block->add_tab('panel_admin_settings',__('Configuration Settings'), array(&$this,'panel_admin_settings') );
			$block->add_tab('panel_schedule',__('Schedule'), array(&$this,'panel_schedule')  );
			$block->draw_block(self::MENUTITLE,'V.'.self::VERSION,'Settings to pull in a facebook feed');
		else:
			echo '<div class="error"><p>The <strong>SP Core</strong> Plugin is not installed.  It must be installed and activated in order for this plugin to operate.</p></div>';
		endif;
	}
	
	function panel_schedule( ) { sp_panel_schedule( "sp_facebookfeed_twicedaily_event" ); }

	/**
	 *	@func 		panel_admin_settings
	 *	@params 	<void>
	 *	@returns 	<void>
	 */	
	 
	public function panel_admin_settings() {
		$template = array( );
		$template["hidden_field_name"] = "sp_facebookfeed_forceupdate";
		$template["hidden_field_name2"] = "sp_facebookfeed_admin_settings";
		$msg = NULL;
		if (isset($_POST[$template["hidden_field_name"]])) {
			$update_facebook_status = $this->update_facebook_status( );
			$template["msg"] = ( $update_facebook_status ) ? '<div class="alert alert-success"><p>The latest posts were imported successfully!</p></div>' : '<div class="alert alert-error"><p>No posts were imported!</p></div>';
		}
		if (isset($_POST[$template["hidden_field_name2"]])) {
			$pageid = sp_clean_post($_POST["pageid"]);
			$appid = sp_clean_post($_POST["appid"]);
			$secretkey = sp_clean_post($_POST["secretkey"]);
			
			$temp_attachments = $_POST["attachments"];
			if (is_array($temp_attachments)) {
				$attachments = implode(',', $temp_attachments);
			}
			if ($pageid && $appid && $secretkey) {
				$fields = array('pageid'=>$pageid,'appid'=>$appid,'secretkey'=>$secretkey,'attachments'=>$attachments);
				update_option($this->OPTION_FEED_ACCOUNT, $fields);
				$template["msg"] = '<div class="alert alert-success"><p>Admin settings are updated!</p></div>';
			}
			else { $template["msg"] = '<div class="alert alert-error"><p>Please ensure you enter a valid account name.</p></div>'; }
		}
		$template["setting"] = get_option($this->OPTION_FEED_ACCOUNT);

		$facebook_attachment_types = explode(',', $template["setting"]["attachments"]);
	    foreach ($this->facebook_attachment_types as $attach_type=>$attach_name):
			$checked = (in_array($attach_type,(array)$facebook_attachment_types)) ? 'checked="checked"' : NULL;
	        $template["checkboxes"] .= '<label for="attachment_'.$attach_type.'"><input type="checkbox" id="attachment_'.$attach_type.'" name="attachments[]" value="'.$attach_type.'" '.$checked.' />&nbsp;'.$attach_name.'</label>';
	    endforeach;

	    echo $this->TWIG->render('panel.facebook.settings.twig.html',$template);
	}

	/**
	 *	@function	register_the_widget
	 *	@params 	<void>
	 *	@return 	<void>
	 */
	public function register_the_widget ( ) { register_widget ( self::NAME ); }
	
	/**
	 *	@function	form
	 *	@params 	<instance:array> widget instance arguments injected from Wordpress
	 *	@return 	<void>
	 */
	public function form( $instance )
	{
		$template = array(
			"languages" => array( "en", "fr" ),
			"class" => $this,
			"instance" => $instance,
			'french_enabled' => true
		);
		echo $this->TWIG->render('widget.facebook.form.twig.html',$template);
	}

	/**
	 *	@function	update
	 *	@params 	<new_instance:array> new widget instance arguments injected from Wordpress
	 *				<old_instance:array> old widget instance arguments injected from Wordpress
	 *	@return 	<instance:array> changed widget instance arguments to be saved by Wordpress
	 */
	public function update( $new_instance, $old_instance )
	{
		$instance = array( );
		$languages = array( "en", "fr" );
		foreach ( $languages as $lang )
		{
			$instance[$lang]["caption"] = $new_instance["widget_caption_".$lang];
			$instance[$lang]["posts_no"] = $new_instance["widget_posts_no_".$lang];
		}
		return $instance;
	}

	/**
	 *	@function	widget
	 *	@params 	<args:array> widget arguments injected from WordPress
	 *				<instance:array> instance arguments injected from WordPress
	 *	@return 	<void>
	 */
	public function widget( $args, $instance )
	{
		global $wpdb;
		$theme_shortcode = get_option( "sp_theme_shortcode" );
		$theme_name = ( !empty( $theme_shortcode ) ? $theme_shortcode : "default" );
		$lang = sp_translate('en','fr');
		extract($args, EXTR_SKIP);
		$posts_no = $instance[$lang]["posts_no"];
		$posts_no = (is_numeric($posts_no)) ? (int)$posts_no : 3;
		$results = $wpdb->get_results("SELECT * FROM {$this->WPDB_TABLE} ORDER BY id DESC LIMIT $posts_no;");
		if ($results) {
			$setting = get_option($this->OPTION_FEED_ACCOUNT);
			$attachment_types = explode(",",$setting["attachments"]);
			$pageid = $setting["pageid"];
			$view_page = @sp_translate("View Post","Consulter la page");
			$posts = array();
			$x=0;
			foreach ($results as $result):
				$data =  json_decode($result->facebook_post_data);
				$posts[$x]['message'] = ($data->message != '') ? $this->get_excerpt(50, $data->message) : '';
				$posts[$x]['date'] = human_time_diff( strtotime( $data->created_time ) );
				$posts[$x]['likes'] = $data->like_count;
				$posts[$x]['src'] = false;
				if (in_array("photo",(array)$attachment_types))
				{
					if ($result->facebook_image_src_big)
					{
						$posts[$x]['src'] = $result->facebook_image_src_big;
					}
				}
				$posts[$x]['img']= ($posts[$x]['src']) ? '<img src="'.$posts[$x]['src'].'" />' : NULL;
				$x++;
			endforeach;
		}

		$template = array (
			"before"       => $before_widget,
			"before_title" => $before_title,
			"title"        => $instance[$lang]["caption"],
			"after_title"  => $after_title,
			"posts"        => $posts,
			"viewpost"     => $view_page,
			"after"        => $after_widget
		);
		
		echo $this->TWIG->render('widget.facebook.default.twig.html', $template);
	}
	/**
	 *	@name 		db_install
	 *	@param 		<void>
	 *	@returns 	<void>
	 */
	function db_install( ) {
		global $wpdb;
		if ( !function_exists('maybe_create_table') ) require_once(ABSPATH . 'wp-admin/install-helper.php');
		$sql = "CREATE TABLE {$this->WPDB_TABLE} (
			`id` int(11) NOT NULL auto_increment,
			`facebook_post_id` varchar(250) NOT NULL,
			`facebook_post_data` longblob NOT NULL,
			`facebook_image_src_big` varchar(300) NOT NULL,
			`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		maybe_create_table( $this->WPDB_TABLE, $sql );
	}
	/**
	 *	@func 		iniital_install
	 *	@params 	<void>
	 *	@returns 	<void>
	 */	
	function initial_install() { @$this->db_install(); putenv("TZ=US/Eastern"); wp_schedule_event((time() - 14400),'twicedaily','sp_facebookfeed_twicedaily_event'); }
	/**
	 *	@func 		deactivate
	 *	@params 	<void>
	 *	@returns 	<void>
	 */	
	function deactivate() { wp_clear_scheduled_hook('sp_facebookfeed_twicedaily_event'); }
	/**
	 *	@name 		update_facebook_status
	 *	@param 		<void>
	 *	@returns 	<void>
	 */
	function update_facebook_status( )
	{
		global $wpdb;
		$new_records = false;
		$json = ( $this->do_get_latest(5) );
		if ($json) {
			$setting = get_option($this->OPTION_FEED_ACCOUNT);
			$token = wp_remote_get( 'https://graph.facebook.com/oauth/access_token?client_id='.$setting["appid"].'&client_secret='.$setting["secretkey"].'&grant_type=client_credentials' );
			$fb_feed = array_reverse($json->data);
			foreach ( $fb_feed as $object ):   
				$id = $object->id;

				/* Start - Attempt to get the largest version of the photo */
				$facebook_image_src_big = false;
				if ( !empty( $object->attachments->data ) )
				{
					if ( is_object( $object->attachments->data[0]->subattachments ) ) {
						foreach ($object->attachments->data[0]->subattachments->data[0]->media as $media):
							$facebook_image_src_big = $media->src;
						endforeach;
					} else {
						foreach ($object->attachments->data[0]->media as $media):
							$facebook_image_src_big = $media->src;
						endforeach;
					}
				}
				$facebook_image_src_big = ($facebook_image_src_big) ? $facebook_image_src_big : NULL;
				/* End - Attempt to get the largest version of the photo */
				
				// Build facebook_post_data from the current object;
				$facebook_post_data = $this->build_facebook_post_data($object);

				$result = $wpdb->get_row( "SELECT * FROM {$this->WPDB_TABLE} WHERE facebook_post_id = '$id';" );
				if ( $result != null ) {
					$wpdb->update(
						$this->WPDB_TABLE,
						array( 'facebook_post_data'		=> json_encode( $facebook_post_data ) ),
						array( 'facebook_post_id'		=> $id ),
						array( 'facebook_image_src_big'	=> $facebook_image_src_big )
					);
				} else {
					$insert = $wpdb->insert( 
						$this->WPDB_TABLE,
						array( 
							'facebook_post_id'			=> $id,
							'facebook_post_data'		=> json_encode( $facebook_post_data ),
							'facebook_image_src_big'	=> $facebook_image_src_big
						),
						array( '%s','%s','%s' )	
					);
				}
				if ( $insert ) $new_records = true;
			endforeach;
		}
		return $new_records;
	}

	public function build_facebook_post_data($object)
	{
		// if (!is_object($object)) throw('Not a valid object'); return;
		
		$post = new stdClass();
		
		$post->name         = $object->from->name;
		$post->id           = $object->id;
		$post->message      = $object->message;
		$post->link         = $object->link;
		$post->like_count   = count($object->likes->data);
		$post->type         = $object->type;
		$post->created_time = $object->created_time;

		return $post;
	}

	/**
	 *	@name 		do_get_lastest
	 *	@param 		<max:int> defining the maximum number of posts to return (Default:5)
	 *	@returns 	<query:string> of encoded JSON containing the posts
	 */
	function do_get_latest($max = 5) {
		$query = '';
		$setting = get_option($this->OPTION_FEED_ACCOUNT);
		$pageid = $setting["pageid"];
		if ($pageid) {
			$token = wp_remote_get( 'https://graph.facebook.com/oauth/access_token?client_id='.$setting["appid"].'&client_secret='.$setting["secretkey"].'&grant_type=client_credentials' );
			$response = wp_remote_get( "https://graph.facebook.com/v2.2/".$pageid."/posts?fields=attachments.limit(1){media,subattachments},message,from,type,id,likes,link&limit=".$max."&" . $token["body"] );
			// echo '<pre>';
			// print_r(json_decode($a["body"]));
			// echo '</pre>';
			// die();
			/*	// fql?q=".urlencode("SELECT page_id FROM page WHERE username=\"$username\"")."&".$token["body"] );
			$jsonString = wp_remote_get( "https://graph.facebook.com/fql?q=".urlencode("SELECT page_id FROM page WHERE username=\"$username\"")."&".$token["body"] );
			// print_r($jsonString);
			$temp = json_decode( preg_replace( '/:\s*(\-?\d+(\.\d+)?([e|E][\-|\+]\d+)?)/', ': "$1"', $jsonString["body"] ) );
			$id = $temp->data[0]->page_id;
			$temp_query = urlencode("SELECT post_id,message,permalink,attachment,created_time,likes FROM stream WHERE source_id = \"$id\" AND message LIMIT $max");
			$reponse = wp_remote_get("https://graph.facebook.com/fql?q=".$temp_query."&".$token["body"]);*/
			$query = json_decode($response["body"]);
		}
		return $query;
	}
}
$SP_FacebookFeed = new SP_FacebookFeed();
else : exit("Class 'SP_FacebookFeed' already exists"); endif;


if ( isset( $SP_FacebookFeed ) ):

register_activation_hook(__FILE__, array(&$SP_FacebookFeed, 'initial_install'));
register_deactivation_hook(__FILE__, array(&$SP_FacebookFeed, 'deactivate'));

if (  defined( "SP_CORE_ACTIVE_FLAG" ) ):
add_action('admin_menu', array(&$SP_FacebookFeed, 'add_pages'));
add_action('widgets_init', array(&$SP_FacebookFeed, 'register_the_widget'));
add_action('sp_facebookfeed_twicedaily_event', array(&$SP_FacebookFeed, 'update_facebook_status'));
endif;

endif;
?>