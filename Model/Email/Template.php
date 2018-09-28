<?php

namespace Dotdigitalgroup\Email\Model\Email;

class Template extends \Magento\Framework\DataObject
{
    /**
     * HTML template type.
     */
    const TEMPLATE_TYPE = 1;

    const XML_PATH_WISHLIST_EMAIL_EMAIL_TEMPLATE = 'wishlist/email/email_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT =
        'dotmailer_email_templates/email_templates/customer_create_account_email_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT_CONFIRMATION_KEY =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmation_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmed_template';
    const XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_forgot_email_template';
    const XML_PATH_DDG_TEMPLATE_REMIND_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_remind_email_template';
    const XML_PATH_DDG_TEMPLATE_WISHLIST_PRODUCT_SHARE =
        'dotmailer_email_templates/email_templates/wishlist_email_email_template';
    const XML_PATH_DDG_TEMPLATE_FORGOT_ADMIN_PASSWORD =
        'dotmailer_email_templates/email_templates/admin_emails_forgot_email_template';
    const XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_SUCCESS =
        'dotmailer_email_templates/email_templates/newsletter_subscription_success_email_template';
    const XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_CONFIRMATION =
        'dotmailer_email_templates/email_templates/newsletter_subscription_confirm_email_template';
    const XML_PATH_DGG_TEMPLATE_NEW_ORDER_CONFIRMATION =
        'dotmailer_email_templates/email_templates/sales_email_order_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ORDER_CONFIRMATION_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_order_guest_template';
    const XML_PATH_DDG_TEMPLATE_ORDER_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_order_comment_template';
    const XML_PATH_DDG_TEMPLATE_ORDER_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_order_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT =
        'dotmailer_email_templates/email_templates/sales_email_shipment_template';
    const XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_shipment_guest_template';
    const XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_invoice_comment_template';
    const XML_PATH_DDG_TEMPLATE_UNSUBSCRIBE_SUCCESS =
        'dotmailer_email_templates/email_templates/newsletter_subscription_un_email_template';
    const XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_invoice_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_NEW_INVOICE =
        'dotmailer_email_templates/email_templates/sales_email_invoice_template';
    const XML_PATH_DDG_TEMPLATE_NEW_INVOICE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_invoice_guest_template';
    const XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_template';
    const XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_guest_template';
    const XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_comment_template';
    const XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_shipment_comment_template';
    const XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_shipment_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_CONTACT_FORM =
        'dotmailer_email_templates/email_templates/contact_email_email_template';
    const XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_comment_guest_template';
    const XML_PATH_DDG_TEMPLATE_SEND_PRODUCT_TO_FRIEND =
        'dotmailer_email_templates/email_templates/sendfriend_email_template';
    const XML_PATH_DDG_TEMPLATE_PRODUCT_STOCK_ALERT =
        'dotmailer_email_templates/email_templates/product_stock_alert_template';
    const XML_PATH_DDG_TEMPLATE_PRODUCT_PRICE_ALERT =
        'dotmailer_email_templates/email_templates/product_price_alert_template';

    /**
     * Mapping from template code = config path for templates.
     * @var array
     */
    public $templateConfigMapping = [
        'customer_create_account_email_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_REGISTER_EMAIL_TEMPLATE,
        'customer_create_account_email_confirmed_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_CONFIRMED_EMAIL_TEMPLATE,
        'customer_create_account_email_confirmation_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_CONFIRM_EMAIL_TEMPLATE,
        'customer_password_forgot_email_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_FORGOT_EMAIL_TEMPLATE,
        'customer_password_remind_email_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_REMIND_EMAIL_TEMPLATE,
        'wishlist_email_email_template' => self::XML_PATH_WISHLIST_EMAIL_EMAIL_TEMPLATE,
        'admin_emails_forgot_email_template' => \Magento\User\Model\User::XML_PATH_FORGOT_EMAIL_TEMPLATE,
        'newsletter_subscription_success_email_template' =>
            \Magento\Newsletter\Model\Subscriber::XML_PATH_SUCCESS_EMAIL_TEMPLATE,
        'newsletter_subscription_confirm_email_template' =>
            \Magento\Newsletter\Model\Subscriber::XML_PATH_CONFIRM_EMAIL_TEMPLATE,
        'newsletter_subscription_un_email_template' =>
            \Magento\Newsletter\Model\Subscriber::XML_PATH_UNSUBSCRIBE_EMAIL_TEMPLATE,
        'sales_email_order_template' =>
            \Magento\Sales\Model\Order\Email\Container\OrderIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_order_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\OrderIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_order_comment_template' =>
            \Magento\Sales\Model\Order\Email\Container\OrderCommentIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_order_comment_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\OrderCommentIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_shipment_template' =>
            \Magento\Sales\Model\Order\Email\Container\ShipmentIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_shipment_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\ShipmentIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_invoice_comment_template' =>
            \Magento\Sales\Model\Order\Email\Container\InvoiceCommentIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_invoice_comment_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\InvoiceCommentIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_invoice_template' =>
            \Magento\Sales\Model\Order\Email\Container\InvoiceIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_invoice_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\InvoiceIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_creditmemo_template' =>
            \Magento\Sales\Model\Order\Email\Container\CreditmemoIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_creditmemo_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\CreditmemoIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_creditmemo_comment_template' =>
            \Magento\Sales\Model\Order\Email\Container\CreditmemoCommentIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_creditmemo_comment_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\CreditmemoCommentIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'sales_email_shipment_comment_template' =>
            \Magento\Sales\Model\Order\Email\Container\ShipmentCommentIdentity::XML_PATH_EMAIL_TEMPLATE,
        'sales_email_shipment_comment_guest_template' =>
            \Magento\Sales\Model\Order\Email\Container\ShipmentCommentIdentity::XML_PATH_EMAIL_GUEST_TEMPLATE,
        'contact_email_email_template' => 'contact/email/email_template',//interface don't exist on lower versions 2.2.2
        'sendfriend_email_template' => \Magento\SendFriend\Helper\Data::XML_PATH_EMAIL_TEMPLATE,
        'product_stock_alert_template' => \Magento\ProductAlert\Model\Email::XML_PATH_EMAIL_STOCK_TEMPLATE,
        'product_price_alert_template' => \Magento\ProductAlert\Model\Email::XML_PATH_EMAIL_PRICE_TEMPLATE,

    ];

    /**
     * Config path id to dotmialer config.
     *
     * @var array
     */
    public $templateConfigIdToDotmailerConfigPath = [
        'customer_create_account_email_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT,
        'customer_create_account_email_confirmation_template' =>
            self::XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT_CONFIRMATION_KEY,
        'customer_create_account_email_confirmed_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION,
        'customer_password_forgot_email_template' => self::XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD,
        'customer_password_remind_email_template' => self::XML_PATH_DDG_TEMPLATE_REMIND_PASSWORD,
        'admin_emails_forgot_email_template' => self::XML_PATH_DDG_TEMPLATE_FORGOT_ADMIN_PASSWORD,
        'newsletter_subscription_success_email_template' => self::XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_SUCCESS,
        'newsletter_subscription_confirm_email_template' => self::XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_CONFIRMATION,
        'newsletter_subscription_un_email_template' => self::XML_PATH_DDG_TEMPLATE_UNSUBSCRIBE_SUCCESS,
        'sales_email_order_template' => self::XML_PATH_DGG_TEMPLATE_NEW_ORDER_CONFIRMATION,
        'sales_email_order_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ORDER_CONFIRMATION_GUEST,
        'sales_email_order_comment_template' => self::XML_PATH_DDG_TEMPLATE_ORDER_UPDATE,
        'sales_email_order_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_ORDER_UPDATE_GUEST,
        'sales_email_shipment_template' => self::XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT,
        'sales_email_shipment_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT_GUEST,
        'sales_email_invoice_comment_template' => self::XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE,
        'sales_email_invoice_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE_GUEST,
        'sales_email_invoice_template' => self::XML_PATH_DDG_TEMPLATE_NEW_INVOICE,
        'sales_email_invoice_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_INVOICE_GUEST,
        'sales_email_creditmemo_template' => self::XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO,
        'sales_email_creditmemo_guest_template' => self::XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO_GUEST,
        'sales_email_creditmemo_comment_template' => self::XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE,
        'sales_email_creditmemo_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE_GUEST,
        'sales_email_shipment_comment_template' => self::XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE,
        'sales_email_shipment_comment_guest_template' => self::XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE_GUEST,
        'contact_email_email_template' => self::XML_PATH_DDG_TEMPLATE_CONTACT_FORM,
        'sendfriend_email_template' => self::XML_PATH_DDG_TEMPLATE_SEND_PRODUCT_TO_FRIEND,
        'wishlist_email_email_template' => self::XML_PATH_DDG_TEMPLATE_WISHLIST_PRODUCT_SHARE,
        'product_stock_alert_template' => self::XML_PATH_DDG_TEMPLATE_PRODUCT_STOCK_ALERT,
        'product_price_alert_template' => self::XML_PATH_DDG_TEMPLATE_PRODUCT_PRICE_ALERT
    ];

    /**
     * @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory
     */
    public $templateCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Email\Model\ResourceModel\TemplateFactory
     */
    public $templateResource;

    /**
     * @var \Magento\Email\Model\TempalteFactory
     */
    public $templateFactory;

    /**
     * @var array
     */
    public $proccessedCampaings = [];

    /**
     * Template constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $store
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Email\Model\TemplateFactory $templateFactory
     * @param \Magento\Email\Model\ResourceModel\Template $templateResource
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Email\Model\ResourceModel\Template $templateResource,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
    ) {
        $data = [];
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $store;
        $this->templateFactory = $templateFactory;
        $this->templateResource = $templateResource;
        $this->templateCollectionFactory  = $templateCollectionFactory;

        parent::__construct($data);
    }

    /**
     * Load email_template by code/name.
     *
     * @param string $templateCode
     * @return \Magento\Framework\DataObject
     */
    public function loadByTemplateByCode($templateCode)
    {
        $template = $this->templateCollectionFactory->create()
            ->addFieldToFilter('template_code', $templateCode)
            ->setPageSize(1);

        return $template->getFirstItem();
    }

    /**
     * Delete email_template.
     *
     * @param string $templatecode
     */
    public function deleteTemplateByCode($templatecode)
    {
        $template = $this->loadByTemplateByCode($templatecode);
        if ($template->getId()) {
            $template->delete();
        }
    }

    /**
     * Template sync.
     *
     * @return array
     */
    public function sync()
    {
        $result = ['store' => 'Stores : ', 'message' => 'Done.'];
        $lastWebsiteId = '0';
        foreach ($this->storeManager->getStores(true) as $store) {
            $storeId = $store->getId();
            //store not enabled to sync
            if (! $this->helper->isStoreEnabled($storeId)) {
                continue;
            }
            //reset the campaign ids for each website
            $websiteId = $store->getWebsiteId();
            if ($websiteId != $lastWebsiteId) {
                $this->proccessedCampaings = [];
                $lastWebsiteId = $websiteId;
            }

            foreach ($this->templateConfigIdToDotmailerConfigPath as $configTemplateId => $dotConfigPath) {
                $campaignId = $this->getConfigValue($dotConfigPath, $storeId);
                $configPath = $this->templateConfigMapping[$configTemplateId];
                $emailTemplateId = $this->getConfigValue($configPath, $storeId);

                if ($campaignId && $emailTemplateId && ! in_array($campaignId, $this->proccessedCampaings)) {
                    //sync template for store
                    $this->syncEmailTemplate($campaignId, $emailTemplateId, $store);
                    $result['store'] .= ', ' . $store->getCode();

                    $this->proccessedCampaings[$campaignId] = $campaignId;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $config
     * @param int $storeId
     * @return string|boolean
     */
    public function getConfigValue($config, $storeId)
    {
        return $this->scopeConfig->getValue(
            $config,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $campaignId
     * @param int $emailTemplateId
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return \Magento\Email\Model\Template|string
     */
    private function syncEmailTemplate($campaignId, $emailTemplateId, $store)
    {
        $websiteId = $store->getWebsiteId();
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $dmCampaign = $client->getCampaignByIdWithPreparedContent($campaignId);

        if (isset($dmCampaign->message)) {
            $message = $dmCampaign->message;
            $this->helper->log($message);
            return $message;
        }

        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $emailTemplateId);
        //check if is a dotmailer template
        if ($template->getId() || $template->getTemplateCode()) {
            return $this->saveTemplate($template, $dmCampaign, $campaignId);
        }
    }

    /**
     * @param string $templateConfigPath
     * @param int $campaignId
     * @param string $scope
     * @param int $scopeId
     * @return bool|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveTemplateWithConfigPath($templateConfigPath, $campaignId, $scope, $scopeId)
    {
        if ($scope == \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITES) {
            $websiteId = $scopeId;
        } elseif ($scope == \Magento\Store\Model\ScopeInterface::SCOPE_STORES) {
            $websiteId = $this->storeManager->getStore($scopeId)->getWebsiteId();
        } else {
            $websiteId = '0';
        }

        //get the campaign from api
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $dmCampaign = $client->getCampaignByIdWithPreparedContent($campaignId);
        if (isset($dmCampaign->message)) {
            $this->helper->log('Failed to get api template : ' . $dmCampaign->message);
            return false;
        }

        $templateName = $dmCampaign->name . '_' . $campaignId;
        $template = $this->loadByTemplateByCode($templateName);

        return $this->saveTemplate($template, $dmCampaign, $campaignId, $templateConfigPath);
    }

    /**
     * @param \Magento\Email\Model\Template $template
     * @param Object $dmCampaign
     * @param int $campaignId
     * @param string $origTemplateCode
     * @return \Magento\Email\Model\Template
     */
    public function saveTemplate($template, $dmCampaign, $campaignId, $origTemplateCode = '')
    {
        $templateName = $dmCampaign->name . '_' . $campaignId;

        try {
            $template->setTemplateCode($templateName)
                ->setOrigTemplateCode($origTemplateCode)
                ->setTemplateSubject($dmCampaign->subject)
                ->setTemplateText($dmCampaign->processedHtmlContent)
                ->setTemplateType(\Magento\Email\Model\Template::TYPE_HTML)
                ->setTemplateSenderName($dmCampaign->fromName)
                ->setTemplateSenderEmail($dmCampaign->fromAddress->email);

            $this->templateResource->save($template);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }

        return $template;
    }
}
