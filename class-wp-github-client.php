<?php
		
/**
 * Wp_Github_Client
 *
 * @ver 1.0.0
 */
class Wp_Github_Client {
		
	var // Default query args
		$query_args = array(
			'query'		=>	'',
			'method'	=>	'GET',
			'data'		=>	''
		),
		
		// If there are errors stop queries
		$has_error = false;
	
				// Token
	 protected 	$bearer_token,
			
				// Default options
				$args = array(
					'client_id'		=>	'client_id',
					'client_secret'	=>	'client_secret',
					'scope'			=>	'gist',
					'redirect_uri'	=>	'',
					'show_login'	=>	true
				);
	
	/**
	 * Constructor
	 * 
	 * @var Array $args Set client_id, client_secret and optionally scope, redirect_uri & show_login
	 */
	function __construct( $args = array() ) {
		
		// Merge options array with defaults
		if ( is_array( $args ) && !empty( $args ) )
			$this->args = array_merge( $this->args, $args );
		
		// If the bearer token isset we are done here
		if ( $this->bearer_token = get_option( 'github_bearer_token' ) )
			return;
		
		// If no redirect uri is set let's get the current one
		if ( empty( $this->args['redirect_uri'] ) ) 
			$this->args['redirect_uri'] = $this->current_page_url();
		
		// If no bearer token is set and the code is sent lets get the bearer token
		if ( isset( $_GET['code'] ) )
			return $this->bearer_token = $this->get_bearer_token( $_GET['code'] );
			
		// Show the login text
		if ( $this->args['show_login'] )
			$this->the_login();
		
		// Bearer token is not set so...
		$this->has_error = true;
		
	}
	
	/**
	 * Get access token from Github Oauth API
	 * 
	 * @var string $code Temporary code used to get the Bearer Token
	 *
	 * @return string Bearer Token
	 */
	protected function get_bearer_token( $code ) {

		$url = 'https://github.com/login/oauth/access_token';
		
		$args = array(
			'method'		=>	'POST',
			'timeout'		=>	45,
			'redirection'	=>	5,
			'httpversion'	=>	'1.0',
			'blocking'		=>	true,
			'headers'		=>	array(
				'Accept'		=>	'application/json'
			),
			'body' => array( 
				'client_id'		=>	$this->args['client_id'], 
				'client_secret'	=>	$this->args['client_secret'],
				'code'			=>	$code
				),
			'cookies'		=>	array()
		);
		
		$result = wp_remote_post( $url, $args );
		
		if ( is_wp_error( $result ) || 200 != $result['response']['code'] || false !== strpos( $result['body'], 'bad_verification_code' ) ){
				
			$this->has_error = true;
			
			return $this->bail( __( 'Can\'t get the bearer token, check your credentials', 'wp_github_client' ), $result );
			
		}
		
		$data = json_decode( $result['body'] );
			
		update_option( 'github_bearer_token', $data->access_token );
		
		return $data->access_token;
		
	}
	
	/**
	 * Return the login uri
	 * 
	 * @return string Login uri
	 */
	public function get_login_uri() {
		
		$url = 'https://github.com/login/oauth/authorize';
		
		$param = array(
			'client_id'		=>	$this->args['client_id'],
			'redirect_uri'	=>	$this->args['redirect_uri'],
			'scope'			=>	$this->args['scope']
		);
		
		return add_query_arg( $param, $url );
	}
	
	/**
	 * Echoes the login url
	 */
	public function the_login( ) {
		
		echo '<p>' . sprintf( __( '<a href="%s" title="Give access to Github" rel="nofollow">Click here</a> to give this App access to your Github account.', 'wp_github_client' ), $this->get_login_uri() ) . '</p>';
		
	}
	
	/**
	 * Check if the bearer token is set
	 * 
	 * @return Bool Bearer Token isset ?!?
	 */
	public function is_authorized() {
		
		return (bool) $this->bearer_token;
		
	}
	
	/**
	 * Parse input data
	 * 
	 * If a string is given uses it as the query
	 * 
	 * @var String $query Input data
	 * @var String $method HTTP method
	 * @var Array $data Data to be added to query var or post
	 * 
	 * @return Array Data ready for the query
	 */
	protected function parse_input_data( $query, $method, array $data = array() ) {
		
		return array(
			'query'		=>	trim( $query ),
			'method'	=>	$method,
			'data'		=>	$data
		);
		
	}
	
	/**
	 * Helper function for query GET
	 * 
	 * @var String $query Api parameters
	 * @var Array $data An array of data to add to the query args
	 *
	 * @return Mixed Query result
	 */
	public function get( $query, $data = array() ) {
		
		$args = $this->parse_input_data( $query, 'GET', $data );
		
		return $this->query( $args );
		
	}
	
	/**
	 * Helper function for query PUT
	 *
	 * @var String $query Api parameters
	 * @var Array $data An array of data to post
	 *
	 * @return Mixed Query result
	 */
	public function put( $query, $data = array() ) {
		
		$args = $this->parse_input_data( $query, 'PUT', $data );
		
		return $this->query( $args );
		
	}
	
	/**
	 * Helper function for query DELETE
	 *
	 * @var String $query Api parameters
	 * @var Array $data An array of data to post
	 * 
	 * @return Mixed Query result
	 */
	public function delete( $query, $data = array() ) {
		
		$args = $this->parse_input_data( $query, 'DELETE', $data );
		
		return $this->query( $args );
		
	}
	
	/**
	 * Helper function for query POST
	 *
	 * @var String $query Api parameters
	 * @var Array $data An array of data to post
	 * 
	 * @return Mixed Query result
	 */
	public function post( $query, $data = array() ) {
		
		$args = $this->parse_input_data( $query, 'POST', $data );
		
		return $this->query( $args );
		
	}
	
	/**
	 * Helper function for query PATCH
	 *
	 * @var String $query Api parameters
	 * @var Array $data An array of data to post
	 * 
	 * @return Mixed Query result
	 */
	public function patch( $query, $data = array() ) {
		
		$args = $this->parse_input_data( $query, 'PATCH', $data );
		
		return $this->query( $args );
		
	}
	
	/**
	 * Main query method
	 * 
	 * @var Array $args Set method, query and data
	 * 
	 * @return Mixed Query result
	 */
	public function query( array $args ) {
			
		$this->query_args = array_merge( $this->query_args, $args );
		
		if ( $this->has_error || empty( $this->query_args['query'] ) )
			return false;
		
		// Arguments 
		$post_args = array(
			'method'		=> 	$this->query_args['method'],
			'timeout'		=> 	45,
			'redirection'	=> 	5,
			'httpversion'	=> 	'1.0',
			'blocking'		=> 	true,
			'headers'		=> 	array(
				'Accept'		=>	'application/json'
			),
			'body'			=>	'',
			'cookies'		=>	array()
		);
		
		// Base API url
		$url = 'https://api.github.com';
		
		// If the query doesn't contain the full url just prepend it
		if ( 0 !== strpos( $this->query_args['query'], $url ) )
			$url = $url . $this->query_args['query'];
		
		// GET OR POST ?!?
		if ( 'GET' == $this->query_args['method'] ){
			
			$data = $this->query_args['data'];
			$data['access_token'] = $this->bearer_token;
			$url = add_query_arg( $data, $url );
			
		} else {
			
			$post_args['body'] = json_encode( $this->query_args['data'] );
			$post_args['headers']['Authorization'] = 'token ' . $this->bearer_token;
			
			// For PUT requests with no body attribute, be sure to set the Content-Length header to zero.
			if ( 'PUT' == $this->query_args['method'] && empty( $post_args['body'] ) )
				$post_args['headers']['Content-Length'] = 0;
			
		}
		
		// Send request
		$result = wp_remote_post( $url , $post_args  );
		
		// On error return false
		if ( is_wp_error( $result ) || ( 200 != $result['response']['code'] && 204 != $result['response']['code'] ) )
			return $this->bail( __( 'Bearer Token is good, check your query or scope.', 'wp_github_client' ), $result );
		
		// For put and delete return Bool
		if ( in_array( $this->query_args['method'] , array( 'DELETE', 'PUT' ) ) ){
		
			if ( 204 == $result['response']['code'] ) 
				return true;
			
			return false;
		
		}
		
		// For the other requests return the content
		return json_decode( $result['body'] );
		
	}
	
	/**
	 * Current page url
	 * 
	 * Support function to retrieve current page uri
	 * if not set by the user in redirect_uri
	 * 
	 * @return string Current uri
	 */
	protected function current_page_url() {
		
		$pageURL = 'http';
		
		if( isset( $_SERVER["HTTPS"] ) && 'on' == $_SERVER["HTTPS"]  )
			$pageURL .= "s";
		
		$pageURL .= "://" . $_SERVER["SERVER_NAME"];
		
		if ( $_SERVER["SERVER_PORT"] != "80" )
			$pageURL .= ":" . $_SERVER["SERVER_PORT"];
		
		$pageURL .= $_SERVER["REQUEST_URI"];
		
		return $pageURL;

	}
	
	/**
	 * Let's manage errors
	 *
	 * WP_DEBUG has to be set to true to show errors
	 *
	 * @param string $error_text Error message
	 * @param string $error_object Server response or wp_error
	 */
	protected function bail( $error_text, $error_object = '' ) {
		
		if ( is_wp_error( $error_object ) ){
			$error_text .= ' - Wp Error: ' . $error_object->get_error_message();
		} elseif ( !empty( $error_object ) && isset( $error_object['response']['message'] ) ) {
			$error_text .= ' ( Response: ' . $error_object['response']['message'] . ' )';
		}
		
		trigger_error( $error_text , E_USER_NOTICE );
		
		return false;
	
	}
	
} // class Wp_Github_Client
