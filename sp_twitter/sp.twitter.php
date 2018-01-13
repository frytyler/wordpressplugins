<?php
/*
Plugin Name: (SP) Twitter
Version: 2.0.1
Author: SP
Description: Import Tweets from Usernames (Twitter API v1.1, using OAuth 2 bearer token).
*/
?>
<?php
if (!function_exists('add_action')) { header('Status: 403 Forbidden'); header('HTTP/1.1 403 Forbidden'); exit("This page can't be loaded outside of WordPress!"); }
global $wp_version;
if (version_compare($wp_version, "3.8.2", "<")) { exit("<b>CPC Tweets</b> requires WordPress 3.8.2 OR newer. <a href=\"http:/codex.wordpress.org/Upgrading_WordPress\">Please update!</a>"); }

if ( !class_exists( "TwitterOAuthBearer" ) )
	require_once __DIR__."/class.TwitterOAuthBearer.php";

if( !class_exists( 'SP_TWITTER' ) ):
class SP_TWITTER extends WP_Widget
{
	/**
	 *	const vars
	 */
	const NAME = "SP_TWITTER";
	const TITLE = "(SP) Twitter";
	const MENUTITLE = "Twitter";
	const VERSION = "1.0";

	var $URI;
	var $DIR;
	var $WPDB_TABLE;
	var $WP_SETTINGS;

	var $TWIG;

	/**
	 *	Constructor
	 */
	public function __construct( )
	{
		global $wpdb;

		parent::__construct(
	 		self::NAME, // Base ID
			self::TITLE, // Name
			array( 'description' => __( "Import Tweets and display them in the sidebar", 'text_domain' ), ), // Args
			array(  )
		);

		$this->URI = plugin_dir_url( __FILE__ );
		$this->DIR = plugin_dir_path( __FILE__ );
		$this->WPDB_TABLE = $wpdb->prefix . "sp_tweets";
		$this->WP_SETTINGS = "sp_twitter_settings";

		if ( defined( "SP_CORE_ACTIVE_FLAG" ) )
			$this->TWIG = sp_new_twig_engine( $this->DIR."templates/" );

		return;
	}

	/**
	 *	FRONT END STYLES	
	 */

	public function wp_styles(  ) 
	{
		?>	
		<style type="text/css">
			.sp-icon { background: url(<?=$this->URI;?>sp.twitter_sprite.png) no-repeat; display: inline-block; opacity: .6; -webkit-transition: opacity .3s ease; -moz-transition: opacity .3s ease; -ms-transition: opacity .3s ease; -o-transition: opacity .3s ease; transition: opacity .3s ease; }
			.sp-icon:hover { opacity: 1; }
			.sp-icon-twitter-reply { background-position: -2px -2px; width: 12px; height: 9px; }
			.sp-icon-twitter-retweet { background-position: -21px -2px; width: 14px; height: 9px; }
			.sp-icon-twitter-favorite { background-position: -41px -1px; width: 10px; height: 10px; }
			.sp-icon-twitter-follow { background-position: -58px -2px; width: 10px; height: 10px; }
		</style>
		<?php
	}
	
	

	/**
	 *	WP WIDGET SECTION
	 */

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
			'class'    => $this,
			'instance' => $instance
		);		
		echo $this->TWIG->render('widget.form.twig',$template);
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
		$instance["caption_en"] = $new_instance["widget_caption_en"];
		$instance["caption_fr"] = $new_instance["widget_caption_fr"];
		$instance["posts_no"] = $new_instance["widget_posts_no"];

		return $instance;
	}

	/**
	 *	@func 		widget
	 *	@params 	<args:array>
	 *	@returns 	<void>
	 */
	public function widget( $args, $instance ) {
		global $wpdb;
		
		extract($args);
		$setting = get_option($this->WP_SETTINGS);

		$isFr = @sp_translate(0,1);

		$username = @sp_translate($setting['username_en'], $setting['username_fr']);
		$hashtag = @sp_translate($setting['hashtag_en'], $setting['hashtag_fr']);
		if ($hashtag != '') {
			$searchtype = 'hashtag';
		} else {
			$searchtype = 'username';
		}
		$tweets = $wpdb->get_results("SELECT * FROM {$this->WPDB_TABLE} WHERE visible = 1 AND isDeleted = 0 AND search_type = '{$searchtype}' AND isFr = {$isFr} ORDER BY id DESC LIMIT 0, ".$instance["posts_no"].";");

		foreach( $tweets as &$tweet )
			$tweet->tweet_created_at = human_time_diff( strtotime( $tweet->tweet_created_at ) );

		$data = array (
			"before"       => str_replace( '[classes]', 'twitter box', $before_widget ),
			"before_title" => $before_title,
			"title"        => @sp_translate($instance["caption_en"],$instance["caption_fr"]),
			"after_title"  => $after_title,
			"tweets"       => $tweets,
			"after"        => $after_widget
		);

		echo $this->TWIG->render( "widget.default.twig", $data );
	}
	
	/**
	 *	WP PLUGIN SETTINGS MENU SECTION
	 */

	/**
	 *	@func 		add_pages
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function add_pages()
	{
		wp_enqueue_script('common'); wp_enqueue_script('wp-lists'); wp_enqueue_script('postbox');
		add_menu_page(self::MENUTITLE, self::MENUTITLE, 8, 'sp_tweets', array(&$this, 'display_dashboard'), $this->URI.'sp.twitter.png');
	}

	/**
	 *	@func 		display_dashboard
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function display_dashboard() 
	{
		$show_hashtag_panel = false;
		$setting = get_option($this->WP_SETTINGS);

		if ($setting["hashtag_en"] != '' || $setting["hashtag_fr"] != '') {
			$show_hashtag_panel = true;
		}

		if(class_exists('SP_BLOCK')):
			$block = new SP_BLOCK();
			$block->add_tab('panel_1',__('Configuration Settings'),array(&$this,'panel_1'));
			if ($show_hashtag_panel) {
				$block->add_tab('panel_hashtags',__('Hashtag Tweets'),array(&$this,'panel_hashtags'));
			}
			$block->add_tab('panel_schedule',__('Schedule'), function( ) { sp_panel_schedule( "sp_tweets_twicedaily_event" ); } );
			$block->draw_block(self::MENUTITLE,'V.'.self::VERSION,'Settings are reflected on the output of the twitter widget');
		else:
			echo '<div id="message" class="updated">Please install SP Core</div>';
		endif;
	}

	/**
	 *	@func 		panel_1
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function panel_1()
	{
		$hidden_field_name = "sp_tweets_forceupdate";
		$hidden_field_name2 = "sp_tweets_admin_settings";
		$msg = NULL;
		if ( isset( $_POST[$hidden_field_name] ) )
		{
			$msg = ( $this->update_twitter_status( ) ) ? '<div class="alert alert-success"><p>The latest tweets were imported successfully!</p></div>' : '<div class="alert alert-error"><p>There was an issue importing your latest tweets</p></div>';
		}
		if ( isset( $_POST[$hidden_field_name2] ) )
		{
			$username_en = trim( stripslashes( strip_tags( $_POST["username_en"] ) ) );
			$username_fr = trim( stripslashes( strip_tags( $_POST["username_fr"] ) ) );
			$consumer_key = trim( stripslashes( strip_tags( $_POST["consumer_key"] ) ) );
			$consumer_secret = trim( stripslashes( strip_tags( $_POST["consumer_secret"] ) ) );
			$username_en = str_replace( '@', '', $username_en );
			$username_fr = str_replace( '@', '', $username_fr );
			$hashtag_en = str_replace( '#', '', $_POST["hashtag_en"] );
			$hashtag_fr = str_replace( '#', '', $_POST["hashtag_fr"] );
			if ( $username_en )
			{
				$fields = array(
					'consumer_key'		=> $consumer_key,
					'consumer_secret'	=> $consumer_secret,
					'username_en'		=> $username_en,
					'username_fr'		=> $username_fr,
					'hashtag_en'		=> $hashtag_en,
					'hashtag_fr'		=> $hashtag_fr
				);
				update_option( $this->WP_SETTINGS, $fields );
				$msg = '<div class="alert alert-success"><p>Admin settings are updated!</p></div>';
			}
			else { $msg = '<div class="alert alert-error"><p>Please ensure you enter a valid account name.</p></div>'; }
		}
		$setting = get_option( $this->WP_SETTINGS );
		
		
		echo $this->TWIG->render( 
			"panel.twitter.settings.twig", 
			array(
				'msg'					=> $msg,
				'hidden_field_name'		=> $hidden_field_name,
				'hidden_field_name2'	=> $hidden_field_name2,
				'setting'				=> $setting
			)
		);
	}

	public function panel_hashtags( )
	{
		global $wpdb;
		$template = array( );

		$template["hidden_field_name"] = 'sp_twitter_deletehashtag';
		$template["hidden_field_name_restore"] = 'sp_twitter_restorehashtag';
		
		if (isset( $_POST[ $template["hidden_field_name"] ] )) {
			$tweet_id = trim( $_POST["tweet_id"] );
			$update = $wpdb->update(
				$this->WPDB_TABLE,
				array( "isDeleted" => 1 ),
				array( "id" => $tweet_id )
			);
			if ( $update ) {
				$template["msg"] = '<div class="alert alert-success"><p>Tweet has been deleted</p></div>';
			}
		}
		if (isset( $_POST[ $template["hidden_field_name_restore"] ] )) {
			$tweet_id = trim( $_POST["tweet_id"] );
			$update = $wpdb->update(
				$this->WPDB_TABLE,
				array( "isDeleted" => 0 ),
				array( "id" => $tweet_id )
			);
			if ( $update ) {
				$template["msg"] = '<div class="alert alert-success"><p>Tweet has been restored</p></div>';
			}
		}

		$tweets = $wpdb->get_results("SELECT * FROM {$this->WPDB_TABLE} WHERE visible = 1 AND search_type = 'hashtag' ORDER BY id DESC");

		foreach( $tweets as &$tweet )
			$tweet->tweet_created_at = human_time_diff( strtotime( $tweet->tweet_created_at ) );

		$template["tweets"] = $tweets;

		echo $this->TWIG->render('panel.twitter.hashtagtweets.twig', $template);
	}

	/**
	 *	WP INSTALL, DEACTIVATE, UPDATE FUNCTIONS
	 */
	
	/**
	 *	@func 		initial_install
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function initial_install() { @$this->db_install(); putenv("TZ=US/Eastern"); wp_schedule_event((time() - 14400),'twicedaily','sp_tweets_twicedaily_event'); }

	/**
	 *	@func 		db_install
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function db_install() 
	{
		global $wpdb;
		if (!function_exists('maybe_create_table')) { require_once(ABSPATH . 'wp-admin/install-helper.php'); }
		$sql = "CREATE TABLE $this->WPDB_TABLE (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`tweet_created_at` varchar(64) NOT NULL,
			`tweet_id_str` varchar(64) NOT NULL,
			`tweet_url` varchar(300) NOT NULL,
			`tweet_text` LONGTEXT NOT NULL,
			`user_screenname` varchar(64) NOT NULL,
			`user_name` varchar(64) NOT NULL,
			`user_avatar` varchar(300) NOT NULL,
			`timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`visible` int(11) NOT NULL DEFAULT '0',
			`isFr` int(1) NOT NULL DEFAULT '0',
			`isDeleted` int(1) NOT NULL DEFAULT '0',
			`search_type` varchar(64) NOT NULL,
			PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
		maybe_create_table($this->WPDB_TABLE, $sql);
	}

	public function get_tweet_ids()
	{
		global $wpdb;
		$tweets = $wpdb->get_results("SELECT tweet_id_str FROM {$this->WPDB_TABLE} ORDER BY id ASC");
		$tweet_ids = array();
		$x = 0;
		foreach($tweets as $tweet):
			$tweet_ids[$x] = $tweet->tweet_id_str;
			$x++;
		endforeach;
		return $tweet_ids;
	}
	
	/**
	 *	@func 		update_twitter_status
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function update_twitter_status()
	{
		global $wpdb;
		$setting = get_option($this->WP_SETTINGS);
		$twitter = new TwitterOAuthBearer( $setting["consumer_key"], $setting["consumer_secret"] );

		$langs = array( "en", "fr" );
		$username = '';
		$hashtag = '';

		if ( $twitter->authenticate( ) ):
			foreach ( $langs as $lang ):
				$isFr = (int)( "fr" == $lang );
				if ( $hashtag = trim( $setting["hashtag_".$lang] )) {
					$json = $twitter->request( "search/tweets", array( "q" => urlencode($hashtag), "include_entities" => true, "count" => "5" ) );

					$results = $this->process_twitter_json($json->statuses, 'hashtag', $isFr, '', $hashtag);
				} 
				elseif ( $username = trim( $setting["username_".$lang] ) )
				{
					$json = $twitter->request( "statuses/user_timeline", array( "include_entities" => true, "include_rts" => true, "count" => "5", "screen_name" => $username ) );

					$results = $this->process_twitter_json($json, 'username', $isFr, $username);
				}
			endforeach;
		endif;
		return $results;
	}

	private function process_twitter_json($json, $searchtype, $isFr, $username = '', $hashtag = '')
	{
		global $wpdb;
		if ($searchtype == 'hashtag') {
			$where = array( "search_type" => 'hashtag', "isFr" => $isFr );
		} else {
			$where = array( "user_screenname" => $username, "isFr" => $isFr );
		}
		if ( !empty( $json ) ) {
			$reset = $wpdb->update(
				$this->WPDB_TABLE,
				array( "visible" => 0 ),
				$where
			);
			$current_tweet_ids = $this->get_tweet_ids();
			foreach ( (array)$json as $tweet ) :
				$created_at = trim( $tweet->created_at );
				$id_str = trim( $tweet->id_str );
				$text = trim( $tweet->text );
				$user_screenname = trim( $tweet->user->screen_name );
				$user_name = trim( $tweet->user->name );
				$user_avatar = trim( $tweet->user->profile_image_url );
				$permalink = 'https://twitter.com/'.$user_screenname.'/status/'.$id_str;
				foreach($tweet->entities->user_mentions as $user_mention):
					if($user_mention->screen_name){
						$text = $this->replace_user_mentions($text, $user_mention->screen_name);
					}
				endforeach;
				foreach($tweet->entities->hashtags as $hashtag):
					if($hashtag->text){
						$text = $this->replace_hashtag($text, $hashtag->text);
					}
				endforeach;
				foreach($tweet->entities->urls as $url):
					if($url->url){
						$text = $this->replace_url($text, $url->url, $url->expanded_url, $url->display_url);
					}
				endforeach;
				if (in_array($id_str, $current_tweet_ids)) {
					// update tweet
					$data = $wpdb->update( 
						$this->WPDB_TABLE,
						array(
							"tweet_created_at" => $created_at,
							"tweet_url"        => $permalink,
							"tweet_text"       => $text,
							"user_screenname"  => $user_screenname,
							"user_name"        => $user_name,
							"user_avatar"      => $user_avatar,
							"visible"          => 1,
							"isFr"             => $isFr,
							"search_type"      => $searchtype
						),
						array( "tweet_id_str" => $id_str )
					);
				} else {
					$data = $wpdb->insert( 
						$this->WPDB_TABLE,
						array(
							"tweet_created_at" => $created_at,
							"tweet_id_str"     => $id_str,
							"tweet_url"        => $permalink,
							"tweet_text"       => $text,
							"user_screenname"  => $user_screenname,
							"user_name"        => $user_name,
							"user_avatar"      => $user_avatar,
							"visible"          => 1,
							"isFr"             => $isFr,
							"search_type"      => $searchtype
						)
					);
				}
				if( $data )
					$results = true;

			endforeach;
		} else { $results = false; }
		return $results;
	}

	private function replace_user_mentions($text, $usermention)
	{
		return str_ireplace('@'.$usermention,'<a href="https://twitter.com/'.$usermention.'" title="https://twitter.com/'.$usermention.'" target="_blank">@'.$usermention.'</a>',$text);
	}
	private function replace_hashtag($text, $hashtag)
	{
		return str_ireplace('#'.$hashtag,'<a href="https://twitter.com/?q=#'.$hashtag.'" title="https://twitter.com/?q=#'.$hashtag.'" target="_blank">#'.$hashtag.'</a>',$text);
	}
	private function replace_url($text, $url, $url_expanded, $url_display)
	{
		return str_ireplace($url,'<a href="'.$url.'" title="'.$url_expanded.'" target="_blank">'.$url_display.'</a>',$text);
	}

	/**
	 *	@func 		deactivate
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function deactivate() { wp_clear_scheduled_hook('sp_tweets_twicedaily_event'); }
}
$SP_TWITTER = new SP_TWITTER();
else : exit("Class 'SP_TWITTER' already exists"); endif;


if ( !empty( $SP_TWITTER ) ): 

register_activation_hook(__FILE__, array(&$SP_TWITTER, 'initial_install'));
register_deactivation_hook(__FILE__, array(&$SP_TWITTER, 'deactivate'));

if ( defined( "SP_CORE_ACTIVE_FLAG" ) ):
add_action('sp_tweets_twicedaily_event', array(&$SP_TWITTER, 'update_twitter_status'));
add_action('widgets_init', array(&$SP_TWITTER, 'register_the_widget'));
add_action('admin_menu', array(&$SP_TWITTER, 'add_pages'));
add_action('wp_head', array(&$SP_TWITTER, 'wp_styles'));
endif;

endif;

?>