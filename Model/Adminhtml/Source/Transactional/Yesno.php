<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Transactional_Yesno
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $status = Mage::helper('ddg')->isSmtpEnabled();
        if (!$status) {
            return array(
                array('value' => 0, 'label'=> Mage::helper('adminhtml')->__('No')),
            );
        } else {
	        return array(
		        array('value' => 0, 'label' => Mage::helper('adminhtml')->__('No')),
		        array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Yes'))
	        );
        }
    }

}