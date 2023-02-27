<?php

namespace Dotdigitalgroup\Email\Controller;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\FailedAuth;
use Dotdigitalgroup\Email\Model\FailedAuthFactory;
use Exception;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\Layout;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth as FailedAuthResource;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class ExternalDynamicContentController implements ActionInterface
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var FailedAuthResource
     */
    private $failedAuthResource;

    /**
     * @var FailedAuthFactory
     */
    private $failedAuthFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var LayoutFactory
     */
    protected $resultLayoutFactory;

    /**
     * @var Layout
     */
    protected $layout;

    /**
     * @param Data $data
     * @param StoreManagerInterface $storeManager
     * @param FailedAuthFactory $failedAuthFactory
     * @param FailedAuthResource $failedAuthResource
     * @param RequestInterface $request
     * @param HttpInterface $response
     * @param UrlInterface $url
     * @param LayoutFactory $resultLayoutFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Data $data,
        StoreManagerInterface $storeManager,
        FailedAuthFactory $failedAuthFactory,
        FailedAuthResource $failedAuthResource,
        RequestInterface $request,
        ResponseInterface $response,
        UrlInterface $url,
        LayoutFactory $resultLayoutFactory
    ) {
        $this->helper = $data;
        $this->storeManager = $storeManager;
        $this->failedAuthFactory = $failedAuthFactory;
        $this->failedAuthResource = $failedAuthResource;
        $this->request = $request;
        $this->response = $response;
        $this->url = $url;
        $this->resultLayoutFactory = $resultLayoutFactory;
    }

    /**
     * Get the  request object
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Auth.
     *
     * @return bool
     */
    public function authenticate()
    {
        //in locked state
        if ($this->isAuthLocked()) {
            $this->setUnauthorizedResponse();

            return false;
        }
        //passcode not valid.
        if (!$this->helper->auth($this->request->getParam('code'))) {
            $this->processFailedRequest();
            $this->setUnauthorizedResponse();
            return false;
        }
        //ip is not allowed
        if (!$this->helper->isIpAllowed()) {
            $this->setUnauthorizedResponse();
            return false;
        }

        return true;
    }

    /**
     * Standard EDC execution
     *
     * @return ResponseInterface|ResultInterface|void
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        //authenticate
        if (!$this->authenticate()) {
            return $this->response;
        }

        $this->layout = $this->resultLayoutFactory->create();
        $this->checkResponse();
        return $this->layout;
    }

    /**
     * Set unauthorised response body and status
     *
     * @return void
     */
    public function setUnauthorizedResponse()
    {
        /** @var \Magento\Framework\Webapi\Rest\Response\Proxy  $response */
        $response = &$this->response;
        $response->setHttpResponseCode(401);
        $response->setHeader('Pragma', 'public', true)
            ->setHeader(
                'Cache-Control',
                'must-revalidate, post-check=0, pre-check=0',
                true
            )
            ->setHeader('Content-type', 'text/html; charset=UTF-8', true)
            ->setBody('<h1>401 Unauthorized</h1>');
    }

    /**
     * Set no content response code and headers
     *
     * @param int $statusCode
     * @return int|void
     */
    public function setNoContentResponse(int $statusCode = 204)
    {
        try {
            $this->layout->setHttpResponseCode($statusCode);
            $this->layout->setHeader('Pragma', 'public', true)
                ->setHeader(
                    'Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0',
                    true
                )
                ->setHeader('Content-type', 'text/html; charset=UTF-8', true);
        } catch (Exception $e) {
            $this->helper->log($e);
        }
    }

    /**
     * If there is no Page output for EDC then send 204
     */
    public function checkResponse()
    {
        if (strlen($this->layout->getLayout()->getOutput()) < 10) {
            $this->setNoContentResponse();
        }
    }

    /**
     * Register the failed attempt and set a lock with a 5min window if more than 5 requests fail.
     *
     * @return void
     */
    private function processFailedRequest()
    {
        $url = $this->url->getCurrentUrl();
        $storeId = $this->storeManager->getStore()->getId();
        $failedAuth = $this->failedAuthFactory->create();
        $this->failedAuthResource->load($failedAuth, $storeId, 'store_id');
        $numOfFails = $failedAuth->getFailuresNum();
        $lastAttemptDate = $failedAuth->getLastAttemptDate();
        //set the first failed attempt
        if (!$failedAuth->getId()) {
            $failedAuth->setFirstAttemptDate(time());
        }

        //check the time for the last fail and update the records
        if ($numOfFails == FailedAuth::NUMBER_MAX_FAILS_LIMIT) {
            //ignore the resource is in a locked state
            if ($failedAuth->isLocked()) {
                $this->helper->log(sprintf('Resource locked time : %s ,store : %s', $lastAttemptDate, $storeId));
                return;
            } else {
                //reset with the first lock after the the lock expired
                $numOfFails = 0;
                $failedAuth->setFirstAttemptDate(time());
            }
        }
        try {
            $failedAuth->setFailuresNum(++$numOfFails)
                ->setStoreId($storeId)
                ->setUrl($url)
                ->setLastAttemptDate(time());
            $this->failedAuthResource->save($failedAuth);
        } catch (Exception $e) {
            $this->helper->log($e);
        }
    }

    /**
     * Check if Auth is locked
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    private function isAuthLocked(): bool
    {
        $failedAuth = $this->failedAuthFactory->create();
        $storeId = $this->storeManager->getStore()->getId();
        $this->failedAuthResource->load($failedAuth, $storeId, 'store_id');

        return $failedAuth->isLocked();
    }
}
