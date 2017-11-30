<?php

namespace Dotdigitalgroup\Email\Model\Email;

class Template extends \Magento\Framework\DataObject
{
    /**
     * HTML template type.
     */
    const TEMPLATE_TYPE = 1;

    const XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT =
        'dotmailer_email_templates/email_templates/customer_create_account_email_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT_CONFIRMATION_KEY =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmation_template';
    const XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION =
        'dotmailer_email_templates/email_templates/customer_create_account_email_confirmed_template';
    const XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD =
        'dotmailer_email_templates/email_templates/customer_password_forgot_email_template';
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
    const XML_PATH_DDG_TEMPLATE_CONTACT_FORM =
        'dotmailer_email_templates/email_templates/contact_email_email_template';

    /**
     * Mapping from template code = template name.
     *
     * @var array
     */
    static public $defaultEmailTemplateCode = [
        'customer_create_account_email_template' => 'New Account (dotmailer)',
        'customer_create_account_email_confirmed_template' => 'New Account Confirmation (dotmailer)',
        'customer_create_account_email_confirmation_template' => 'New Account Confirmation Key (dotmailer)',
        'customer_password_forgot_email_template' => 'Forgot Password (dotmailer)',
        'admin_emails_forgot_email_template' => 'Forgot Admin Password (dotmailer)',
        'newsletter_subscription_success_email_template' => 'Subscription Success (dotmailer)',
        'newsletter_subscription_confirm_email_template' => 'Subscription Confirmation (dotmailer)',
        'newsletter_subscription_un_email_template' => 'Unsubscribe Success (dotmailer)',
        'sales_email_order_template' => 'New Order Confirmation (dotmailer)',
        'sales_email_order_guest_template' => 'New Order Confirmation For Guest (dotmailer)',
        'sales_email_order_comment_template' => 'Order Update (dotmailer)',
        'sales_email_order_comment_guest_template' => 'Order Update For Guest (dotmailer)',
        'sales_email_shipment_template' => 'New Shipment (dotmailer)',
        'sales_email_shipment_guest_template' => 'New Shipment For Guest (dotmailer)',
        'sales_email_invoice_comment_template' => 'Invoice Update (dotmailer)',
        'sales_email_invoice_comment_guest_template' => 'Invoice Update Guest (dotmailer)',
        'sales_email_invoice_template' => 'New Invoice (dotmailer)',
        'sales_email_invoice_guest_template' => 'New Invoice Guest (dotmailer)',
        'sales_email_creditmemo_template' => 'New Credit Memo (dotmailer)',
        'sales_email_creditmemo_guest_template' => 'New Credit Memo Guest (dotmailer)',
        'sales_email_creditmemo_comment_template' => 'Credit Memo Update (dotmailer)',
        'contact_email_email_template' => 'Contact Form (dotmailer)',

    ];

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
        'contact_email_email_template' => \Magento\Contact\Model\ConfigInterface::XML_PATH_EMAIL_TEMPLATE

    ];

    /**
     * Mapping for template code = dotmailer path templates.
     *
     * @var array
     */
    public $templateEmailConfigMapping = [
        'customer_create_account_email_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT,
        'customer_create_account_email_confirmation_template' =>
            self::XML_PATH_DDG_TEMPLATE_NEW_ACCCOUNT_CONFIRMATION_KEY,
        'customer_create_account_email_confirmed_template' => self::XML_PATH_DDG_TEMPLATE_NEW_ACCOUNT_CONFIRMATION,
        'customer_password_forgot_email_template' => self::XML_PATH_DDG_TEMPLATE_FORGOT_PASSWORD,
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
        'contact_email_email_template' => self::XML_PATH_DDG_TEMPLATE_CONTACT_FORM
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
     * @var array
     */
    public $proccessedCampaings = [];

    /**
     * Template constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $store
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Email\Model\ResourceModel\Template $templateResource
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $store,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Email\Model\ResourceModel\Template $templateResource,
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
    ) {
        $data = [];
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $store;
        $this->templateResource = $templateResource;
        $this->templateCollectionFactory  = $templateCollectionFactory;

        parent::__construct($data);
    }

    /**
     * Load email_template by code/name.
     *
     * @param $templateCode
     * @return mixed
     */
    public function loadByTemplateCode($templateCode)
    {
        $template = $this->templateCollectionFactory->create()
            ->addFieldToFilter('template_code', $templateCode)
            ->setPageSize(1);

        return $template->getFirstItem();
    }

    /**
     * Delete email_template.
     *
     * @param $templatecode
     */
    public function deleteTemplateByCode($templatecode)
    {
        $template = $this->loadByTemplateCode($templatecode);
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
        $result = ['store' => 'Stores : ', 'message' => 'Campaign id\'s '];
        foreach ($this->storeManager->getStores() as $store) {
            foreach ($this->templateEmailConfigMapping as $templateCode => $configPath) {
                $campaignId = $this->scopeConfig->getValue(
                    $configPath,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $store->getId()
                );
                if ($campaignId && ! in_array($campaignId, $this->proccessedCampaings)) {
                    $this->helper->log(sprintf('Campaign %s for store %s', $campaignId, $store->getid()));
                    $this->syncEmailTemplate($campaignId, $templateCode, $store);

                    $this->proccessedCampaings[] = $campaignId;
                    $result['store'] .= ', ' . $store->getCode();
                    $result['message'] .= ' : ' . $campaignId;
                }
            }
        }

        if (! isset($result['message'])) {
            $result['message'] = 'Done.';
        } else {
            $this->helper->log('Email Template Sync ' . $result['message']);
        }

        return $result;
    }

    /**
     * @param $campaignId
     * @param $templateCode
     * @param $store \Magento\Store\Api\Data\StoreInterface
     * @return mixed
     */
    private function syncEmailTemplate($campaignId, $templateCode, $store)
    {
        $websiteId = $store->getWebsiteId();
        $client = $this->helper->getWebsiteApiClient($websiteId);

        $dmCampaign = $client->getCampaignById($campaignId);

        if (isset($dmCampaign->message)) {
            $message = $dmCampaign->message;
            $this->helper->log($message);
            return $message;
        }

        $this->updateTemplate($dmCampaign, $templateCode);
    }

    /**
     * 1. Replace the img source url; /vedimage with https://i.emlfiles.com
     * 2. remove unscbsribe, forward links http://$unsub$/ , http://$forward$/
     * @param $htmlContent
     * @return mixed
     */
    public function convertContent($htmlContent)
    {
        $htmlContent = str_replace('/vedimage', 'https://i.emlfiles.com', $htmlContent);

        //@todo remove whole nodes?!?
        $htmlContent = str_replace('Unsubscribe', '', $htmlContent);
        $htmlContent = str_replace('http://$unsub$/', '', $htmlContent);
        $htmlContent = str_replace('Forward this email', '', $htmlContent);
        $htmlContent = str_replace('http://$forward$/', '', $htmlContent);
        $htmlContent = str_replace('View in browser', '', $htmlContent);
        $htmlContent = str_replace('http://$cantread$/', '', $htmlContent);

        return $htmlContent;
    }

    /**
     * @param $dmCampaign
     * @param $templateCode
     */
    private function updateTemplate($dmCampaign, $templateCode)
    {
        $fromName       = $dmCampaign->fromName;
        $fromEmail      = $dmCampaign->fromAddress->email;
        $templateSubject = $dmCampaign->subject;
        $templateBody   = $this->convertContent($dmCampaign->htmlContent);
        $templateCodeToName = self::$defaultEmailTemplateCode[$templateCode];

        $template = $this->loadByTemplateCode($templateCodeToName);

        try {
            $template->setOrigTemplateCode($templateCode)
                ->setTemplateCode($templateCodeToName)
                ->setTemplateSubject($templateSubject)
                ->setTemplateText($templateBody)
                ->setTemplateType(\Magento\Email\Model\Template::TYPE_HTML)
                ->setTemplateSenderName($fromName)
                ->setTemplateSenderEmail($fromEmail);
            $this->templateResource->save($template);
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
        }
    }

}