<?php

namespace Dotdigitalgroup\Email\Model\Email;

class Template extends \Magento\Framework\DataObject
{
    const TEMPLATE_TYPE = 1;

    /**
     * Mapping from template code = template name.
     *
     * @var array
     */
    static public $defaultEmailTemplateCode = [
        'customer_create_account_email_template' => 'New Account (dotmailer)',
        'customer_create_account_email_confirmation_template' => 'New Account Confirmation Key (dotmailer)'
    ];

    /**
     * Mapping from template code = config path for templates.
     * @var array
     */
    public $templateConfigMapping = [
        'customer_create_account_email_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_REGISTER_EMAIL_TEMPLATE,
        'customer_create_account_email_confirmation_template' =>
            \Magento\Customer\Model\EmailNotification::XML_PATH_CONFIRM_EMAIL_TEMPLATE
    ];

    /**
     * Mapping for template code = dotmailer path templates.
     *
     * @var array
     */
    public $templateEmailConfigMapping = [
        'customer_create_account_email_template' =>
            \Dotdigitalgroup\Email\Helper\Transactional::XML_PATH_DDG_TRANSACTIONAL_NEW_ACCCOUNT,
        'customer_create_account_email_confirmation_template' =>
            \Dotdigitalgroup\Email\Helper\Transactional::XML_PATH_DDG_TRANSACTIONAL_NEW_ACCCOUNT_CONFIRMATION_KEY
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
     * @param $store
     * @return string
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

        //@todo remove whole nodes
        $htmlContent = str_replace('Unsubscribe', '', $htmlContent);
        $htmlContent = str_replace('http://$unsub$/', '', $htmlContent);
        $htmlContent = str_replace('Forward this email', '', $htmlContent);
        $htmlContent = str_replace('http://$forward$/', '', $htmlContent);


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

        $template->setOrigTemplateCode($templateCode)
            ->setTemplateCode($templateCodeToName)
            ->setTemplateSubject($templateSubject)
            ->setTemplateText($templateBody)
            ->setTemplateType(\Magento\Email\Model\Template::TYPE_HTML)
            ->setTemplateSenderName($fromName)
            ->setTemplateSenderEmail($fromEmail);
        $this->templateResource->save($template);
    }

}