<?php

/**
 *	@func 		twig_language_date
 * 	@params 	<timestamp:int> the initial time
 *				<format:string> the desired length of the string
 *  @returns 	<string> the date run through the wordpress filter
 */
function twig_language_date( $timestamp, $format )
{
	return ucfirst( date_i18n( $format, $timestamp ) );
}

/**
 *	Register and Add Filter
 */
$twig_new_filters[] = new Twig_SimpleFilter( 'language_date', 'twig_language_date' );