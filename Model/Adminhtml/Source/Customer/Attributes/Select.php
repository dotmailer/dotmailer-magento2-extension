<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Customer\Attributes;

class Select
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;

    /**
     * Select constructor.
     *
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory
    ) {
        $this->customerFactory = $customerFactory;
    }

    /**
     * Customer custom attributes.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        //exclude attributes from mapping
        $excluded = [
            'created_at',
            'created_in',
            'dob',
            'dotmailer_contact_id',
            'email',
            'firstname',
            'lastname',
            'gender',
            'group_id',
            'password_hash',
            'prefix',
            'rp_token',
            'rp_token_create_at',
            'website_id',
        ];
        $attributes = $this->customerFactory->create()
            ->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getFrontendLabel()) {
                $code = $attribute->getAttributeCode();
                //escape the label in case of quotes
                //@codingStandardsIgnoreStart
                $label = addslashes($attribute->getFrontendLabel());
                //@codingStandardsIgnoreEnd
                if (!in_array($code, $excluded)) {
                    $options[] = [
                        'value' => $attribute->getAttributeCode(),
                        'label' => $label,
                    ];
                }
            }
        }

        return $options;
    }
}
