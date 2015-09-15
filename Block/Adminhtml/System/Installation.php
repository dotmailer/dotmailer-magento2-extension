<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\System;

class Installation extends \Magento\Backend\Block\Template
{
    public $sections = array(
        'connector_api_credentials',
        'connector_data_mapping',
        'connector_sync_settings',
        'connector_roi_tracking',
        'connector_lost_baskets',
        'connector_reviews',
        'connector_dynamic_content',
        'connector_transactional_emails',
        'connector_configuration',
        'connector_developer_settings'
    );

	protected $_helper;
	protected $_productMetadata;

	public function __construct(
		\Magento\Framework\App\ProductMetadata $productMetadata,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Backend\Block\Template\Context $context,
		array $data = []
	) {
		$this->_helper = $data;
		$this->_productMetadata = $productMetadata;
		parent::__construct($context, $data);
	}
    /**
     * get the website domain.
     *
     * @return string
     */
    public function getDomain()
    {
	    return $this->_urlBuilder->getBaseUrl();
    }

    /**
     * api username.
     * @return string
     */
    public function getApiUsername()
    {
	    return $this->_helper->getApiUsername();
    }

    /**
     * check if the cron is running.
     * @return bool
     */
    public function getCronInstalled()
    {
        return ($this->_helper->getCronInstalled())? '1' : '0';
    }

    /*
     * Features enabled to use.
     */
    public function getFeatures()
    {
        $section = $this->getRequest()->getParam('section');

        // not not track other sections
        if (!in_array($section, $this->sections))
            return;

        $features = array(
            'customer_sync' => $this->getCustomerSync(),
            'guest_sync' => $this->getGuestSync(),
            'subscriber_sync' => $this->getSubscriberSync(),
            'order_sync' => $this->getOrderSync(),
            'catalog_sync' => $this->getCatalogSync(),
            'dotmailer_smtp' => $this->getDotmailerSmtp(),
            'roi' => $this->getRoi()
        );

        return json_encode($features);
    }


    public function getCatalogSync()
    {
        return $this->_helper->getCatalogSyncEnabled();
    }

    public function getOrderSync()
    {
        return $this->_helper->getOrderSyncEnabled();
    }

    public function getSubscriberSync()
    {
        return $this->_helper->getSubscriberSyncEnabled();
    }

    public function getGuestSync()
    {
        return $this->_helper->getGuestSyncEnabled();
    }

    public function getCustomerSync()
    {
        return $this->_helper->getContactSyncEnabled();
    }

    public function getRoi()
    {
        return $this->_helper->getRoiTrackingEnabled();
    }

    public function getDotmailerSmtp()
    {
        return $this->_helper->isSmtpEnabled();
    }

    /**
     * magento version.
     * @return string
     */
    public function getMageVersion()
    {
	    return $this->_productMetadata->getVersion();
    }

    /**
     * connector version.
     * @return string
     */
    public function getConnectorVersion()
    {
        return $this->_helper->getConnectorVersion();
    }

	/**
	 * Get the api and website names.
	 * @return mixed|string
	 */
	public function getWebsiteNames()
	{

		$data = $this->_helper->getStringWebsiteApiAccounts();

		return $data;
	}

	/**
	 * Get the account email.
	 *
	 * @return mixed
	 */
	public function getAccountEmail()
	{
		return $this->_helper->getAccountEmail();
	}
}