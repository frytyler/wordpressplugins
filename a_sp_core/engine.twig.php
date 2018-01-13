<?php

/**
 *	Twig Templating Engine Setup Functions
 */

require_once __DIR__."/Twig/Autoloader.php";
Twig_Autoloader::register();

/**
 *	@procedure	load_twig_filters
 */
$twig_new_filters = array( );
if ( $handle = opendir( __DIR__.'/twig_custom_filters' )) 
{
	while ( false !== ( $entry = readdir( $handle ) ) ) 
	{
		if ( $entry != "." && $entry != ".." )
		{			
			$file_inc_path = __DIR__.'/twig_custom_filters/'.$entry;
			if ( is_file( $file_inc_path ) )
				include_once( $file_inc_path );
		}
	}
	
	closedir($handle);
}
/**
 *	@endprocedure
 */

/**
 *	@func 		sp_new_twig_engine
 * 	@params 	<dir:string> the path to the templates directory, or <null> if using a string template
 *  @returns 	<Twig_Environment>
 *	@example 	
 * 		$twig = sp_new_twig_engine( );
 * 		echo $twig->render('Hello {{ name }}!', array('name' => 'Hello'));
 *		OR
 *		$twig = sp_new_twig_engine( __DIR__."/templates" ); // HAS TO BE PATH
 *		echo $twig->render( "index.twig", array('name' => 'Hello'));
 */
function sp_new_twig_engine( $dir = null )
{
	global $twig_new_filters;

	if ( is_null( $dir ) )
		$loader = new Twig_Loader_String();
	else
		$loader = new Twig_Loader_Filesystem( $dir );

	$twig = new Twig_Environment( $loader, array('debug' => true) );
	$twig->addExtension(new Twig_Extension_Debug());
	
	foreach( $twig_new_filters as $filter )
		$twig->addFilter( $filter );
	
	return $twig;
}

/**
 *	@func 		sp_new_twig_engine
 * 	@params 	<twig:Twig_Loader_Filesystem>
 *				<theme:string>
 *				<filename:string>
 *				<template:array>
 *				<error_msg:bool>
 *  @returns 	<string>
 */
function sp_render_template( &$twig, $theme, $filename, $template, $error_msg = false )
{
	$loader = $twig->getLoader( );

	if ( $loader instanceof Twig_Loader_Filesystem )
	{
		if ( !$loader->exists( $filename ) )
			$filename = str_replace( $theme, "default", $filename );

		if ( $loader->exists( $filename ) )
			return $twig->render( $filename, $template );
	}

	if ( $error_msg )
		return "Template can't render, are you sure the file exists?";
	return " ";
}

?>