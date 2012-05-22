<?php

/**
 *  @class     GCal
 *  @author    Charles R. Portwood II  <charlesportwoodii@ethreal.net>
 *                                     <crp07c@acu.edu>
 *  @version   1.0.0
 *  @package   EGCal (An extension for Yii)	
 *
 *  //---------------------------------------------------------------------//
 *  Public Methods
 *  isConnected()		// Returns bool if connection was successful
 *  getResponseCode()		// Returns HTTP response code from last request
 *  find()			// Finds a list of events based on an id, and a date range
 *  delete()                    // Deletes a single event
 *  create()                    // Creates a single event
 *  update()                    // Updats a single event
 **/
class EGCal
{
	// Calendar ID that you wish to connect to
	private $calendar_id;
	
	// Username of the calendar owner (example@gmail.com)
	private $username;
	
	// Auth Password
	private $password;
	
	// Source
	private $source;
	
	// Reporting Level
	private $level;
	
	// Authentication Data
	private $auth;
	
	// Connection Status
	private $connected;
	
	// Response Code
	private $response_code;
	
	// CURL Headers
	private $headers;
	
	/**
	 *  Constructor. Called on new GCal(). Sets up initial connection
	 *
	 *  @param string $username 	Google Username
	 *  @param string $password	Google Password
	 *  @param string $source 	Identifies your client application for support purposes. This string should take the form of your companyName-applicationName-versionID.
	 *				https://code.google.com/googleapps/domain/calendar_resource/docs/1.0/calendar_resource_developers_guide_protocol.html#client_login
	 *  @param mixed $level		Indicates the log level to use, 0, 1 (true, false)
	 *  @call new GCal('username', 'password')
	 *  @call new GCal('username', 'password', 1)
	 *  @call new GCal('username', 'password', 0, 'companyName-appName-versionId')
	 *  @return void
	 **/
	public function __construct($username, $password, $level = 0, $source = NULL)
	{		
		if ($source == NULL)
		{
			$this->source = str_replace(' ', '_',Yii::app()->name);
		}
		else
		{
			$this->source = $source;
		}
		
		// Set the warning level
		$this->level = $level;
		
		// Perform the connection
		$this->connected = $this->connect($username, $password);
				
	}
	
	/**
	 *  Performs Connection to Google Calendar
	 *
	 *  @param string $username
	 *  @param string $username
	 *  @return bool $this->connection
	 **/
	private function connect($username=NULL, $password=NULL)
	{
		$this->connected = false;
		$this->username = $username;
		
		$this->password = $password;
		
		$content = array(
			'accountType' => 'HOSTED_OR_GOOGLE',
			'Email' => $this->username,
			'Passwd' => $this->password,
			'source' => $this->source,
			'service' => 'cl'
		);
			
		Yii::import('application.extensions.EGCal.Curl');
		$curl = new Curl('https://www.google.com/accounts/ClientLogin');	
		$response = $curl->run('POST', $content);
		
		$this->response_code = $curl->getStatus();
		
		if ($curl->getStatus() == '403')
		{			
			if ($this->level == TRUE)
			{
				echo 'Could not establish connection to Google Calendar.' . "\n";
				echo 'Response Code: ' . $curl->getStatus() . "\n";
			}
			
			return false;
		}
		else
		{
			parse_str(str_replace(array("\n", "\r\n"), '&', $response), $response);
			$this->auth = $response['Auth'];
			$this->setHeaders();
			return true;
		}		
	}
	
	/**
	 *  Prepares the headers one time so we do not keep re-creating the headers
	 *
	 **/
	private function setHeaders($ifMatch = FALSE, $contentLength=NULL)
	{
		$this->headers = array(
			    "Authorization: GoogleLogin auth=" . $this->auth,
			    "GData-Version: 2.6",
			    'Content-Type: application/json',
			);
		
		if ($ifMatch)
		{
			$this->headers[] = 'If-Match: *';
		}
		
		if ($contentLength != NULL)
		{
			$this->headers[] = 'Content-Length: ' . $contentLength;
		}
	}
	/**
	 *  Simple debug helper
	 *
	 *  @param mixed $options
	 *  @return print_r($option)
	 **/
	private function debug($options)
	{
		echo '<pre>';
		print_r($options);
		echo '</pre>';
	}
	
	/**
	 *  Public method to retrieve connection status
	 *
	 *  @return bool $this->connected
	 **/
	public function isConnected()
	{
		return $this->connected;
	}
	
	/**
	 *  Public method to retrieve the last response code
	 *
	 *  @return int/string $this->response_code
	 **/
	public function getResponseCode()
	{
		return $this->response_code;
	}
	
	/**
	 *  Method to find events based upon a date range and calendar_id
	 *
	 *  @param array $options
	 *	@subparam datetime $min
	 *	@subparam datetime $max
	 *	@subparam string $order 	(a,d) (Ascending, Descending)
	 *	@subparam int $limit 	(50)
	 *	@subparam string 	$calendar_id
	 *
	 *  Example $options
	 *	array(
	 *		'min'=>date('c', strtotime("8 am")), 
	 *		'max'=>date('c', strtotime("5 pm")),
	 *		'limit'=>5,
	 *		'order'=>'d',
	 *		'calendar_id'=>'en.usa#holiday@group.v.calendar.google.com'
	 *	)
	 *
	 *  @return array $results
	 **/
	public function find($options=array())
	{
		if ($this->isConnected())
		{
			if (!empty($options) && is_array($options) && $options['calendar_id'] != NULL)
			{
				// Parse the options to a usable format
				$min = (!isset($options['min'])) ? date('Y-m-d\T00:i:s') : date('Y-m-d\TH:i:s', strtotime($options['min']));
				$max = (!isset($options['max'])) ? date('Y-m-d\T23:59:59') : date('Y-m-d\TH:i:s', strtotime($options['max']));
				$limit = (!isset($options['limit'])) ? 50 : $options['limit'];
				$order = (!isset($options['order'])) ? 'a' : $options['order'];
				
				$calendar_id = $options['calendar_id'];
				
				// Build the Calendar URL
				$url = "https://www.google.com/calendar/feeds/$calendar_id/private/full?orderby=starttime&sortorder=$order&singleevents=true&start-min=$min&start-max=$max&max-results=$limit&alt=jsonc";
				
				// Load the CURL Library
				Yii::import('application.extensions.GCal.Curl');
				$curl = new Curl($url);
								
				// Set the headers
				$curl->setHeader($this->headers, $url, false);
				
				// Make the request
				$response = json_decode($curl->run('GET'),true);
				
				// Set the response code for debugging purposes
				$this->response_code = $curl->getStatus();
				
				// We should receive a 200 response. If we don't, return a blank array
				if ($this->response_code != '200')
					return array();
				
				// Build the results array
				$results = array(
					'totalResults'=>$response['data']['totalResults'],
					'events'=>array()
				);
		
				// Handles the case of there being no items in the last response
				if ($response['data']['totalResults'] != 0)
				{	
					// Parse the response, and use it to populate our results
					foreach ($response['data']['items'] as $item)
					{
						$tmp = array(
							'id' => $item['id'],
							'start' => $item['when'][0]['start'],
							'end' => $item['when'][0]['end'],
							'title' => $item['title'],
							//'location' => $item['location']
						);
						$results['events'][] = $tmp;
					}
				}
				// Return the results as an array
				return $results;
		
			}
			else
			{
				// Debug Output
				if ($this->level == TRUE)
				{
					if (empty($options))
					{
						echo 'No options were specified' . "\n";
					}
					
					if ($options['calendar_id'] == NULL)
					{
						echo 'Calendar ID is not set.' . "\n";
					}
				}
				
				return array();
			}
		}
		else
		{
			// Debug Output
			if ($this->level == TRUE)
			{
				echo 'Cannot complete query. No connection has been established.' . "\n";
			}
			return array();
		}
	}
	
	/**
	 *  Method to create new events
	 *  @param int type
	 *     $type = 1 		Single Event
	 *     $type = 2		Quick Events
	 *     $type = 3		Recurring Events
	 *
	 *  @param array $options | $type == 1
	 *     @subparam datetime $start
	 *     @subparam datetime $end
	 *     @subparam string $title
	 *     @subparam string $location
	 *     @subparam string $details
	 *     @subparam string $calendar_id
	 *
	 *  Example Options for Single Events
	 *	    array(
	 *		'start'=>date('c', strtotime("8 am")), 
	 *		'end'=>date('c', strtotime("5 pm")),
	 *		'title'=>'Meeting with Jane',
	 *		'details'=>'Discuss business plan',
	 *		'location'=>'My Office',
	 * 		'calendar_id'=>'en.usa#holiday@group.v.calendar.google.com'
	 *	    )
	 *
	 *  @return array
	 **/
	public function create($options, $type = 1)
	{
		if ($this->isConnected())
		{
			// Verify the options are properly  set
			if (!empty($options) && is_array($options))
			{
		
				// Verify the required fields are set to something
				if (!isset($options['title']))
				{
					if ($this->level == TRUE)
					{
						echo 'No title was specified for event creation' . "\n";
					}
					return array();
				}
			
				if (!isset($options['start']))
				{
					if ($this->level == TRUE)
					{
						echo 'No start time specified for event creation' . "\n";
					}
					return array();
				}
			
				if (!isset($options['end']))
				{
					if ($this->level == TRUE)
					{
						echo 'No end time specified for event creation' . "\n";
					}
					return array();
				}
			
				// End isset validation
			
				// Retrieve and set the calendar_id and URL
				$calendar_id = $options['calendar_id'];
			
				$url = "https://www.google.com/calendar/feeds/$calendar_id/private/full?alt=jsonc";
			
				// Load the CURL Library
				Yii::import('application.extensions.GCal.Curl');
				//$curl = new Curl($url);
				
				// Create a blank data set
				$data = array();
				
				// If we are creating a single event, or doing anything else not specified below
				if ($type == 1 || $type > 3)
				{
					// Build the data query
					$data = array(
						'data'=>array(
							'title'=>$options['title'],
							'details'=>isset($options['details']) ? $options['details'] : '',
							'location'=>isset($options['location']) ? $options['location'] : '',
							'status'=>isset($options['status']) ? $options['status'] : '',
							'when'=>array(array(
								'start'=>date('c', strtotime($options['start'])),
								'end'=>date('c', strtotime($options['end']))
							))
						)
					);					
				}
				/*
				else if ($type == 2) // Quick Events
				{
			
				}
				else if ($type == 3) // Recurring Events
				{
					{
					  "data": {
					    "recurrence": "DTSTART;VALUE=DATE:20100505\r\nDTEND;VALUE=DATE:20100506\r\nRRULE:FREQ=WEEKLY;BYDAY=Tu;UNTIL=20100904\r\n"
					  }
					}
					
					$data = array(
						'data'=>array(
							'title'=>$options['title'],
							'details'=>isset($options['details']) ? $options['details'] : '',
							'location'=>isset($options['location']) ? $options['location'] : '',
							'status'=>isset($options['status']) ? $options['status'] : '',
							'recurrence'=>"DTSTART;VALUE=DATE:" . date('Y-m-d', strtotime($options['start'])) . "\r\nDTEND;VALUE=DATE:" . date('Y-m-d', strtotime($options['end'])). "\r\nRRULE:FREQ=weekly;BYDAY=Tu;UNTIL=" . date('2012-01-30') . "\r\n"
						)
					);
				}
				*/
								
				// Set the initial headers
				//$curl->setHeader($this->headers, $url, TRUE, TRUE, 30);
					
				// Make an initial request to get the GSESSIONID			
				//$response = $curl->run('POST', json_encode($data));
								
				//$last_url =  $curl->getLastURL();			// Error code is 200, but is preceeded by a 301 for the gSessionId
				//unset($curl);
				
				// Rebuild the Object to create to create the actual create Request
				
				$curl = new Curl($url);
				$curl->setHeader($this->headers, $url, TRUE);
					
				// Make an initial request to get the gSessionId	
				$response = json_decode($curl->run('POST', json_encode($data)), TRUE);

				error_reporting(0);
				return array(
					'id'=>$response['data']['id'],	
					'title'=>$response['data']['title'],
					'details'=>$response['data']['details'],
					'location'=>$response['data']['location'],
					'start'=>$response['data']['when'][0]['start'],
					'end'=>$response['data']['when'][0]['end']
				);
			}
			else
			{
				if ($this->level == TRUE)
				{
					echo 'Options are not properly set' . "\n";
					return array();
				}
			}
		}
		else
		{
			if ($this->level == TRUE)
			{
				echo 'No connection has been started' . "\n";
				return array();
			}
		}
	}
	
	/**
	 *  Method to update events
	 *  @param array $options
	 *	@subparam string   $id
	 *	@subparam bool     $canEdit
	 *	@subparam string   $title
	 *	@subparam string   $details
	 *	@subparam string   $location
	 *	@subparam datetime $start
	 *	@subparam datetime $end
	 *  Example Options for Update
	 *	    array(
	 *              'id'=>'calendar_id'
	 *		'start'=>date('c', strtotime("8 am")), 
	 *		'end'=>date('c', strtotime("5 pm")),
	 *		'title'=>'Meeting with Jane',
	 *		'details'=>'Discuss business plan',
	 *		'location'=>'My Office',
	 * 		'calendar_id'=>'en.usa#holiday@group.v.calendar.google.com'
	 *	    )
	 **/
	public function update($options=array())
	{
		if (!empty($options))
		{
		
			// Begin Validation
			if (!isset($options['id']))
			{
				if ($this->level == TRUE)
				{
					echo 'ID was not set' . "\n";
				}
				return array();
			}
			
			if (!isset($options['calendar_id']))
			{
				if ($this->level == TRUE)
				{
					echo 'Calendar ID was not set' . "\n";
				}			
				return array();
			}
			
			// End isset validation
			
			// Update by delete & create
			$this->delete($options);
			return $this->create($options);
			
			/*
			// Retrieve and set the calendar_id and URL
			$calendar_id = $options['calendar_id'];
			$event_id = $options['id'];
			
			$url = "https://www.google.com/calendar/feeds/$calendar_id/private/full/$event_id?alt=jsonc";
			
			$data = array(
				'data'=>array(
					'id'=>$options['id'],
					'title'=>$options['title'],
					'details'=>$options['details'],
					'location'=>$options['location'],
					'when'=>array(array(
						'start'=>date('Y-m-d\TH:i:s', strtotime($options['start'])),
						'end'=>date('Y-m-d\TH:i:s', strtotime($options['end']))
					))
					
				)
			);
			
			// Load the CURL Library
			Yii::import('application.extensions.GCal.Curl');
			$curl = new Curl($url);
			
			// Set the headers for an If-Match Request
			$this->setHeaders(TRUE, strlen(json_encode($data)));
			
			// Set the header for the CURL request
			$curl->setHeader($this->headers, $url, FALSE);
			
			$response = json_decode($curl->run('PUT', json_encode($data)), true);
			
			return $response;
			*/
			
		}
		else
		{
			if ($this->level == TRUE)
			{
				echo 'Event ID was not set' . "\n";
				return array();
			}
		}
	}
	
	/**
	 *  Method to delete events
	 *  @param array $options
	 *
	 *
	 *  @return bool response
	 *	TRUE if the delete was successful, FALSE otherwise
	 **/
	public function delete($options=array())
	{
		if (!empty($options))
		{
		
			// Begin Validation
			if (!isset($options['id']))
			{
				if ($this->level == TRUE)
				{
					echo 'ID was not set' . "\n";
				}
				return false;
			}
			
			if (!isset($options['calendar_id']))
			{
				if ($this->level == TRUE)
				{
					echo 'Calendar ID was not set' . "\n";
				}			
				return false;
			}
			
			// End isset validation
			
			// Retrieve and set the calendar_id and URL
			$calendar_id = $options['calendar_id'];
			$event_id = $options['id'];
			
			$url = "https://www.google.com/calendar/feeds/$calendar_id/private/full/$event_id?alt=jsonc";
		
			// Load the CURL Library
			Yii::import('application.extensions.GCal.Curl');
			$curl = new Curl($url);
			
			// Set the headers for an If-Match Request
			$this->setHeaders(TRUE);
			
			// Set the header for the CURL request
			$curl->setHeader($this->headers, $url, false);
			
			// Reset the Headers
			$this->setHeaders();
			
			// Make the request
			$response = json_decode($curl->run('DELETE'),true);
			
			// Set the response code for debugging purposes
			$this->response_code = $curl->getStatus();
			
			if ($this->response_code == 200 && $response == NULL)
			{
				return true;
			}
			else
			{
				if ($this->level == TRUE)
				{
					echo 'Deletion failed with response code: ' . $this->response_code . "\n";
					echo 'Message: ' . $response['error']['message'];
				}
			}
			return false;
		}
		else
		{
			if ($this->level == TRUE)
			{
				echo 'Event ID was not set' . "\n";
				return false;
			}
		}
	}	
}

?>