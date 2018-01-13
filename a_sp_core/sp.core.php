<?php
/*
Plugin Name: (SP) Core
Version: 1.0
Author: SP
Description: Core modules
*/
( @__DIR__ == '__DIR__' ) && define( '__DIR__', realpath( dirname(__FILE__) ) );

if (!function_exists('add_action')) { header('Status: 403 Forbidden'); header('HTTP/1.1 403 Forbidden'); exit("This page can't be loaded outside of WordPress!"); }
global $wp_version;
if (version_compare($wp_version, "3.8.2", "<")) { exit("<b>CPC Tweets</b> requires WordPress 3.8.2 OR newer. <a href=\"http:/codex.wordpress.org/Upgrading_WordPress\">Please update!</a>"); }

define( "SP_CORE_ACTIVE_FLAG", true );
define(	"SP_CORE_URL", WP_PLUGIN_URL."/".plugin_basename( dirname( __FILE__ ) ) );	
define( "SP_CORE_DIR", plugin_dir_path( __FILE__ ) );

/**
 *	COMMON FUNCTIONS
 */
function sp_translate($en=NULL,$fr=NULL) {
	if(function_exists('pll_current_language')) {
		if ("fr" == pll_current_language()) return $fr;
		return $en; 	
	} 
	
}
function sp_clean_post( $p ) { $p = strip_tags($p); $p = stripslashes($p); $p = trim($p); return $p; }
function sp_save_postdata($post_id, $n, $v) { if('' == trim($v) || '0' == trim($v)) { delete_post_meta($post_id, $n); } else { update_post_meta($post_id, $n, $v); } }
function main_excerpt($content) { $content = str_replace("[...]", "...", $content); return $content; }

/*
 *	@func  		sp_mail_headers
 * 	@params 	<send_from_name:string, send_from_email:string>
 * 	@returns 	headers prepared appropraitely. 
 */
function sp_mail_headers($send_from_name, $send_from_email) {
	$headers = "From: {$send_from_name} <{$send_from_email}>"."\r\n";
	$headers .= "Reply-To: {$send_from_name} <{$send_from_email}>"."\r\n";
	$headers .= "Return-path: {$send_from_email}\n";
	$headers .= "X-Mailer:PHP".phpversion()."\r\n";
	$headers .= "Precedence: list\nList-Id: ".@get_option('blogname')."\r\n";
	$headers .= "MIME-Version: 1.0\n";
	$headers .= "Content-Type: text/html; charset=\"".@get_bloginfo('charset')."\""."\r\n";
	return $headers;
}
/*
 *	@func 		sp_get_email_addresses
 * 	@params 	<ns:string, es:string>
 *  @returns 	Array, to and cc keys.
 */
function sp_get_email_addresses($ns,$es) 
{
	$n = explode(",",$ns); $e = explode(",",$es);
	if (count($e) > 1) {
		$temp["to"] = trim($e[0]);
		for ($x = 1; $x < count($e); $x++):
			$temp["cc"] .= trim($e[$x]).",";
		endfor;
		$temp["cc"] = substr($temp["cc"],0,-1);
		$temp["cc"] = "Cc: " . $temp["cc"] . "\n";
	}
	else { $temp["to"] = trim($e[0]); $temp["cc"] = NULL; }
	return $temp;
}
/*
 *	@func 		sp_verify_email_addresses
 *	@params 	<es:string> contains either email or comma separated list of emails
 * 	@returns 	bolean 
 */
function sp_verify_email_addresses($es) 
{
	$temp = true;
	$e = explode(",",$es);
	if (count($e) > 1) { for ($x = 0; $x < count($e); $x++) { if (!@is_email($e[$x])) $temp = false;	} }
	else { if (!@is_email($e[0])) $temp = false; }
	return $temp;
}

/*
 *	@func 		sp_get_youtube_id
 *	@params 	<yt_link:string>
 * 	@returns 	<void> 
 */
function sp_get_youtube_id( $yt_link )
{
	return preg_replace( '~
		# Match non-linked youtube URL in the wild. (Rev:20130823)
		https?://         # Required scheme. Either http or https.
		(?:[0-9A-Z-]+\.)? # Optional subdomain.
		(?:               # Group host alternatives.
		  youtu\.be/      # Either youtu.be,
		| youtube\.com    # or youtube.com followed by
		  \S*             # Allow anything up to VIDEO_ID,
		  [^\w\-\s]       # but char before ID is non-ID char.
		)                 # End host alternatives.
		([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
		(?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
		(?!               # Assert URL is not pre-linked.
		  [?=&+%\w.-]*    # Allow URL (query) remainder.
		  (?:             # Group pre-linked alternatives.
		    [\'"][^<>]*>  # Either inside a start tag,
		  | </a>          # or inside <a> element text contents.
		  )               # End recognized pre-linked alts.
		)                 # End negative lookahead assertion.
		[?=&+%\w.-]*        # Consume any URL (query) remainder.
		~ix', 
		'$1',
		$yt_link );
}

/*
 *	@func 		sp_css
 *	@params 	<void>
 * 	@returns 	<void> 
 */
function sp_css() { if (is_admin()) { wp_enqueue_style('sp_core_css',SP_CORE_URL.'/css/sp.core.css'); } }

add_action( "admin_print_styles", sp_css() );

/**
 *	Q8 BLOCK and DEPENDANCIES
 */

if ( !class_exists( 'SP_BLOCK' ) ):
class SP_BLOCK {

	var $arr = array();
	/**
	 *	@function	draw_block
	 *	@params 	<title:string>,<version:string>,<description:string>,<footer:string>
	 *	@return 	<void>
	 */
	public function draw_block($title,$version=NULL,$description=NULL,$footer=NULL){
		?>
		<div class="wrap">
       		<div class="sp_block">
            	<div class="header">
            		<h2><?php echo $title; ?> <small><?php echo $version; ?></small></h2>
            		<div class="description">
            			<p><?php echo $description; ?></p>
            		</div>
            	</div>
            	<div class="body">
            		<div class="nav">
            			<ul>
            				<?php echo $this->create_navigation(); ?>
            			</ul>
            		</div>
            		<div class="content">
            			<?php echo $this->create_tab_block(); ?>
            		</div>
            	</div>
            	<div class="footer">
            		<p><?php echo $footer; ?></p>
            	</div>
            </div>
        </div>
        <?php
	}
	/**
	 *	@function	add_tab
	 *	@params 	<id:string>,<title:string>,<callback:array>
	 *	@return 	<void>
	 */
	public function add_tab($id,$title,$callback){
		$this->arr[] = array('id'=>$id,'title'=>$title,'callback'=>$callback);
	}
	/**
	 *	@function	create_navigation
	 *	@params 	<void>
	 *	@return 	<li:string>
	 */
	private function create_navigation() {
		$arr = $this->arr;
		foreach($arr as $a):
			$li .= '<li><a href="#'.$a['id'].'">'.$a['title'].'</a></li>';
		endforeach;
		return $li;
	}

	/**
	 *	@function	create_tab_block
	 *	@params 	<void>
	 *	@return 	<tab_block:string>
	 */
	private function create_tab_block() {
		$arr = $this->arr;
		$i=0;
		foreach($arr as $a): 
			$i++;
			ob_start();
			call_user_func($a['callback']);
			$callback = ob_get_contents();
			ob_end_clean();
			$tab_block .= '<div id="'.$a['id'].'" class="panel">'.$callback.'</div>';
		endforeach;
		return $tab_block;
	}

	public static function css() { if (is_admin()) { wp_enqueue_style('sp_block_css',SP_CORE_URL.'/css/sp.block.css'); } }
	public static function js() { if (is_admin()) { wp_enqueue_script('sp_block_js',SP_CORE_URL.'/scripts/sp.block.js'); } }
}
endif;

add_action( "admin_print_styles", array( 'SP_BLOCK', "css" ) );
add_action( "admin_print_scripts", array( 'SP_BLOCK', "js" ) );


/**
 *	ENGINES ( TWIG AND GA INJECTOR )
 */

include_once __DIR__."/engine.twig.php";
include_once __DIR__."/engine.injector.php";
include_once __DIR__."/engine.metaboxes.php";

/**
 *	PANEL TEMPLATES
 */

/**
 *	@func 		panel_theme
 *	@params 	<void>
 *	@returns 	<void>
 */
function sp_panel_theme( $option ) 
{
	$twig = sp_new_twig_engine( SP_CORE_DIR."templates/" );

	$template = array( );
	$template["hidden_field_name"] = md5( "sp_theme" );

	if( isset( $_POST[$template["hidden_field_name"]] ) ) 
	{
		$theme_name = @sp_clean_post( $_POST["sp_theme"] );
		if( $theme_name ) 
		{
			update_option( $option, array( "theme_name" => $theme_name ) );
			$template["msg"] = "<div class=\"alert alert-success\"><p>Theme name settings have been updated.</p></div>";
		} 
		else 
		{
			delete_option( $option );
			$template["msg"] = "<div class=\"alert alert-success\"><p>Theme name settings have been removed.</p></div>";
		}
	}

	$template["setting"] = get_option( $option );

	echo $twig->render( "panel.core.theme.twig.html", $template );
}

/**
 *	@func 		panel_schedule
 *	@params 	<void>
 *	@returns 	<void>
 */
function sp_panel_schedule( $hook )
{
	putenv( "TZ=US/Eastern" );
	$timestamp = wp_next_scheduled( $hook );
	$scheduled_time = date( "l, F j, Y @ g:i A T", $timestamp );
	$current_time = date( "l, F j, Y @ g:i A T", ( time( ) - 14400 ) );
	?>
    <div class="blurb">The next update is scheduled to perform on <b><?php echo $scheduled_time; ?></b>. The current time is <b><?php echo $current_time; ?></b>.</div>
	<?php
}

?>