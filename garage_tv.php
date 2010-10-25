<?php
/**
 * GarageTV class
 *
 * This source file can be used to communicate with GarageTV (http://garagetv.be)
 *
 * The class is documented in the file itself. If you find any bugs help me out and report them. Reporting can be done by sending an email to php-garage-tv-bugs[at]verkoyen[dot]eu.
 * If you report a bug, make sure you give me enough information (include your code).
 *
 * License
 * Copyright (c) 2008, Tijs Verkoyen. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products derived from this software without specific prior written permission.
 *
 * This software is provided by the author "as is" and any express or implied warranties, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose are disclaimed. In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages (including, but not limited to, procurement of substitute goods or services; loss of use, data, or profits; or business interruption) however caused and on any theory of liability, whether in contract, strict liability, or tort (including negligence or otherwise) arising in any way out of the use of this software, even if advised of the possibility of such damage.
 *
 * @author		Tijs Verkoyen <php-garage-tv@verkoyen.eu>
 * @version		1.0
 *
 * @copyright	Copyright (c) 2008, Tijs Verkoyen. All rights reserved.
 * @license		BSD License
 */
class GarageTv
{
	// internal constant to enable/disable debugging
	const DEBUG = false;

	// url for the api
	const API_URL = 'http://www.garagetv.be/api.ashx';

	// port for the api
	const API_PORT = 80;

	// current version
	const VERSION = '1.0';


	/**
	 * The API-key
	 *
	 * @var	string
	 */
	private $apiKey;


	/**
	 * The timeout
	 *
	 * @var	int
	 */
	private $timeOut = 60;


	/**
	 * The UserAgent
	 *
	 * @var	string
	 */
	private $userAgent;


// class methods
	/**
	 * Default constructor
	 *
	 * @return	void
	 * @param	string[optional] $key
	 */
	public function __construct($key = null)
	{
		// set properties if needed
		if($key != null) $this->setApiKey($key);
	}


	/**
	 * Make the call
	 *
	 * @return	string
	 * @param	string $method
	 * @param	array[optional] $aParameters
	 */
	private function doCall($method, $aParameters = array())
	{
		// redefine
		$method = (string) $method;
		$aParameters = (array) $aParameters;

		// build url
		$url = self::API_URL .'?apikey='. $this->getApiKey() .'&method='. $method;

		// rebuild url if we don't use post
		if(!empty($aParameters))
		{
			// init var
			$queryString = '';

			// loop parameters and add them to the queryString
			foreach($aParameters as $key => $value) $queryString .= '&'. $key .'='. urlencode(utf8_encode($value));

			// append to url
			$url .= $queryString;
		}

		// set options
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_PORT] = self::API_PORT;
		$options[CURLOPT_USERAGENT] = $this->getUserAgent();
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_TIMEOUT] = (int) $this->getTimeOut();

		// init
		$curl = curl_init();

		// set options
		curl_setopt_array($curl, $options);

		// execute
		$response = curl_exec($curl);
		$headers = curl_getinfo($curl);

		// close
		curl_close($curl);

		// invalid headers
		if(!in_array($headers['http_code'], array(0, 200)))
		{
			// should we provide debug information
			if(self::DEBUG)
			{
				// make it output proper
				echo '<pre>';

				// dump the header-information
				var_dump($headers);

				// dump the raw response
				var_dump($response);

				// end proper format
				echo '</pre>';

				// stop the script
				exit;
			}

			// throw error
			throw new GarageTvException('Invalid response-headers ('. $headers['code'] .')', (int) $headers['code']);
		}

		// validate body
		$xml = @simplexml_load_string($response);

		if($xml === false) throw new GarageTvException(null, 1);
		if(isset($xml['type']) && $xml['type'] == 'Success' && isset($xml['error']) && $xml['error'] == '0') return $response;

		// error or warning
		else
		{
			// should we provide debug information
			if(self::DEBUG)
			{
				// make it output proper
				echo '<pre>';

				// dump the header-information
				var_dump($headers);

				// dump the raw response
				var_dump($response);

				// end proper format
				echo '</pre>';

				// stop the script
				exit;
			}

			// errormessage or code provided?
			if(isset($xml['type']) && ($xml['type'] == 'Failure' || $xml['type'] == 'Warning'))
			{
				// message given?
				if(isset($xml->message)) throw new GarageTvException((string) $xml->message);

				// no message, but errorcode
				if(isset($xml['error'])) throw new GarageTvException(null, (int) substr($xml['error'], 1));
			}

			// invalid response
			throw new GarageTvException(null, 1);
		}
	}


	/**
	 * Get the API-key
	 *
	 * @return	string
	 */
	private function getApiKey()
	{
		return $this->apiKey;
	}


	/**
	 * Get the timeout
	 *
	 * @return	int
	 */
	public function getTimeOut()
	{
		return (int) $this->timeOut;
	}


	/**
	 * Get the useragent
	 *
	 * @return	string
	 */
	public function getUserAgent()
	{
		return (string) 'PHP GarageTv/'. self::VERSION .' '. $this->userAgent;
	}


	/**
	 * Set the API-key
	 *
	 * @return	void
	 * @param	string $key
	 */
	public function setApiKey($key)
	{
		$this->apiKey = (string) $key;
	}


	/**
	 * Set the timeout
	 *
	 * @return	void
	 * @param	int $seconds
	 */
	public function setTimeOut($seconds)
	{
		$this->timeOut = (int) $seconds;
	}


	/**
	 * Set the user-agent for you application
	 * It will be appended to ours
	 *
	 * @return	void
	 * @param	string $userAgent
	 */
	public function setUserAgent($userAgent)
	{
		$this->userAgent = (string) $userAgent;
	}


	/**
	 * Convert userXml into an array
	 *
	 * @return	array
	 * @param	SimpleXMLElement $xml
	 */
	private function userXmlToArray($xml)
	{
		// init var
		$aUser = array();

		// account properties
		if(isset($xml->id)) $aUser['id'] = (string) $xml->id;
		if(isset($xml->datecreated)) $aUser['datecreated'] = strtotime(utf8_decode((string) $xml->datecreated));

		if(isset($xml->hasavatar)) $aUser['hasavatar'] = (bool) (strtolower((string) $xml->hasavatar) == 'true');
		if(isset($xml->avatarurl)) $aUser['avatar_url'] = (string) $xml->avatarurl;

		if(isset($xml->isgroup)) $aUser['isgroup'] = (bool) (strtolower((string) $xml->isgroup) == 'true');

		if(isset($xml->language)) $aUser['language'] = utf8_decode((string) $xml->language);
		if(isset($xml->lastlogin)) $aUser['lastlogin'] = strtotime(utf8_decode((string) $xml->lastlogin));

		if(isset($xml->totalfriends)) $aUser['totalfriends'] = (int) $xml->totalfriends;
		if(isset($xml->totalposts)) $aUser['totalposts'] = (int) $xml->totalposts;

		if(isset($xml->accountstatus)) $aUser['account_status'] = utf8_decode((string) $xml->accountstatus);
		if(isset($xml->isbanned)) $aUser['isbanned'] = (bool) (strtolower((string) $xml->isbanned) == 'true');
		if(isset($xml->banneduntil)) $aUser['banned_until'] = strtotime(utf8_decode((string) $xml->banneduntil));
		if(isset($xml->banreason)) $aUser['banreason'] = utf8_decode((string) $xml->banreason);

		if(isset($xml->displayname)) $aUser['displayname'] = utf8_decode((string) $xml->displayname);
		if(isset($xml->name)) $aUser['name'] = utf8_decode((string) $xml->name);
		if(isset($xml->email)) $aUser['email'] = utf8_decode((string) $xml->email);
		if(isset($xml->birthdate)) $aUser['birthdate'] = utf8_decode((string) $xml->birthdate);
		if(isset($xml->location)) $aUser['location'] = utf8_decode((string) $xml->location);
		if(isset($xml->countryid)) $aUser['countryid'] = utf8_decode((string) $xml->countryid);
		if(isset($xml->countryname)) $aUser['countryname'] = utf8_decode((string) $xml->countryname);

		if(isset($xml->bio)) $aUser['bio'] = utf8_decode((string) $xml->bio);
		if(isset($xml->occupation)) $aUser['occupation'] = utf8_decode((string) $xml->occupation);
		if(isset($xml->interests)) $aUser['interests'] = utf8_decode((string) $xml->interests);
		if(isset($xml->webaddress)) $aUser['webaddress'] = utf8_decode((string) $xml->webaddress);
		if(isset($xml->signature)) $aUser['signature'] = utf8_decode((string) $xml->signature);

		// return
		return (array) $aUser;
	}


	/**
	 * Convert videoXml into an array
	 *
	 * @return	array
	 * @param	SimpleXMLElement $xml
	 */
	private function videoXmlToArray($xml)
	{
		// init var
		$aVideo = array();

		// properties
		if(isset($xml->id)) $aVideo['id'] = (string) $xml->id;
		if(isset($xml->name)) $aVideo['name'] = utf8_decode((string) $xml->name);
		if(isset($xml->description)) $aVideo['description'] = trim(utf8_decode((string) $xml->description));
		if(isset($xml->duration))
		{
			$aVideo['duration'] = utf8_decode((string) $xml->duration);

			// calculate seconds
			$chunks = explode(':', $aVideo['duration']);
			$sec = (int) $chunks[0] * 60;
			$sec += (int) $chunks[1];
			$aVideo['duration_in_seconds'] = $sec;
		}
		if(isset($xml->rating)) $aVideo['rating'] = (int) $xml->rating;
		if(isset($xml->postdate)) $aVideo['post_date'] = strtotime(utf8_decode((string) $xml->postdate));
		if(isset($xml->views)) $aVideo['views'] = (int) $xml->views;

		// urls
		if(isset($xml->thumbnail)) $aVideo['thumbnail_url'] = utf8_decode((string) $xml->thumbnail);
		if(isset($xml->image)) $aVideo['image_url'] = utf8_decode((string) $xml->image);
		if(isset($xml->video)) $aVideo['video_url'] = utf8_decode((string) $xml->video);
		if(isset($xml->tvvideo)) $aVideo['tvvideo_url'] = utf8_decode((string) $xml->tvvideo);
		if(isset($xml->mp4video)) $aVideo['mp4video_url'] = utf8_decode((string) $xml->mp4video);
		if(isset($xml->viewer)) $aVideo['viewer_url'] = utf8_decode((string) $xml->viewer);

		// code
		if(isset($xml->embedcode)) $aVideo['embed_code'] = utf8_decode((string) $xml->embedcode);

		// user
		if(isset($xml->userid)) $aVideo['user']['id'] = (string) $xml->userid;
		if(isset($xml->username)) $aVideo['user']['username'] = (string) $xml->username;
		if(isset($xml->useravatar)) $aVideo['user']['avatar'] = (string) $xml->useravatar;

		// return
		return $aVideo;
	}


// test methods
	/**
	 * Pings the server and optionally returns the specified value. This is a good way to check whether the API is online.
	 *
	 * Will return a boolean if no parameter is specified, otherwise the specified text will be returned if the api is online
	 *
	 * @return	mixed
	 * @param	string[optional] $payload
	 */
	public function ping($payload = null)
	{
		// build parameters
		$aParameters = array();
		if($payload !== null) $aParameters['Payload'] = (string) $payload;

		// make the call
		$response = $this->doCall('ping', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->answer)) throw new GarageTvException(null, 1);

		// no parameters specified
		if(utf8_decode((string) $xml->answer) == 'Pong!') return true;

		// payload specified
		if(utf8_decode((string) $xml->answer) == $payload) return $payload;

		// fallback
		return false;
	}


	/**
	 * Returns the current version of the API.
	 *
	 * @return	string
	 */
	public function getVersion()
	{
		// make the call
		$response = $this->doCall('GetVersion');

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->answer)) throw new GarageTvException(null, 1);

		// return
		return utf8_decode((string) $xml->answer);
	}


// auth methods
	/**
	 * Authenticates the specified user. Before uploading video files, you need to authenticate a user and use the retured token for validation purposes.
	 *
	 * @return	string
	 * @param	string $username	The name of the user that is authenticating.
	 * @param	string $password	The password of the user that is authenticating.
	 */
	public function login($username, $password)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserName'] = (string) $username;
		$aParameters['Password'] = (string) $password;

		// make the call
		$response = $this->doCall('LogOnUser', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->usertoken)) throw new GarageTvException(null, 1);

		// return
		return utf8_decode((string) $xml->usertoken);
	}


	/**
	 * After the user has been authenticated using the LogOnUser method, the user can be logged off using this method call
	 *
	 * @return	bool
	 * @param	string $token	The authentication token received from the login method call.
	 */
	public function logoff($token)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserToken'] = (string) $token;

		// make the call
		$this->doCall('LogOffUser', $aParameters);

		// return
		return true;
	}


// categories methods
	/**
	 * Fetches the list of supported GarageTV categories.
	 *
	 * @return	array
	 */
	public function getCategories()
	{
		// make the call
		$response = $this->doCall('FetchCategories');

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->categories->categoryinfo)) throw new GarageTvException(null, 1);

		// init var
		$aCategories = array();

		// loop categories
		foreach($xml->categories->categoryinfo as $row)
		{
			// init var
			$aTemp = array();

			// build array
			$aTemp['id'] = (string) $row->id;
			$aTemp['name'] = utf8_decode((string) $row->name);
			$aTemp['video_count'] = (int) $row->videocount;

			// add categorie
			$aCategories[] = $aTemp;
		}

		// return
		return (array) $aCategories;
	}


	/**
	 * Fetches videos from the specified video category.
	 *
	 * @return	array
	 * @param	string $categoryId	The unique ID of the category to access. This can be retrieved using the FetchCategories method call.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @param	bool[optional] $adult	Indicates whether to return 18+ videos.
	 */
	public function getCategory($categoryId, $page = 1, $itemsPerPage = 48, $adult = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['CategoryID'] = (string) $categoryId;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;
		$aParameters['18plus'] = (bool) $adult;

		// make the call
		$response = $this->doCall('FetchCategory', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->category->videos->videoinfo)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->category->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


// gallery methods
	/**
	 * Fetches the list of supported GarageTV galleries.
	 *
	 * @return array
	 */
	public function getGalleries()
	{
		// make the call
		$response = $this->doCall('FetchGalleries');

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->galleries->galleryinfo)) throw new GarageTvException(null, 1);

		// init var
		$aGalleries = array();

		// loop categories
		foreach($xml->galleries->galleryinfo as $row)
		{
			// init var
			$aTemp = array();

			// build array
			$aTemp['id'] = (string) $row->id;
			$aTemp['name'] = utf8_decode((string) $row->name);
			$aTemp['video_count'] = (int) $row->videocount;

			// add categorie
			$aGalleries[] = $aTemp;
		}

		// return
		return (array) $aGalleries;
	}


	/**
	 * Fetches videos from the specified video category.
	 *
	 * @return	array
	 * @param	string $galleryId	The unique ID of the category to access. This can be retrieved using the FetchGalleries method call.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @param	bool[optional] $adult	Indicates whether to return 18+ videos.
	 */
	public function getGallery($galleryId, $page = 1, $itemsPerPage = 48, $adult = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['GalleryId'] = (string) $galleryId;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;
		$aParameters['18plus'] = (bool) $adult;

		// make the call
		$response = $this->doCall('FetchGallery', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->gallery->videos->videoinfo)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->gallery->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


// video methods
	/**
	 * Comment the specified video.
	 *
	 * @return	bool
	 * @param	string $videoId	The unique ID of the video to comment.
	 * @param	string $token	The authentication token received from the login method call.
	 * @param	string $comment	The comment for the specified video.
	 */
	public function commentVideo($videoId, $token, $comment)
	{
		// build parameters
		$aParameters = array();
		$aParameters['VideoID'] = (string) $videoId;
		$aParameters['UserToken'] = (string) $token;
		$aParameters['Comment'] = (string) $comment;

		// make the call
		$this->doCall('CommentVideo', $aParameters);

		// return
		return true;
	}


	/**
	 * Deletes the specified video from the specified user.
	 *
	 * @return	bool
	 * @param	string $videoId
	 * @param	string $token
	 */
	public function deleteVideo($videoId, $token)
	{
		// build parameters
		$aParameters = array();
		$aParameters['VideoID'] = (string) $videoId;
		$aParameters['UserToken'] = (string) $token;

		// make the call
		$this->doCall('DeleteVideo', $aParameters);

		// return
		return true;
	}


	/**
	 * Fetches video information
	 *
	 * @return	array
	 * @param	string $videoId	The unique ID of the video to access.
	 */
	public function getVideo($videoId)
	{
		// build parameters
		$aParameters = array();
		$aParameters['VideoId'] = (string) $videoId;

		// make the call
		$response = $this->doCall('FetchVideo', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->video->videoinfo)) throw new GarageTvException(null, 1);

		// return
		return $this->videoXmlToArray($xml->video->videoinfo);
	}


	/**
	 * Enter description here...
	 *
	 * @return	bool
	 * @param	string $videoId	The unique ID of the video to comment.
	 * @param	string $token	The authentication token received from the login method call.
	 * @param	int $rating	The rating between 1 and 5 for the specified video.
	 */
	public function rateVideo($videoId, $token, $rating)
	{
		// build parameters
		$aParameters = array();
		$aParameters['VideoID'] = (string) $videoId;
		$aParameters['UserToken'] = (string) $token;
		$aParameters['Rating'] = (int) $rating;

		// make the call
		$this->doCall('RateVideo', $aParameters);

		// return
		return true;
	}


	/**
	 * Fetches videos from the specified video category.
	 *
	 * @return	array
	 * @param	string $categoryId	The unique ID of the category to access. This can be retrieved using the FetchCategories method call.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @param	bool[optional] $adult	Indicates whether to return 18+ videos.
	 */
	public function getVideosByCategory($categoryId, $page = 1, $itemsPerPage = 48, $adult = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['CategoryID'] = (string) $categoryId;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;
		$aParameters['18plus'] = (bool) $adult;

		// make the call
		$response = $this->doCall('FetchVideosByCategory', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->videos->videoinfo)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


	/**
	 * Fetches featured videos of today
	 *
	 * @return array
	 */
	public function getVideosToday()
	{
		// make the call
		$response = $this->doCall('FetchVideosToday');

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->videos->videoinfo)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


	/**
	 * Fetches the top rated videos
	 *
	 * @param	int $timeWindow	The time window specified in days.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 20 videos is returned.
	 * @return unknown
	 */
	public function getVideosTopRated($timeWindow, $page = 1, $itemsPerPage = 20)
	{
		// build parameters
		$aParameters = array();
		$aParameters['TimeWindowSizeInDays'] = (int) $timeWindow;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;

		// make the call
		$response = $this->doCall('FetchVideosTopRated', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->videos->videoinfo)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


	/**
	 * Searches videos for the specified keyword
	 *
	 * @return	array
	 * @param	string $keyword	The keyword to search for.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @return unknown
	 */
	public function searchVideos($keyword, $page = 1, $itemsPerPage = 48)
	{
		// build parameters
		$aParameters = array();
		$aParameters['Keyword'] = (string) $keyword;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;

		// make the call
		$response = $this->doCall('SearchVideos', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->videos->videoinfo)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


// user methods
	/**
	 * Fetches favorite videos from the specified user
	 *
	 * @return	array
	 * @param	string $userId	The unique ID of the user whoes videos to fetch.
	 * @param	string $token	The authentication token received from the login method call.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @param	bool[optional] $adult	Indicates whether to return 18+ videos.
	 */
	public function getUserFavoriteVideos($userId, $token, $page = 1, $itemsPerPage = 48, $adult = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserID'] = (string) $userId;
		$aParameters['UserToken'] = (string) $token;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;
		$aParameters['18plus'] = (bool) $adult;

		// make the call
		$response = $this->doCall('FetchUserFavoriteVideos', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->user->videos)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->user->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


	/**
	 * Fetches the user details from the specified user
	 *
	 * @return	array
	 * @param	string $userId	The unique ID of the user whoes profile to fetch.
	 */
	public function getUserProfile($userId)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserID'] = (string) $userId;

		// make the call
		$response = $this->doCall('FetchUserProfile', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->users->userinfo)) throw new GarageTvException(null, 1);

		// return
		return $this->userXmlToArray($xml->users->userinfo);
	}


	/**
	 * Fetches the user details from the specified user
	 *
	 * @return	array
	 * @param	string $username	The username of the user whoes profile to fetch.
	 */
	public function getUserProfileByUserName($username)
	{
		// build parameters
		$aParameters = array();
		$aParameters['Username'] = (string) $username;

		// make the call
		$response = $this->doCall('FetchUserProfileByUserName', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->users->userinfo)) throw new GarageTvException(null, 1);

		// return
		return $this->userXmlToArray($xml->users->userinfo);
	}


	/**
	 * Fetches videos from the specified user
	 *
	 * @return	array
	 * @param	string $userId	The unique ID of the user whoes videos to fetch.
	 * @param	string $token	The authentication token received from the login method call.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @param	bool[optional] $adult	Indicates whether to return 18+ videos.
	 */
	public function getUserVideos($userId, $token, $page = 1, $itemsPerPage = 48, $adult = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserID'] = (string) $userId;
		$aParameters['UserToken'] = (string) $token;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;
		$aParameters['18plus'] = (bool) $adult;

		// make the call
		$response = $this->doCall('FetchUserVideos', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->user->videos)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->user->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


	/**
	 * Fetches videos from the specified user
	 *
	 * @return	array
	 * @param	string $username	The username of the user whoes videos to fetch.
	 * @param	int[optional] $page	The index of the page to access. When this parameter is not specified, only the first page is returned.
	 * @param	int[optional] $itemsPerPage	The number of videos to return. When this parameter is not specified, a default of 48 videos is returned.
	 * @param	bool[optional] $adult	Indicates whether to return 18+ videos.
	 */
	public function getUserVideosByUserName($username, $page = 1, $itemsPerPage = 48, $adult = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserName'] = (string) $username;
		$aParameters['PageIndex'] = (int) $page;
		$aParameters['PageSize'] = (int) $itemsPerPage;
		$aParameters['18plus'] = (bool) $adult;

		// make the call
		$response = $this->doCall('FetchUserVideosByUserName', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->user->videos)) throw new GarageTvException(null, 1);

		// init var
		$aVideos = array();

		// loop videos
		foreach($xml->user->videos->videoinfo as $row)
		{
			$aVideos[] = $this->videoXmlToArray($row);
		}

		// return
		return $aVideos;
	}


	/**
	 * Uploads a movie using an URL
	 *
	 * @return	array
	 * @param	string $token	The authentication token received from the login method call.
	 * @param	string $url	Contains the URL to a file to upload.
	 * @param	string[optional] $title	The title of the video. When not specified, the title is the same as the name of the video filename.
	 * @param	string[optional] $description	The description of the video.
	 * @param	array[optional] $aTags	The list of tags to associate with the specified video. When not specified, tags are retrieved from the specified title and/or video filename.
	 * @param	string[optional] $categoryId	The unique ID of the category to which this video belongs. When not specified, the video is not stored in a specific category.
	 * @param	array[optional] $aChannels	The names of the channels where the video should be stored. When not specified, the video is stored only inside the root channel of the specified user.
	 * @param	int[optional] $timestamp	The publish date of the video. When not specified 'Now' is used as date.
	 * @param	bool[optional] $published	Publish the video public on www.garagetv.be or not. (This option only works for premium accounts.)
	 */
	public function uploadURL($token, $url, $title = null, $description = null, $aTags = array(), $categoryId = null, $aChannels = array(), $timestamp = null, $published = false)
	{
		// build parameters
		$aParameters = array();
		$aParameters['UserToken'] = (string) $token;
		if($title !== null) $aParameters['Title'] = (string) $title;
		if($description !== null) $aParameters['Description'] = (string) $description;
		if(!empty($aTags)) $aParameters['Tags'] = (string) implode('+', $aTags);
		if($categoryId !== null) $aParameters['CategoryID'] = (string) $categoryId;
		if(!empty($aChannels)) $aParameters['channels'] = (string) implode('+', $aChannels);
		if($timestamp !== null) $aParameters['date'] = date('Ymd-hi', $timestamp);
		$aParameters['URL'] = (string) $url;
		if($published) $aParameters['published'] = (bool) $published;

		// make the call
		$response = $this->doCall('UploadURL', $aParameters);

		// convert into XML-object
		$xml = @simplexml_load_string($response);

		// validate xml
		if(!isset($xml->video->videoinfo)) throw new GarageTvException(null, 1);

		// return
		return $this->videoXmlToArray($xml->video->videoinfo);
	}
}


/**
 * GarageTv Exception class
 *
 * @author		Tijs Verkoyen <php-garage-tv@verkoyen.eu>
 */
class GarageTvException extends Exception
{
	/**
	 * Possible errors
	 *
	 * @var	array
	 */
	private $aErrorCodes = array(1 => 'API Error. Invalid response.',
								11 => 'API Error. A required parameter is missing.',
								12 => 'API Error. A required parameter is empty.',
								13 => 'API Error. A parameter contains an illegal or unknown value.',
								14 => 'API Error. Wrong HTTP method used.',
								15 => 'API Error. The HTTP request does not contain an attached file.',
								16 => 'API Error. The HTTP request contains to many attached files.',
								17 => 'API Error. Cannot read from the attached file.',
								18 => 'API Error. No file attached or empty file attached.',
								19 => 'API Error. IP Address from host is not valid.',
								20 => 'API Error. Not a valid Phonenumber, no users or groups found.',
								21 => 'API Error. Not a direct URL to a videofile.',
								31 => 'APIKey Error. The specified APIKey is not valid.',
								32 => 'APIKey Error. The specified APIKey is not authorized to use the specified method call.',
								33 => 'APIKey Error. The specified APIKey does not exist.',
								101 => 'User Error. The specified user has been banned and can no longer authenticate.',
								102 => 'User Error. The account with the specified username exists but has not been activated yet.',
								103 => 'User Error. The specified username or password is incorrect.',
								104 => 'Internal Error. An unknown error occurred.',
								200 => 'IO Error.',
								201 => 'IO Error. Cannot access the specified file.',
								202 => 'IO Error. The specified file is not a video file.',
								300 => 'Post Error. The post with the requested postID does not exist.',
								301 => 'Post Error. The local file path for the video is empty.',
								666 => 'Internal Error. Please try again or contact us for more technical support.');


	/**
	 * Default constructor
	 *
	 * @return	void
	 * @param	string[optional] $message
	 * @param	int[optional] $code
	 */
	public function __construct($message = null, $code = null)
	{
		// set message
		if($message === null && isset($this->aErrorCodes[(int) $code])) $message = $this->aErrorCodes[(int) $code];

		// call parent
		parent::__construct((string) $message, $code);
	}
}

?>