<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Filesystem\DriverInterface;
use stdClass;

/**
 * Rest class to make cURL requests.
 */
class Rest
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var File
     */
    protected $fileHelper;

    /**
     * @var bool
     */
    protected $isNotJson = false;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var string
     */
    private $verb;

    /**
     * @var string|null
     */
    private $requestBody;

    /**
     * @var int
     */
    private $requestLength;

    /**
     * @var string
     */
    private $apiUsername;

    /**
     * @var string
     */
    private $apiPassword;

    /**
     * @var string
     */
    private $acceptType;

    /**
     * @var mixed
     */
    private $responseBody;

    /**
     * @var mixed
     */
    private $responseInfo;

    /**
     * @var string
     */
    private $responseMessage;

    /**
     * @var string
     */
    private $curlError;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * Rest constructor.
     * @param Data $data
     * @param Logger $logger
     * @param File $fileHelper
     * @param int $website
     * @param DriverInterface $driver
     *
     * @return null
     */
    public function __construct(
        Data $data,
        Logger $logger,
        File $fileHelper,
        DriverInterface $driver,
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
        $this->logger = $logger;
        $this->fileHelper = $fileHelper;
        $this->driver = $driver;

        if ($this->requestBody !== null) {
            $this->buildPostBody();
        }
    }

    /**
     * @param mixed $json
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $prevChar = '';
        $inQuotes = false;
        $endsLineLevel = null;
        $jsonLength = strlen($json);

        for ($i = 0; $i < $jsonLength; ++$i) {
            $char = $json[$i];
            $newLIneLevel = null;
            $post = '';
            if ($endsLineLevel !== null) {
                $newLIneLevel = $endsLineLevel;
                $endsLineLevel = null;
            }
            if ($char === '"' && $prevChar != '\\') {
                $inQuotes = !$inQuotes;
            } elseif (!$inQuotes) {
                switch ($char) {
                    case '}':
                    case ']':
                        $level--;
                        $endsLineLevel = null;
                        $newLIneLevel = $level;
                        break;

                    case '{':
                    case '[':
                        $level++;
                        break;
                    case ',':
                        $endsLineLevel = $level;
                        break;

                    case ':':
                        $post = ' ';
                        break;

                    case ' ':
                    case "\t":
                    case "\n":
                    case "\r":
                        $char = '';
                        $endsLineLevel = $newLIneLevel;
                        $newLIneLevel = null;
                        break;
                }
            }
            if ($newLIneLevel !== null) {
                $result .= "\n" . str_repeat("\t", $newLIneLevel);
            }
            $result .= $char . $post;
            $prevChar = $char;
        }

        return $result;
    }

    /**
     * Returns the object as JSON.
     *
     * @param bool $pretty
     *
     * @return string
     */
    public function toJSON($pretty = false)
    {
        if (!$pretty) {
            return json_encode($this->expose());
        } else {
            return $this->prettyPrint(json_encode($this->expose()));
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
     *
     * @return array|stdClass
     */
    public function execute()
    {
        // clear any recent error response message
        $this->responseMessage = null;
        // @codingStandardsIgnoreLine
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
            // @codingStandardsIgnoreLine
            curl_close($ch);
            throw $e;
        } catch (\Exception $e) {
            // @codingStandardsIgnoreLine
            curl_close($ch);
            throw $e;
        }

        /*
         * check and debug api request total time
         */
        $this->processDebugApi();

        $response = $this->responseBody;

        if (!$response) {
            $response = new stdClass();
            if ($curlError = $this->getCurlError()) {
                $response->message = $curlError;
            }
        }

        $this->responseMessage = $response->message ?? null;

        return $response;
    }

    /**
     * @return void
     */
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
     * @param mixed $ch
     *
     * @return null
     */
    private function executeGet($ch)
    {
        $this->doExecute($ch);
    }

    /**
     * Execute post request.
     *
     * @param mixed $ch
     *
     * @return null
     */
    private function executePost($ch)
    {
        if (!is_string($this->requestBody)) {
            $this->buildPostBody();
        }
        // @codingStandardsIgnoreStart
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
        curl_setopt($ch, CURLOPT_POST, true);
        // @codingStandardsIgnoreEnd
        $this->doExecute($ch);
    }

    /**
     * Post from the file.
     *
     * @param mixed $filename
     *
     * @return void
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
     * @param mixed $ch
     *
     * @return null
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function executePut($ch)
    {
        if (!is_string($this->requestBody)) {
            $this->buildPostBody();
        }

        $this->requestLength = strlen($this->requestBody);
        $fh = $this->driver->fileOpen('php://memory', 'rw');
        $this->driver->fileWrite($fh, $this->requestBody);
        rewind($fh);

        // @codingStandardsIgnoreStart
        curl_setopt($ch, CURLOPT_INFILE, $fh);
        curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
        curl_setopt($ch, CURLOPT_PUT, true);
        // @codingStandardsIgnoreEnd

        $this->doExecute($ch);

        $this->driver->fileClose($fh);
    }

    /**
     * Execute delete.
     *
     * @param mixed $ch
     *
     * @return void
     */
    private function executeDelete($ch)
    {
        // @codingStandardsIgnoreLine
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->doExecute($ch);
    }

    /**
     * Execute request.
     *
     * @param mixed $ch
     *
     * @return void
     */
    private function doExecute(&$ch)
    {
        $this->setCurlOpts($ch);

        if ($this->isNotJson) {
            // @codingStandardsIgnoreLine
            $this->responseBody = curl_exec($ch);
        } else {
            // @codingStandardsIgnoreLine
            $this->responseBody = json_decode(curl_exec($ch));
        }
        // @codingStandardsIgnoreStart
        $this->responseInfo = curl_getinfo($ch);

        //if curl error found
        if (curl_errno($ch)) {
            //save the error
            $this->curlError = curl_error($ch);
        }

        curl_close($ch);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Curl options.
     *
     * @param mixed $ch
     *
     * @return void
     */
    private function setCurlOpts(&$ch)
    {
        // @codingStandardsIgnoreStart
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: ' . $this->acceptType,
                'Content-Type: application/json',
            ]
        );
        // @codingStandardsIgnoreEnd
    }

    /**
     * Basic auth.
     *
     * @param mixed $ch
     *
     * @return void
     */
    private function setAuth(&$ch)
    {
        if ($this->apiUsername !== null && $this->apiPassword !== null) {
            // @codingStandardsIgnoreStart
            curl_setopt($ch, CURLAUTH_BASIC, CURLAUTH_DIGEST);
            curl_setopt(
                $ch,
                CURLOPT_USERPWD,
                $this->apiUsername . ':' . $this->apiPassword
            );
            // @codingStandardsIgnoreEnd
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
     * @param mixed $acceptType
     *
     * @return null
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
     * @param mixed $apiUsername
     *
     * @return $this
     */
    public function setApiUsername($apiUsername)
    {
        $this->apiUsername = trim($apiUsername);

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
     * @param mixed $apiPassword
     *
     * @return $this
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = trim($apiPassword);

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
     *
     * @return mixed
     */
    private function getResponseInfo()
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
     * @param mixed $url
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
     * @param mixed $verb
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
     * Log a REST failure
     *
     * @param string $message
     * @param array $extra
     * @param int $level
     * @return $this
     */
    protected function addClientLog(string $message, array $extra = [], $level = Logger::WARNING)
    {
        $logTitle = sprintf(
            'Apiconnector Client [%s]: %s',
            debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2)[1]['function'],
            $message
        );

        $extra += [
            'api_user' => $this->getApiUsername(),
            'url' => $this->url,
            'verb' => $this->verb,
        ];

        if ($this->responseMessage) {
            $extra['error_message'] = $this->responseMessage;
        }

        switch ($level) {
            case Logger::ERROR:
                $this->logger->addError($logTitle, $extra);
                break;

            case Logger::WARNING:
                $this->logger->addWarning($logTitle, $extra);
                break;

            case Logger::DEBUG:
                $this->logger->addDebug($logTitle, $extra);
                break;

            default:
                $this->logger->addInfo($logTitle, $extra);
        }

        return $this;
    }
}
