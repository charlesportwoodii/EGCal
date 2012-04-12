<?php

class Curl
{
	// Curl Objcet
	private $curl;
	
	// Request URL
	private $url;
	
	// Curl error number
	private $error_code;
	
	// HTTP Respponse Code
	private $status;
	
	// HTTP Response String
	private $error_string;
	
	// Last effective url
	private $last_url;
	
	/**
	 *  PHP5 Constructor
	 *  @param string $url
	 * 	The URL to be requested
	 **/
	public function __construct($url)
	{
		$this->curl = curl_init($url);
	}
	
	/**
	 *  Allows for custom headers to be sent before the request is made
	 *
	 *  @param array $headers
	 *	Headers to be sent with the request
	 *
	 *  @param string $url
	 *	Modified URL if it needs to be changed from the original request
	 *
	 *  @param bool $post
	 *	Whether we are performing a GET or POST requst, defaults to GET
	 *
	 *  @param bool $follow
	 *	Whether we are to follow redirects
	 *
	 *  @param int $redirects
	 *	Number of redirects to follow before stopping
	 *
	 **/
	public function setHeader($headers=array(), $url=NULL, $post=NULL, $follow = TRUE, $redirects = 30)
	{
		curl_setopt($this->curl, CURLOPT_URL, $url == NULL ? $this->url : $url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		if ($post != NULL)
		{
			curl_setopt($this->curl, CURLOPT_POST, $post);
		}
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $follow); // follow redirects
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, $redirects); // maximum number of redirects
	}
	
	public function run($method, $content = NULL)
	{
		if ($method != 'POST' && $method != 'GET')
		{
			curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
		}
		else
		{
			curl_setopt($this->curl, CURLOPT_POST, ($method == 'POST') ? true : false);
		}
		
		if ($content != NULL)
		{
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $content);
		}
			
		curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		
		$response =  curl_exec($this->curl);
		
		$this->error_code = curl_errno($this->curl);
		$this->status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
		$this->error_string = curl_error($this->curl);
		$this->last_url = curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);
		
		curl_close($this->curl);
		
		return $response;
		
		
	}
	
	/**
	 *  Retrieves the CURL error code retrieved from the last request
	 *  @return int $this->error_code
	 **/
	public function getErrorCode()
	{
		return $this->error_code;
	}
	
	/**
	 *  Retrieves the HTTP status code from the last request
	 *  @return int $this->status
	 **/
	public function getStatus()
	{
		return $this->status;
	}
	
	/**
	 *  Retrieves the error string from the last request
	 *  @return int $this->error_string
	 **/
	public function getErrorString()
	{
		return $this->error_string;
	}
	
	/**
	 *  Retrieves the last URL CURL processed
	 *  @return int $this->last_url
	 **/
	public function getLastURL()
	{
		return $this->last_url;
	}

}

?>
