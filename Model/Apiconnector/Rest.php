<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

abstract class Rest
{

	protected $url;
	protected $verb;
	protected $requestBody;
	protected $requestLength;
	protected $_apiUsername;
	protected $_apiPassword;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;
	protected $curlError;
	protected $_helper;
	protected $isNotJson = false;

	public function __construct(
		$website = 0,
		\Dotdigitalgroup\Email\Helper\Data $data
	) // ($url = null, $verb = 'GET', $requestBody = null)
	{
		$this->_helper       = $data;
		$this->url           = null; //$url;
		$this->verb          = 'GET'; //$verb;
		$this->requestBody   = null; //$requestBody;
		$this->requestLength = 0;
		$this->_apiUsername  = (string)$this->_helper->getApiUsername($website);
		$this->_apiPassword  = (string)$this->_helper->getApiPassword($website);
		$this->acceptType    = 'application/json';
		$this->responseBody  = null;
		$this->responseInfo  = null;

		if ($this->requestBody !== null) {
			$this->buildPostBody();
		}
	}

	private function prettyPrint($json)
	{
		$result          = '';
		$level           = 0;
		$prev_char       = '';
		$in_quotes       = false;
		$ends_line_level = null;
		$json_length     = strlen($json);

		for ($i = 0; $i < $json_length; $i++) {
			$char           = $json[$i];
			$new_line_level = null;
			$post           = "";
			if ($ends_line_level !== null) {
				$new_line_level  = $ends_line_level;
				$ends_line_level = null;
			}
			if ($char === '"' && $prev_char != '\\') {
				$in_quotes = ! $in_quotes;
			} elseif ( ! $in_quotes) {
				switch ($char) {
					case '}':
					case ']':
						$level--;
						$ends_line_level = null;
						$new_line_level  = $level;
						break;

					case '{':
					case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
						break;

					case ':':
						$post = " ";
						break;

					case " ":
					case "\t":
					case "\n":
					case "\r":
						$char            = "";
						$ends_line_level = $new_line_level;
						$new_line_level  = null;
						break;
				}
			}
			if ($new_line_level !== null) {
				$result .= "\n" . str_repeat("\t", $new_line_level);
			}
			$result .= $char . $post;
			$prev_char = $char;
		}

		return $result;
	}

	/**
	 * returns the object as JSON.
	 *
	 * @param bool $pretty
	 *
	 * @return string
	 */
	public function toJSON($pretty = false)
	{

		if ( ! $pretty) {
			return json_encode($this->expose());
		} else {
			return $this->prettyPrint(json_encode($this->expose()));
		}
	}

	/**
	 * exposes the class as an array of objects
	 *
	 * @return array
	 */
	public function expose()
	{

		return get_object_vars($this);

	}


	/**
	 * Reset the client.
	 *
	 * @return $this
	 */
	public function flush()
	{
		$this->_apiUsername  = '';
		$this->_apiPassword  = '';
		$this->requestBody   = null;
		$this->requestLength = 0;
		$this->verb          = 'GET';
		$this->responseBody  = null;
		$this->responseInfo  = null;

		return $this;
	}

	/**
	 * @return null
	 * @throws \Exception
	 */
	public function execute()
	{
		$ch = curl_init();
		$this->setAuth($ch);
		try {
			switch (strtoupper($this->verb)) {
				case 'GET':
					$this->executeGet($ch);
					break;
				case 'POST':
					$this->executePost($ch);
					break;
				case 'PUT':
					$this->executePut($ch);
					break;
				case 'DELETE':
					$this->executeDelete($ch);
					break;
				default:
					throw new \InvalidArgumentException(
						'Current verb (' . $this->verb
						. ') is an invalid REST verb.'
					);
			}
		} catch (\InvalidArgumentException $e) {
			curl_close($ch);
			throw $e;
		} catch (\Exception $e) {
			curl_close($ch);
			throw $e;
		}

		/**
		 * check and debug api request total time
		 */
		if ($this->_helper->getDebugEnabled()) {
			$info = $this->getResponseInfo();
			//the response info data is set
			if (isset($info['url']) && isset($info['total_time'])) {
				$url       = $info['url'];
				$time      = $info['total_time'];
				$totalTime = sprintf(' time : %g sec', $time);
				$check     = $this->_helper->getApiResponseTimeLimit();
				$limit     = ($check) ? $check : '2';
				$message   = $this->verb . ', ' . $url . $totalTime;
				//check for slow queries
				if ($time > $limit) {
					//log the slow queries
					$this->_helper->log($message);
				}
			}
		}

		return $this->responseBody;
	}

	/**
	 * Post data.
	 *
	 * @param null $data
	 *
	 * @return $this
	 */
	public function buildPostBody($data = null)
	{
		$this->requestBody = json_encode($data);

		return $this;
	}

	/**
	 * Execute curl get request.
	 *
	 * @param $ch
	 */
	protected function executeGet($ch)
	{
		$this->doExecute($ch);
	}

	/**
	 * Execute post request.
	 *
	 * @param $ch
	 */
	protected function executePost($ch)
	{
		if ( ! is_string($this->requestBody)) {
			$this->buildPostBody();
		}

		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		curl_setopt($ch, CURLOPT_POST, true);

		$this->doExecute($ch);
	}

	/**
	 * Post from the file.
	 *
	 * @param $filename
	 */
	protected function buildPostBodyFromFile($filename)
	{
		$this->requestBody = array(
			'file' => '@' . $filename
		);
	}

	/**
	 * Execute put.
	 *
	 * @param $ch
	 */
	protected function executePut($ch)
	{
		if ( ! is_string($this->requestBody)) {
			$this->buildPostBody();
		}

		$this->requestLength = strlen($this->requestBody);

		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);

		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);

		$this->doExecute($ch);

		fclose($fh);
	}

	/**
	 * Ececute delete.
	 *
	 * @param $ch
	 */
	protected function executeDelete($ch)
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		$this->doExecute($ch);
	}

	/**
	 * Execute request.
	 *
	 * @param $ch
	 */
	protected function doExecute(&$ch)
	{
		$this->setCurlOpts($ch);

		if ($this->isNotJson) {
			$this->responseBody = curl_exec($ch);
		} else {
			$this->responseBody = json_decode(curl_exec($ch));
		}

		$this->responseInfo = curl_getinfo($ch);

		//if curl error found
		if (curl_errno($ch)) {
			//save the error
			$this->curlError = curl_error($ch);
		}

		curl_close($ch);
	}

	/**
	 * curl options.
	 *
	 * @param $ch
	 */
	protected function setCurlOpts(&$ch)
	{
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt(
			$ch, CURLOPT_HTTPHEADER, array('Accept: ' . $this->acceptType,
			                               'Content-Type: application/json')
		);
	}

	/**
	 * basic auth.
	 *
	 * @param $ch
	 */
	protected function setAuth(&$ch)
	{
		if ($this->_apiUsername !== null && $this->_apiPassword !== null) {
			curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
			curl_setopt(
				$ch, CURLOPT_USERPWD,
				$this->_apiUsername . ':' . $this->_apiPassword
			);
		}
	}

	/**
	 * Get accept type.
	 *
	 * @return string
	 */
	public function getAcceptType()
	{
		return $this->acceptType;
	}

	/**
	 * set accept type.
	 *
	 * @param $acceptType
	 */
	public function setAcceptType($acceptType)
	{
		$this->acceptType = $acceptType;
	}


	/**
	 * get api username.
	 *
	 * @return string
	 */
	public function getApiUsername()
	{
		return $this->_apiUsername;
	}

	/**
	 * set api username.
	 *
	 * @param $apiUsername
	 *
	 * @return $this
	 */
	public function setApiUsername($apiUsername)
	{
		$this->_apiUsername = $apiUsername;

		return $this;
	}

	/**
	 * Get api password.
	 *
	 * @return string
	 */
	public function getApiPassword()
	{
		return $this->_apiPassword;
	}

	/**
	 * set api password.
	 *
	 * @param $apiPassword
	 *
	 * @return $this
	 */
	public function setApiPassword($apiPassword)
	{
		$this->_apiPassword = $apiPassword;

		return $this;
	}

	/**
	 * get response body.
	 *
	 * @return string/object
	 */
	public function getResponseBody()
	{
		return $this->responseBody;
	}

	/**
	 * get response info.
	 *
	 * @return null
	 */
	public function getResponseInfo()
	{
		return $this->responseInfo;
	}

	/**
	 * get url.
	 *
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * set url.
	 *
	 * @param $url
	 *
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = $url;

		return $this;
	}

	/**
	 * get the verb.
	 *
	 * @return string
	 */
	public function getVerb()
	{
		return $this->verb;
	}

	/**
	 * set the verb.
	 *
	 * @param $verb
	 *
	 * @return $this
	 */
	public function setVerb($verb)
	{
		$this->verb = $verb;

		return $this;
	}

	public function getCurlError()
	{
		//if curl error
		if ( ! empty($this->curlError)) {
			//log curl error
			$message = 'CURL ERROR ' . $this->curlError;
			$this->_helper->log($message);

			return $this->curlError;
		}

		return false;
	}

	public function setIsNotJsonTrue()
	{
		$this->isNotJson = true;

		return $this;
	}
}
