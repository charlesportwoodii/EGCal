<?

class Curl
{
	
	private $curl;
	
	private $url;
	
	public $error_code;
	
	public $status;
	
	public $error_string;
	
	public $last_url;
	
	public function __construct($url)
	{
		$this->curl = curl_init($url);
	}
	
	public function setHeader($headers=array(), $url, $post=false, $follow = TRUE, $redirects = 30)
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($this->curl, CURLOPT_POST, $post);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, $follow); // follow redirects
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, $redirects); // maximum number of redirects
	}
	
	public function run($method, $content = NULL)
	{
		curl_setopt($this->curl, CURLOPT_POST, ($method == 'POST') ? true : false);
		
		if ($content != NULL)
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $content);
			
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

}

?>
