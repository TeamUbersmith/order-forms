<?php

class UberApi extends AppModel
{
	
	var $useTable = false;
	
	private $user = '';
	private $token = '';
	private $endpoint = '';
	
	public function __construct()
	{
		$this->user     = Configure::read('Ubersmith.user');
		$this->token    = Configure::read('Ubersmith.token');
		$this->endpoint = Configure::read('Ubersmith.endpoint');
	}
	
	public function call_api($array = array(), $raw = false)
	{
		$url = $this->endpoint . '?' . http_build_query($array);
		
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL,            $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPAUTH,       CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_HTTP_VERSION,   CURL_HTTP_VERSION_1_0);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERPWD,        $this->user . ':' . $this->token);
		
		$response = curl_exec($curl);
		
		if (curl_errno($curl)) {
			trigger_error('Curl error querying Ubersmith: ' . curl_error($curl) . ' (' . curl_errno($curl) . ')', E_USER_WARNING);
			return false;
		}
		else {
			return ($raw == false) ? json_decode($response) : $response;
		}
	}
}