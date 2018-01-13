<?php

if ( !function_exists( 'add_action' ) ) 
{ 
	header( "Status: 403 Forbidden" ); 
	header( "HTTP/1.1 403 Forbidden" ); 
	exit( "This page can't be loaded outside of WordPress!" ); 
}

if ( !class_exists( "injector_admin" ) ):
class injector_admin
{

	const TITLE = "Google Analytics";
	const VERSION = "1.0";
	const OPT_NAME = "ga_settings";

	var $URI;

	public function __construct( )
	{
		$this->URI = plugins_url( "/", __FILE__ );
	}

	/**
	 *	WP PLUGIN SETTINGS MENU SECTION
	 */

	/**
	 *	@func 		add_a_wp_menu
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function add_a_wp_menu()
	{
		add_action('admin_menu', array(&$this, 'add_pages'));
	}

	/**
	 *	@func 		add_pages
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function add_pages()
	{
		wp_enqueue_script('common'); wp_enqueue_script('wp-lists'); wp_enqueue_script('postbox');
		add_menu_page(self::TITLE, self::TITLE, 8, 'injector_settings', array(&$this, 'display_dashboard'), $this->URI.'sp.core.png');
	}

	/**
	 *	@func 		display_dashboard
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function display_dashboard() 
	{
		if ( class_exists( 'SP_BLOCK' ) ):
			$block = new SP_BLOCK( );
			$block->add_tab( 'ga_settings', __('Settings'), array( &$this, 'ga_settings' ) );
			$block->draw_block( self::TITLE, self::VERSION, "Settings control the output of Google Analytics" );
		else:
			?>
			<div id="message" class="updated">Please install SP Core</div>
			<?php
		endif;
	}

	/**
	 *	@func 		ga_settings
	 *	@params 	<void>
	 *	@returns 	<void>
	 */
	public function ga_settings( )
	{
		
		$hidden_field = "injector_submit";
		if ( !empty( $_POST ) && $_POST[$hidden_field] )
		{
			$cur_settings["tracking_id"] = $_POST["tracking_id"];
			$cur_settings["enable_ga"] = ( 0 === strcasecmp( "on", $_POST["enable_ga"] ) ) ? true : false;

			update_option( self::OPT_NAME, $cur_settings );
			$msg = '<div class="alert alert-success">GA settings have been updated.</div>';
		}
		$cur_settings = get_option( self::OPT_NAME );
		echo $msg;
		?>
		<form method="POST">
			<div class="form title">
				<h3>Google Analytics Settings</h3>
			</div>
			<div class="form elems">
				<label for="_tracking_id">Tracking ID:</label>
				<input type="text" id="_tracking_id" name="tracking_id" class="widefat" value="<?php echo $cur_settings["tracking_id"]; ?>" />
				<label for="_enable_ga">Yes, I would like to have Google Analytics tracking enabled!</label>
				<input type="checkbox" id="_enable_ga" name="enable_ga" <?php echo ( $cur_settings["enable_ga"] ) ? "checked=\"checked\"" : NULL; ?> />
			</div>
			<div class="form submit">
				<input type="hidden" name="<?php echo $hidden_field; ?>" value="1" />
				<p><input type="submit" class="button button-primary" /></p>
			</div>
		</form>
		<?php
	}
}

$injector_admin = new injector_admin;

if ( is_admin( ) ) 
	$injector_admin->add_a_wp_menu( );

$ga_settings = get_option( injector_admin::OPT_NAME );

/**
 *	@func 		put_ga_tracking_info
 *	@params 	<void>
 *	@returns 	<void>
 */
function injector_put_ga_tracking_info( )
{
	global $ga_settings;
	?>
<script type="text/javascript">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '<?php echo $ga_settings["tracking_id"]; ?>');
ga('send', 'pageview');
</script>
	<?php
}

/**
 *	@func 		load_script
 *	@params 	<void>
 *	@returns 	<void>
 */
function injector_load_script( )
{
	global $injector_admin;
	?>
<?php echo PHP_EOL; ?>
<script src="<?php echo $injector_admin->URI; ?>scripts/injector.js"></script>
<script type="text/javascript">
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
	<?php
}

if ( !empty( $ga_settings ) && $ga_settings["enable_ga"] )
{
	add_action( "wp_head", "injector_put_ga_tracking_info" );
	add_action( "wp_footer", "injector_load_script" );
}

endif;

?>
