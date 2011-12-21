EGCal - A Google Calendar Extension for Yii
=============

About
-----

EGCal is a simple extension that enables Yii Applications to communicate with Google Calendar.

How it Works
------------

EGCal works by making a autorization request to Google Calendar via ClientLogin. All subsequent requests are then
made through a single connection.

Purpose
-------
One of the projects I was working on required me to retrieve events from Google Calendar. I didn't see any good classes that were simple or intuitive to use, so I decided to build my own. The purpose of this class is to provide a simple interface between the programmer and Google Calendar. Once I have completed this class in it's entirety I may consider building an OAuth version of the class to use with Google Calendar API 3.0.

Requirements
------------
PHP 5.3+

Yii Framework 1.1.x

php-curl installed

Usage
=====

### Importing the Class

	~~~
	[php]
	Yii::import('application.extensions.EGCal.EGCal');
	~~~

### Instantiation

You have a couple of options here. All you need to do to get it working is to call:

	~~~
	[php]
	$cal = new EGCal('username@gmail.com', 'gmail-password');
	~~~

If you would like EGCal to provide debugging text:
	
	~~~
	[php]
	$cal = new EGCal('username@gmail.com', 'gmail-password', TRUE);
	~~~

By default, EGCal uses your application name (Yii::app()->name) for the source request identifier. This can be easily altered by calling (with debugging disable)

	~~~
	[php]
	$cal = new EGCal('username@gmail.com', 'gmail-password', FALSE, 'companyName-applicationName-versionID');
	~~~

Google Calendar Requirements
----------------------------

Calendars (calendar id's) must be either own be owned by the user or granted read/write access to the calendar.	
Timezones should also be appropriatly set within Google Calendar.		

Retrieving Events
-----------------

Retrieving events can be done by calling find() as such:
            
        ~~~
	[php]
	$response = $cal->find(
		array(
			'min'=>date('c', strtotime("8 am")), 
			'max'=>date('c', strtotime("5 pm")),
			'limit'=>50,
			'order'=>'a',
			'calendar_id'=>'#stardate@group.v.calendar.google.com'
		)
	);
	~~~

The fields min, max, and calendar_id are required.
The fields limit and order are option, and default to 50, and ascending respectivly.

The min and max times should be in ISO 8601 date format [ date('c') ], and may require a timezone offset.


### Adjusting for Timezone

Sometimes events may appear to be several hours off. This is due to the timezone of your Google Calendar differing from that of your PHP System Time.
This can be easily corrected by modifying the calendar settings, and/or offseting the min and max request times by a timezone offset.

### Response
Responses will take the form of a php array, containing the total number of events , and an array of events.
Each event will contain the calendar ID, the start and end times, and the title of the event.

For example:

	~~~
	[php]
	Array
	(
	    [totalResults] => z
	    [events] => Array
		(
		    [0] => Array
		        (
		            [id] => n9af6k7fpbh4p90snih1vfe1bc
		            [start] => 2011-12-19T08:20:00.000-06:00
		            [end] => 2011-12-19T08:40:00.000-06:00
		            [title] => Meeting with Josh
		        )

		    [1] => Array
		        (
		            [id] => ux5ohbtgbr0u2tk6cyivsi8tj9
		            [start] => 2011-12-19T15:30:00.000-06:00
		            [end] => 2011-12-19T17:00:00.000-06:00
		            [title] => Meeting with Jane
		        )
			
		    [...]
		)
	)
	~~~
	
Creating Events
---------------

### Single Events

Single events can be created with the following format

	~~~
	[php]
	$response = $cal->create(
	    array(
		'start'=>date('c', strtotime("4 pm")), 
		'end'=>date('c', strtotime("5 pm")),
		'title'=>'Appointment with Jane',
		'details'=>'Talk about business proposal',
		'location'=>'My Office',
		'calendar_id'=>'#stardate@group.v.calendar.google.com'
	    )
	);
	~~~

#### Adjusting for Timezone

As with retrieving events, you may have to offset your start and end times depending on your timezone.

### Response

An unsuccessful response will return an empty array

A successful response will look as follows:

	~~~
	[php]
	Array
	(
	    [id] => GoogleCalendarID
	    [title] => Appointment with Jane
	    [details] => Talk about business proposal
	    [location] => My Office
	    [start] => 2011-12-19T16:00:00.000-06:00
	    [end] => 2011-12-19T17:00:00.000-06:00
	)
	~~~

### Quick Events

### Repeating Events


Updating Events
---------------


Deleting Events
---------------

Single events can be deleted by calling the delete method. Deleting an event requires both the specific event_id you with to delete, and the calendar_id that event belongs to.

For example:

	~~~
	[php]
	$response = $cal->delete(
		array(
			'id'=>'9u5fj46m0fcd8scb3dohds2kso',
			'calendar_id'=>'#stardate@group.v.calendar.google.com'
		)
	);
	~~~

The delete method will return true if the event was deleted, and false if the event could not be deleted. If logging is enabled, the response code and message from Google will be provided.
