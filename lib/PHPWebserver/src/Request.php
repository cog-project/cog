<?php namespace ClanCats\Station\PHPServer;

use ClanCats\Station\PHPServer\Exception;

class Request 
{
	/**
	 * The request method
	 *
	 * @var string 
	 */
	protected $method = null;
	
	/**
	 * The requested uri
	 *
	 * @var string
	 */
	protected $uri = null;
	
	/**
	 * The request params
	 *
	 * @var array
	 */
	public $parameters = [];

	public $post = [];

	/**
	 * The request params
	 *
	 * @var array
	 */
	protected $headers = [];
	
	/**
	 * Create new request instance using a string header
	 *
	 * @param string 			$header
	 * @return Request
	 */
	public static function withHeaderString( $header )
	{
echo "$header\n";
		$lines = explode( "\n", $header );

		// payload
		if(count($lines) == 1) {
			parse_str($header,$post);
			return $post;
		}
		
		// method and uri
		list( $method, $uri ) = explode( ' ', array_shift( $lines ) );
		
		$headers = [];

		$post = [];
		
		foreach( $lines as $k => $line )
		{
			// clean the line
			$line = trim( $line );
			
			if ( strpos( $line, ': ' ) !== false )
			{
				list( $key, $value ) = explode( ': ', $line );
				$headers[$key] = $value;
			} elseif ($k == count($lines) - 1) {
				parse_str($line,$post);
			}
		}	
		
		// create new request object
		return new static( $method, $uri, $headers, $post);
	}
	
	/**
	 * Request constructor
	 *
	 * @param string 			$method
	 * @param string 			$uri
	 * @param array 			$headers
	 * @return void
	 */
	public function __construct( $method, $uri, $headers = [], $post = [] ) 
	{
		$this->headers = $headers;
		$this->post = $post;
		$this->method = strtoupper( $method );
		
		// split uri and parameters string
		@list( $this->uri, $params ) = explode( '?', $uri );

		// parse the parmeters
		parse_str( $params, $this->parameters );

		if($this->method == 'POST' && !empty($post)) {
			// parse the post params
			parse_str( $post, $this->post );
		}
	}
	
	/**
	 * Return the request method
	 *
	 * @return string
	 */
	public function method()
	{
		return $this->method;
	}
	
	/**
	 * Return the request uri
	 *
	 * @return string
	 */
	public function uri()
	{
		return $this->uri;
	}
	
	/**
	 * Return a request header
	 *
	 * @return string
	 */
	public function header( $key, $default = null )
	{
		if ( !isset( $this->headers[$key] ) )
		{
			return $default;
		}
		
		return $this->headers[$key];
	}
	
	/**
	 * Return a request parameter
	 *
	 * @return string
	 */
	public function param( $key, $default = null )
	{
		if ( !isset( $this->parameters[$key] ) )
		{
			return $default;
		}
		
		return $this->parameters[$key];
	}

	public function post( $key, $default = null )
	{
		if ( !isset( $this->post[$key] ) )
		{
			return $default;
		}
		
		return $this->post[$key];
	}
}