<?php

namespace Dotdigitalgroup\Email\Controller;

/**
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Edc extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\Escaper
     */
    public $escaper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth
     */
    private $failedAuthResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\FailedAuthFactory
     */
    private $failedAuthFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Config
     */
    private $configHelper;

    /**
     * Response constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Escaper $escaper
     * @param \Dotdigitalgroup\Email\Helper\Config $configHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Dotdigitalgroup\Email\Model\FailedAuthFactory $failedAuthFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth $failedAuthResource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Escaper $escaper,
        \Dotdigitalgroup\Email\Helper\Config $configHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Dotdigitalgroup\Email\Model\FailedAuthFactory $failedAuthFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth $failedAuthResource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->helper = $data;
        $this->escaper = $escaper;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->failedAuthFactory = $failedAuthFactory;
        $this->failedAuthResource = $failedAuthResource;

        parent::__construct($context);
    }

    /**
     * Auth.
     */
    public function authenticate()
    {
        //in locked state
        if ($this->isAuthLocked()) {
            $this->sendUnauthorizedResponse();

            return false;
        }
        //passcode not valid.
        if (!$this->helper->auth($this->getRequest()->getParam('code'))) {
            $this->processFailedRequest();
            $this->sendUnauthorizedResponse();
            return false;
        }
        //ip is not allowed
        if (!$this->helper->isIpAllowed()) {
            $this->sendNoContentResponse();
            return false;
        }

        return true;
    }

    /**
     * Standard EDC execution
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        //authenticate
        if ($this->authenticate()) {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
            $this->checkResponse();
        }
    }

    /**
     * @return \Zend\Http\PhpEnvironment\Response
     */
    public function sendUnauthorizedResponse()
    {
        $this->getResponse()
            ->setHttpResponseCode(401)
            ->setHeader('Pragma', 'public', true)
            ->setHeader(
                'Cache-Control',
                'must-revalidate, post-check=0, pre-check=0',
                true
            )
            ->setHeader('Content-type', 'text/html; charset=UTF-8', true)
            ->setBody('<h1>401 Unauthorized</h1>');

        return $this->getResponse()->sendHeaders();
    }

    /**
     * @return \Zend\Http\PhpEnvironment\Response
     */
    public function sendNoContentResponse()
    {
        try {
            $this->getResponse()
                ->setHttpResponseCode(204)
                ->setHeader('Pragma', 'public', true)
                ->setHeader(
                    'Cache-Control',
                    'must-revalidate, post-check=0, pre-check=0',
                    true
                )
                ->setHeader('Content-type', 'text/html; charset=UTF-8', true);
            return $this->getResponse()->sendHeaders();
        } catch (\Exception $e) {
            $this->helper->log($e);
        }
    }

    /**
     * If there is no Page output for EDC then send 204
     */
    public function checkResponse()
    {
        if(strlen($this->_view->getLayout()->getOutput()) < 10) {
            $this->sendNoContentResponse();
        }
    }

    /**
     * Register the failed attempt and set a lock with a 5min window if more then 5 request failed.
     */
    private function processFailedRequest()
    {
        $url = $this->_url->getCurrentUrl();
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
        if ($numOfFails == \Dotdigitalgroup\Email\Model\FailedAuth::NUMBER_MAX_FAILS_LIMIT) {
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
        } catch (\Exception $e) {
            $this->helper->log($e);
        }
    }

    /**
     * @return bool
     */
    private function isAuthLocked()
    {
        $failedAuth = $this->failedAuthFactory->create();
        $storeId = $this->storeManager->getStore()->getId();
        $this->failedAuthResource->load($failedAuth, $storeId, 'store_id');

        return $failedAuth->isLocked();
    }
}
