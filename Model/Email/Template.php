<?php

namespace Dotdigitalgroup\Email\Model\Email;

use Dotdigitalgroup\Email\Model\Sync\SyncInterface;

class Template extends \Magento\Framework\DataObject implements SyncInterface
{
    public const XML_PATH_WISHLIST_EMAIL_EMAIL_TEMPLATE = 'wishlist/email/email_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT =
        'dotmailer_email_templates/email_templates/customer_create_account_email_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION_KEY =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmation_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmed_template';
    public const XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_forgot_email_template';
    public const XML_PATH_DDG_TEMPLATE_REMIND_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_remind_email_template';
    public const XML_PATH_DDG_TEMPLATE_RESET_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_reset_password_template';
    public const XML_PATH_DDG_TEMPLATE_WISHLIST_PRODUCT_SHARE =
        'dotmailer_email_templates/email_templates/wishlist_email_email_template';
    public const XML_PATH_DDG_TEMPLATE_FORGOT_ADMIN_PASSWORD =
        'dotmailer_email_templates/email_templates/admin_emails_forgot_email_template';
    public const XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_SUCCESS =
        'dotmailer_email_templates/email_templates/newsletter_subscription_success_email_template';
    public const XML_PATH_DDG_TEMPLATE_SUBSCRIPTION_CONFIRMATION =
        'dotmailer_email_templates/email_templates/newsletter_subscription_confirm_email_template';
    public const XML_PATH_DGG_TEMPLATE_NEW_ORDER_CONFIRMATION =
        'dotmailer_email_templates/email_templates/sales_email_order_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_ORDER_CONFIRMATION_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_order_guest_template';
    public const XML_PATH_DDG_TEMPLATE_ORDER_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_order_comment_template';
    public const XML_PATH_DDG_TEMPLATE_ORDER_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_order_comment_guest_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT =
        'dotmailer_email_templates/email_templates/sales_email_shipment_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_SHIPMENT_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_shipment_guest_template';
    public const XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_invoice_comment_template';
    public const XML_PATH_DDG_TEMPLATE_UNSUBSCRIBE_SUCCESS =
        'dotmailer_email_templates/email_templates/newsletter_subscription_un_email_template';
    public const XML_PATH_DDG_TEMPLATE_INVOICE_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_invoice_comment_guest_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_INVOICE =
        'dotmailer_email_templates/email_templates/sales_email_invoice_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_INVOICE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_invoice_guest_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_template';
    public const XML_PATH_DDG_TEMPLATE_NEW_CREDIT_MEMO_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_guest_template';
    public const XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_comment_template';
    public const XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE =
        'dotmailer_email_templates/email_templates/sales_email_shipment_comment_template';
    public const XML_PATH_DDG_TEMPLATE_SHIPMENT_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_shipment_comment_guest_template';
    public const XML_PATH_DDG_TEMPLATE_CONTACT_FORM =
        'dotmailer_email_templates/email_templates/contact_email_email_template';
    public const XML_PATH_DDG_TEMPLATE_CREDIT_MEMO_UPDATE_GUEST =
        'dotmailer_email_templates/email_templates/sales_email_creditmemo_comment_guest_template';
    public const XML_PATH_DDG_TEMPLATE_SEND_PRODUCT_TO_FRIEND =
        'dotmailer_email_templates/email_templates/sendfriend_email_template';
    public const XML_PATH_DDG_TEMPLATE_PRODUCT_STOCK_ALERT =
        'dotmailer_email_templates/email_templates/product_stock_alert_template';
    public const XML_PATH_DDG_TEMPLATE_PRODUCT_PRICE_ALERT =
        'dotmailer_email_templates/email_templates/product_price_alert_template';

    /**
     * Mapping from template code = config path for templates.
     *
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
        'customer_password_reset_password_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_RESET_PASSWORD_TEMPLATE,
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
     * Config path id to dotmailer config.
     *
     * @var array
     */
    public $templateConfigIdToDotmailerConfigPath = [
        'customer_create_account_email_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT,
        'customer_create_account_email_confirmation_template' =>
            self::XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION_KEY,
        'customer_create_account_email_confirmed_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION,
        'customer_password_forgot_email_template' => self::XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD,
        'customer_password_remind_email_template' => self::XML_PATH_DDG_TEMPLATE_REMIND_PASSWORD,
        'customer_password_reset_password_template' => self::XML_PATH_DDG_TEMPLATE_RESET_PASSWORD,
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
    private $templateCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Email\Model\ResourceModel\Template
     */
    private $templateResource;

    /**
     * @var \Magento\Email\Model\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var array
     */
    private $processedCampaigns = [];

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
    private function loadTemplateByCode($templateCode)
    {
        $template = $this->templateCollectionFactory->create()
            ->addFieldToFilter('template_code', $templateCode)
            ->setPageSize(1);

        return $template->getFirstItem();
    }

    /**
     * Template sync.
     *
     * @param \DateTime|null $from
     *
     * @return array
     */
    public function sync(?\DateTime $from = null)
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
                $this->processedCampaigns = [];
                $lastWebsiteId = $websiteId;
            }

            foreach ($this->templateConfigIdToDotmailerConfigPath as $configTemplateId => $dotConfigPath) {
                $campaignId = $this->getConfigValue($dotConfigPath, $storeId);
                $configPath = $this->templateConfigMapping[$configTemplateId];
                $emailTemplateId = $this->getConfigValue($configPath, $storeId);

                if ($campaignId && $emailTemplateId && ! in_array($campaignId, $this->processedCampaigns)) {
                    //sync template for store
                    $this->syncEmailTemplate($campaignId, $emailTemplateId, $store);
                    $result['store'] .= ', ' . $store->getCode();

                    $this->processedCampaigns[$campaignId] = $campaignId;
                }
            }
        }

        return $result;
    }

    /**
     * Get config value.
     *
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
     * Sync email template.
     *
     * @param int $campaignId
     * @param int $emailTemplateId
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return void
     */
    private function syncEmailTemplate($campaignId, $emailTemplateId, $store)
    {
        $websiteId = $store->getWebsiteId();
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $dmCampaign = $client->getCampaignByIdWithPreparedContent($campaignId);

        if (isset($dmCampaign->message)) {
            return;
        }

        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $emailTemplateId);
        //check if is a dotmailer template
        if ($template->getId() || $template->getTemplateCode()) {
            $this->saveTemplate($template, $dmCampaign, $campaignId);
        }
    }

    /**
     * Save template with config path.
     *
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
            $this->helper->debug('Failed to get api template : ' . $dmCampaign->message);
            return false;
        }

        $templateName = $dmCampaign->name . '_' . $campaignId;
        $template = $this->loadTemplateByCode($templateName);
        $this->saveTemplate($template, $dmCampaign, $campaignId, $templateConfigPath);

        return $template->getId();
    }

    /**
     * Save template.
     *
     * @param \Magento\Email\Model\Template $template
     * @param Object $dmCampaign
     * @param int $campaignId
     * @param string $origTemplateCode
     * @return void
     */
    private function saveTemplate($template, $dmCampaign, $campaignId, $origTemplateCode = '')
    {
        if (!isset($dmCampaign->name) ||
            !isset($dmCampaign->subject) ||
            !isset($dmCampaign->processedHtmlContent) ||
            !isset($dmCampaign->fromName) ||
            !isset($dmCampaign->fromAddress->email)
        ) {
            $this->helper->debug(
                'Missing data for campaign id: ' . $campaignId . '. Cannot update template.',
                (array) $dmCampaign
            );
            return;
        }

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
            $this->helper->debug($e->getMessage());
        }
    }

    /**
     * Load template.
     *
     * @param string $templateId
     *
     * @return \Magento\Email\Model\Template
     */
    public function loadTemplate($templateId)
    {
        $template = $this->templateFactory->create();
        $this->templateResource->load($template, $templateId);

        return $template;
    }
}
