<?php

/**
 *	@func 		twig_truncate_word
 * 	@params 	<string:string> the initial string
 *				<length:int> the desired length of the string
 *  @returns 	<string:string> the initial string, trsuncated, preserving words
 */
function twig_truncate_word( $string, $length = null )
{
	if ( !is_null( $length ) )
	{
		$length++;
		$string = trim( $string );
		if ( strlen( $string ) > $length ) 
		{
			$subex = substr( $string, 0, $length - 5 );
			$exwords = explode( ' ', $subex );
			$excut = - ( strlen( $exwords[count( $exwords ) - 1] ) );
			if ( $excut < 0 ) 
				$string = substr( $subex, 0, $excut );
			else 
				$string = $subex;

			$string .= '...';
		}
	}

	return $string;
}

/**
 *	Register and Add Filter
 */
$twig_new_filters[] = new Twig_SimpleFilter( 'truncate_word', 'twig_truncate_word' );