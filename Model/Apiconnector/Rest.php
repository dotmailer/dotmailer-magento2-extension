<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * abstract Rest class to make cURL requests.
 */
abstract class Rest
{
    /**
     * @var null
     */
    public $url;
    /**
     * @var string
     */
    public $verb;
    /**
     * @var null
     */
    public $requestBody;
    /**
     * @var int
     */
    public $requestLength;
    /**
     * @var string
     */
    public $apiUsername;
    /**
     * @var string
     */
    public $apiPassword;
    /**
     * @var string
     */
    public $acceptType;
    /**
     * @var null
     */
    public $responseBody;
    /**
     * @var null
     */
    public $responseInfo;
    /**
     * @var
     */
    public $curlError;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var bool
     */
    public $isNotJson = false;

    /**
     * Rest constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param int $website
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        $website = 0
    ) {
        $this->helper        = $data;
        $this->url           = null;
        $this->verb          = 'GET';
        $this->requestBody   = null;
        $this->requestLength = 0;
        $this->apiUsername   = (string)$this->helper->getApiUsername($website);
        $this->apiPassword   = (string)$this->helper->getApiPassword($website);
        $this->acceptType    = 'application/json';
        $this->responseBody  = null;
        $this->responseInfo  = null;

        if ($this->requestBody !== null) {
            $this->buildPostBody();
        }
    }

    /**
     * Exposes the class as an array of objects.
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
        $this->apiUsername   = '';
        $this->apiPassword   = '';
        $this->requestBody   = null;
        $this->requestLength = 0;
        $this->verb          = 'GET';
        $this->responseBody  = null;
        $this->responseInfo  = null;

        return $this;
    }

    /**
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

        /*
         * check and debug api request total time
         */
        $this->processDebugApi();

        return $this->responseBody;
    }

    private function processDebugApi()
    {
        if ($this->helper->isDebugEnabled()) {
            $info = $this->getResponseInfo();
            //the response info data is set
            if (isset($info['url']) && isset($info['total_time'])) {
                $url = $info['url'];
                $time = $info['total_time'];
                $totalTime = sprintf(' time : %g sec', $time);
                $check = $this->helper->getApiResponseTimeLimit();
                $limit = ($check) ? $check : '2';
                $message = $this->verb . ', ' . $url . $totalTime;
                //check for slow queries
                if ($time > $limit) {
                    //log the slow queries
                    $this->helper->log($message);
                }
            }
        }
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
    public function executeGet($ch)
    {
        $this->doExecute($ch);
    }

    /**
     * Execute post request.
     *
     * @param $ch
     */
    public function executePost($ch)
    {
        if (!is_string($this->requestBody)) {
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
    public function buildPostBodyFromFile($filename)
    {
        $this->requestBody = [
            'file' => '@' . $filename,
        ];
    }

    /**
     * Execute put.
     *
     * @param $ch
     */
    public function executePut($ch)
    {
        if (!is_string($this->requestBody)) {
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
    public function executeDelete($ch)
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->doExecute($ch);
    }

    /**
     * Execute request.
     *
     * @param $ch
     */
    public function doExecute(&$ch)
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
     * Curl options.
     *
     * @param $ch
     */
    public function setCurlOpts(&$ch)
    {
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt(
            $ch, CURLOPT_HTTPHEADER, [
                'Accept: ' . $this->acceptType,
                'Content-Type: application/json',
            ]
        );
    }

    /**
     * Basic auth.
     *
     * @param $ch
     */
    public function setAuth(&$ch)
    {
        if ($this->apiUsername !== null && $this->apiPassword !== null) {
            curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
            curl_setopt(
                $ch, CURLOPT_USERPWD,
                $this->apiUsername . ':' . $this->apiPassword
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
     * Set accept type.
     *
     * @param $acceptType
     */
    public function setAcceptType($acceptType)
    {
        $this->acceptType = $acceptType;
    }

    /**
     * Get api username.
     *
     * @return string
     */
    public function getApiUsername()
    {
        return $this->apiUsername;
    }

    /**
     * Set api username.
     *
     * @param $apiUsername
     *
     * @return $this
     */
    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = $apiUsername;

        return $this;
    }

    /**
     * Get api password.
     *
     * @return string
     */
    public function getApiPassword()
    {
        return $this->apiPassword;
    }

    /**
     * Set api password.
     *
     * @param $apiPassword
     *
     * @return $this
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    /**
     * Get response body.
     *
     * @return string/object
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * Get response info.
     */
    public function getResponseInfo()
    {
        return $this->responseInfo;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url.
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
     * Set the verb.
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

    /**
     * @return mixed
     */
    public function getCurlError()
    {
        //if curl error
        if (!empty($this->curlError)) {
            //log curl error
            $message = 'CURL ERROR ' . $this->curlError;
            $this->helper->log($message);

            return $this->curlError;
        }

        return false;
    }

    /**
     * @return $this
     */
    public function setIsNotJsonTrue()
    {
        $this->isNotJson = true;

        return $this;
    }
}
