<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\ServerSentEvents;
use Dotdigitalgroup\Email\Model\Events\CloseStreamHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\AddressBooksHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\CronCheckHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\DataFieldsHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\EasyEmailCaptureHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\EnableSyncsHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\OrdersHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\ProductsHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\InvalidConfigurationHandler;
use Dotdigitalgroup\Email\Model\Events\SetupIntegration\ReInitConfigurationHandler;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;

class SetupIntegration extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    /**
     * @var ServerSentEvents
     */
    private $serverSentEvents;

    /**
     * @var AddressBooksHandler
     */
    private $addressBooksHandler;

    /**
     * @var CronCheckHandler
     */
    private $cronCheckHandler;

    /**
     * @var DataFieldsHandler
     */
    private $dataFieldsHandler;

    /**
     * @var EnableSyncsHandler
     */
    private $enableSyncsHandler;

    /**
     * @var EasyEmailCaptureHandler
     */
    private $easyEmailCaptureHandler;

    /**
     * @var OrdersHandler
     */
    private $ordersHandler;

    /**
     * @var ProductsHandler
     */
    private $productsHandler;

    /**
     * @var InvalidConfigurationHandler
     */
    private $invalidConfigurationHandler;

    /**
     * @var InvalidConfigurationHandler
     */
    private $reInitConfigurationHandler;

    /**
     * @var CloseStreamHandler
     */
    private $closeStreamHandler;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Context $context
     * @param AddressBooksHandler $addressBooksHandler
     * @param CronCheckHandler $cronCheckHandler
     * @param DataFieldsHandler $dataFieldsHandler
     * @param EnableSyncsHandler $enableSyncsHandler
     * @param EasyEmailCaptureHandler $easyEmailCaptureHandler
     * @param OrdersHandler $ordersHandler
     * @param ProductsHandler $productsHandler
     * @param InvalidConfigurationHandler $invalidConfigurationHandler
     * @param ReInitConfigurationHandler $reInitConfigurationHandler
     * @param CloseStreamHandler $closeStreamHandler
     * @param ServerSentEvents $serverSentEvents
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        AddressBooksHandler $addressBooksHandler,
        CronCheckHandler $cronCheckHandler,
        DataFieldsHandler $dataFieldsHandler,
        EnableSyncsHandler $enableSyncsHandler,
        EasyEmailCaptureHandler $easyEmailCaptureHandler,
        OrdersHandler $ordersHandler,
        ProductsHandler $productsHandler,
        InvalidConfigurationHandler $invalidConfigurationHandler,
        ReInitConfigurationHandler $reInitConfigurationHandler,
        CloseStreamHandler $closeStreamHandler,
        ServerSentEvents $serverSentEvents,
        Data $helper
    ) {
        $this->addressBooksHandler = $addressBooksHandler;
        $this->cronCheckHandler = $cronCheckHandler;
        $this->dataFieldsHandler = $dataFieldsHandler;
        $this->enableSyncsHandler = $enableSyncsHandler;
        $this->easyEmailCaptureHandler = $easyEmailCaptureHandler;
        $this->ordersHandler = $ordersHandler;
        $this->productsHandler = $productsHandler;
        $this->invalidConfigurationHandler = $invalidConfigurationHandler;
        $this->closeStreamHandler = $closeStreamHandler;
        $this->serverSentEvents = $serverSentEvents;
        $this->reInitConfigurationHandler = $reInitConfigurationHandler;
        $this->helper = $helper;
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return \Dotdigitalgroup\Email\Model\Events\Response\StreamedResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $broadcaster = $this->serverSentEvents;
        $websiteId = $this->getRequest()->getParam('website', 0);
        if (!$this->helper->isEnabled($websiteId) ||
            !$this->helper->getWebsiteApiClient($websiteId)
        ) {
            return $broadcaster
                ->addEventHandler('InvalidConfiguration', $this->invalidConfigurationHandler)
                ->addEventHandler('close', $this->closeStreamHandler)
                ->createResponse()
                ->send();
        }

        return $broadcaster
            ->addEventHandler('AddressBooks', $this->addressBooksHandler)
            ->addEventHandler('DataFields', $this->dataFieldsHandler)
            ->addEventHandler('EnableSyncs', $this->enableSyncsHandler)
            ->addEventHandler('EasyEmailCapture', $this->easyEmailCaptureHandler)
            ->addEventHandler('ConfigurationReInitialise', $this->reInitConfigurationHandler)
            ->addEventHandler('Orders', $this->ordersHandler)
            ->addEventHandler('Products', $this->productsHandler)
            ->addEventHandler('CronCheck', $this->cronCheckHandler)
            ->addEventHandler('close', $this->closeStreamHandler)
            ->createResponse()
            ->send();
    }
}
