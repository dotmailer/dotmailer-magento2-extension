<?php

namespace Dotdigitalgroup\Email\Model\Adminhtml\Source\Customer\Attributes;

class Select
{
	protected $_customerFactory;

	public function __construct(
		\Magento\Customer\Model\CustomerFactory $customerFactory
	)
	{
		$this->_customerFactory = $customerFactory;
	}

	/**
	 * customer custom attributes.
	 *
	 * @return array
	 */
	public function toOptionArray()
    {

        $options = array();
        //exclude attributes from mapping
        $excluded =
            array('created_at', 'created_in', 'dob', 'dotmailer_contact_id', 'email', 'firstname', 'lastname', 'gender',
                'group_id', 'password_hash', 'prefix', 'rp_token', 'rp_token_create_at', 'website_id');
        $attributes = $this->_customerFactory->create()
	        ->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getFrontendLabel()) {
                $code = $attribute->getAttributeCode();
	            //escape the label in case of quotes
	            $label = addslashes($attribute->getFrontendLabel());
                if(!in_array($code, $excluded))
                    $options[] = array(
                        'value' => $attribute->getAttributeCode(),
                        'label' => $label
                    );
            }
        }

        return $options;
    }
}