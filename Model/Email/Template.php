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
     * Template constructor.
     * @param \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
     */
    public function __construct(
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $templateCollectionFactory
    ){
        $data = [];
        $this->templateCollectionFactory  = $templateCollectionFactory;

        parent::__construct($data);
    }


    /**
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
     * @param $templatecode
     */
    public function deleteTemplateByCode($templatecode)
    {
        $template = $this->loadByTemplateCode($templatecode);
        if ($template->getId()) {
            $template->delete();
        }
    }

    public function sync()
    {
        $result['message'] = 'Done';

        return $result;
    }

}