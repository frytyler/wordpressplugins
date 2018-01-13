$js_ = jQuery.noConflict( );

var ga_elems = $js_( "body" ).find( "[data-ga-cat]", "[data-ga-action]", "[data-ga-label]" );

ga_elems.each( function( k, v ) { 
	$js_( v )
		.on( 'click', function( ) {
			ga( 'send', 'event', jQuery( this ).data( "gaCat" ), jQuery( this ).data( "gaAction" ), jQuery( this ).data( "gaLabel" ) );
		} )
	;
} );