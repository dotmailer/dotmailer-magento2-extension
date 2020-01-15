<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Rest class to no longer make cURL requests.
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
    private $verb = 'GET';

    /**
     * @var array
     */
    private $requestBody;

    /**
     * @var string
     */
    private $requestFile;

    /**
     * @var int
     */
    private $requestLength = 0;

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
    private $acceptType = 'application/json';

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
    private $requestError;

    /**
     * @var string
     */
    private $encType;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

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
        RequestFactory $requestFactory,
        Data $data,
        Logger $logger,
        File $fileHelper,
        DriverInterface $driver,
        $website = 0
    ) {
        $this->requestFactory = $requestFactory;
        $this->helper        = $data;
        $this->apiUsername   = (string)$this->helper->getApiUsername($website);
        $this->apiPassword   = (string)$this->helper->getApiPassword($website);
        $this->logger = $logger;
        $this->fileHelper = $fileHelper;
        $this->driver = $driver;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    public function setFileUpload(string $filePath)
    {
        $this->requestFile = $filePath;
        return $this;
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
        $this->requestFile = [];

        return $this;
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    public function execute()
    {
        // clear any recent error response message
        $this->responseMessage = null;

        // get request
        $request = $this->requestFactory->create()
            ->prepare(
                $this->url,
                $this->verb,
                $this->apiUsername,
                $this->apiPassword,
                $this->encType
            );

        // add files, if required
        if (!empty($this->requestFile)) {
            $request->addFile($this->requestFile);
        }

        try {
            $response = $request->send($this->requestBody);

            if ($response->getStatusCode() >= 400) {
                $this->requestError = $response->getReasonPhrase();
            }

            $this->responseBody = $this->isNotJson
                ? $response->getBody()
                : json_decode($response->getBody());

            $this->addClientLog('EC API response', [
                'status' => $response->getStatusCode(),
                'phrase' => $response->getReasonPhrase(),
            ], Logger::DEBUG);

        } catch (\Exception $e) {
            $this->requestError = $e->getMessage();
        }

        /*
         * check and debug api request total time
         */
        if ($this->helper->isDebugEnabled()) {
            $this->processDebugApi($request);
        }

        $this->responseMessage = $this->responseBody->message ?? null;

        return $this->responseBody;
    }

    /**
     * @param Request $request
     */
    private function processDebugApi(Request $request)
    {
        $url = $request->getUri();
        $time = $request->getRequestTime();
        $totalTime = sprintf(' time : %g sec', $time);
        $limit = $this->helper->getApiResponseTimeLimit() ?: 2;
        $message = $this->verb . ', ' . $url . $totalTime;

        // check for slow queries
        if ($time > $limit) {
            // log the slow queries
            $this->helper->log($message);
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
        $this->requestBody = $data;

        return $this;
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
     * @param string $encType
     * @return $this
     */
    public function setEncType(string $encType)
    {
        $this->encType = $encType;
        return $this;
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
     * @return string|null
     */
    public function getRequestError()
    {
        //if request error
        if (!empty($this->requestError)) {
            //log request error
            $message = 'REQUEST ERROR ' . $this->requestError;
            $this->helper->log($message);

            return $this->requestError;
        }

        return null;
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
