<?php

/**
 *	@class			TwitterOAuthBearer
 *	@description 	Simple class which wraps the curl library performing the application-only authentication
 *					described by twitter here: <https://dev.twitter.com/docs/auth/application-only-auth>
 *	@version 		0.1a
 *	@lastModified 	14/06/2013
 */
class TwitterOAuthBearer
{
	private $app_name = "Twitter App v1.0";

	private $twitter_base_uri = "https://api.twitter.com";
	private $twitter_version = "1.1";
	private $twitter_return_type = "json";

	private $curl;
	private $encoded_bearer_token;
	private $access_token_type = "bearer";
	private $access_token;

	/**
	 *	@function	constructor
	 *	@params 	<consumer_key:string>
	 *				<consumer_secret:string>
	 *	@return 	<void>
	 */
	public function __construct( $consumer_key, $consumer_secret )
	{
		$this->make_encoded_bearer_token( $consumer_key, $consumer_secret );
	}

	/**
	 *	@function	authenticate
	 *	@return 	<bool> true on successful authentication, false otherwise
	 */
	public function authenticate( )
	{
		$this->curl_reset( );
		$request = "/oauth2/token";
		$uri = $this->twitter_base_uri.$request;

		$headers = array( 
			"POST {$request} HTTP/1.1", 
			"Host: api.twitter.com", 
			"User-Agent: {$this->app_name}",
			"Authorization: Basic {$this->encoded_bearer_token}",
			"Content-Type: application/x-www-form-urlencoded;charset=UTF-8", 
			"Content-Length: 29",
			"Accept-Encoding: gzip"
		);

		curl_setopt( $this->curl, CURLOPT_URL, $uri ); 
		curl_setopt( $this->curl, CURLOPT_HTTPHEADER, $headers ); 
		curl_setopt( $this->curl, CURLOPT_POST, 1 );
		curl_setopt( $this->curl, CURLOPT_POSTFIELDS, "grant_type=client_credentials" );
		curl_setopt( $this->curl, CURLOPT_ENCODING, "gzip" );
		curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec( $this->curl );
		$body = json_decode( $response );

		curl_close( $this->curl );

		if ( !is_null( $body ) && !empty( $body->access_token ) )
		{
			$this->set_access_token( $body->access_token );
			return true;
		}
		else
			return false;
	}

	/**
	 *	@function	request
	 *	@params 	<endpoint:string>
	 *				<parameters:array>
	 *	@return 	<bool> true on successful authentication, false otherwise
	 */
	public function request( $endpoint, $parameters = array( ) )
	{
		$this->curl_reset( );
		$endpoint = "/".$this->twitter_version."/".$endpoint.".".$this->twitter_return_type."?".http_build_query( $parameters );

		$uri = $this->twitter_base_uri.$endpoint;
		$headers = array( 
			"GET {$endpoint} HTTP/1.1", 
			"Host: api.twitter.com", 
			"User-Agent: {$this->app_name}",
			"Authorization: Bearer {$this->access_token}",
			"Accept-Encoding: gzip"
		);

		curl_setopt( $this->curl, CURLOPT_URL, $uri ); 
		curl_setopt( $this->curl, CURLOPT_HTTPHEADER, $headers ); 
		curl_setopt( $this->curl, CURLOPT_HTTPGET, 1 );
		curl_setopt( $this->curl, CURLOPT_ENCODING, "gzip" );
		curl_setopt( $this->curl, CURLOPT_RETURNTRANSFER, true );

		$response = curl_exec( $this->curl );
		$body = json_decode( $response );

		curl_close( $this->curl );

		return $body;
	}

	/**
	 *	@function	make_encoded_bearer_token
	 *	@params 	<consumer_key:string>
	 *				<consumer_secret:string>
	 *	@return 	<void>
	 */
	private function make_encoded_bearer_token( $consumer_key, $consumer_secret ) { $this->encoded_bearer_token = base64_encode( urlencode( $consumer_key ).":".urlencode( $consumer_secret ) ); }
	
	/**
	 *	@function	curl_reset
	 *	@params 	<void>
	 *	@return 	<void>
	 */
	private function curl_reset( ) { $this->curl = curl_init( ); }

	/**
	 *	@function	set_app_name
	 *	@params 	<name:string>
	 *	@return 	<void>
	 */
	public function set_app_name( $name ) { $this->app_name = $name; }

	/**
	 *	@function	get_access_token
	 *	@params 	<void>
	 *	@return 	<string>
	 */
	public function get_access_token( ) { return json_encode( array( "token_type" => "bearer", "access_token" => $this->access_token ) ); }

	/**
	 *	@function	set_access_token
	 *	@params 	<token:string>
	 *	@return 	<void>
	 */
	public function set_access_token( $token ) { $this->access_token = $token; }
}

?>