<?php

include_once ABSPATH."wp-admin/includes/plugin.php";
include_once ABSPATH."wp-admin/includes/template.php";
include_once ABSPATH."wp-includes/pluggable.php";

if ( !class_exists( "WPMetaPages" ) ):
class WPMetaPages
{
	var $slug;
	var $pagehook;

	var $page = array( );
	var $metaboxes = array( );

	var $sidebar = false;

	/**
	 *	CONSTRUCTOR
	 */

	/**
	 *	@func 		__construct
	 * 	@params 	<menu_slug:string>
	 *				<menu_title:string>
	 *				<page_title:string>
	 *				<type:string>
	 *				<parent:string>
	 *  @returns 	<void>
	 */
	public function __construct( $menu_slug, $menu_title, $page_title, $icon = "default", $type = "menu", $parent = null )
	{
		$this->slug = $menu_slug;

		$this->page = array( 
			"type" => $type,
			"title" => $page_title,
			"menu_title" => $menu_title,
			"menu_slug" => $menu_slug,
			"icon" => $icon,
			"parent" => $parent
		);

		return;
	}

	/**
	 *	@func 		add_metabox
	 * 	@params 	<slug:string>
	 *				<name:string>
	 *				<callback:callback>
	 *				<args:array>
	 *				<loc:string>
	 *				<prio:string>
	 *  @returns 	<bool>
	 */
	public function add_metabox( $slug, $name, $callback, $args = array( ), $loc = "normal", $prio = "core" )
	{
		if ( !array_key_exists( $slug, $this->metaboxes ) )
		{
 			$this->metaboxes[$slug] = array( 
 				"name" => $name,
 				"callback" => $callback,
 				"args" => $args,
 				"loc" => $loc,
 				"prio" => $prio
 			);

 			if ( "side" == $loc )
 				$this->sidebar = true;

 			return true;
		}

		return false;
	}

	/**
	 *	@func 		register
	 * 	@params 	<void>
	 *  @returns 	<void>
	 */
	public function register( )
	{
		add_action( "admin_menu", array( &$this, 'do_registered_metaboxes' ) );
	}

	/**
	 *	@func 		do_registered_metaboxes
	 * 	@params 	<void>
	 *  @returns 	<void>
	 */
	public function do_registered_metaboxes( )
	{
		wp_enqueue_script('common'); wp_enqueue_script('wp-lists'); wp_enqueue_script('postbox');
		
		switch( $this->page["type"] )
		{
			default:
			case "menu":
				$this->pagehook = add_menu_page( $this->page["title"], $this->page["menu_title"], 7, $this->page["menu_slug"], array( &$this, "on_show_page" ), $this->page["icon"] );
			break;

			case "submenu":
				$this->pagehook = add_submenu_page( $this->page["parent"], $this->page["title"], $this->page["menu_title"], 7, $this->page["menu_slug"], array( &$this, "on_show_page" ) );
			break;

			case "theme":
				$this->pagehook = add_theme_page( $this->page["title"], $this->page["menu_title"], 7, $this->page["menu_slug"], array( &$this, "on_show_page" ) );
			break;
		}

		foreach( $this->metaboxes as $slug => $values )
			add_meta_box( $slug, $values["name"], $values["callback"], $this->pagehook, $values["loc"], $values["prio"], $values["args"] );
	}

	/**
	 *	HELPERS
	 */

	public function on_show_page( ) 
	{
		extract( func_get_args( ) );
		$data = array();
		?>
		<div class="wrap">
            <h2><?php echo $this->page["title"]; ?></h2>
            <div id="poststuff" class="metabox-holder<?php echo ( $this->sidebar ) ? ' has-right-sidebar' : ''; ?>">
                <div id="side-info-column" class="inner-sidebar">
                    <?php do_meta_boxes( $this->pagehook, 'side', $data ); ?>
                </div>
                <div id="post-body" class="has-sidebar">
                    <div id="post-body-content" class="has-sidebar-content">
                        <?php do_meta_boxes( $this->pagehook, 'normal', $data ); ?>
                    </div>
                </div>
                <br class="clear" />
            </div>
		</div>
		<script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready( function($) {
                $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
            });
            //]]>
        </script>
        <?php
	}
}
endif;

?>