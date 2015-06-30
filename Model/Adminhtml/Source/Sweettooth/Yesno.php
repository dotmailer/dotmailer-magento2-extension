<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sweettooth_Yesno
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $status = Mage::helper('ddg')->isSweetToothEnabled();
        if($status){
            return array(
                array('value' => 1, 'label'=>Mage::helper('adminhtml')->__('Yes')),
                array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('No')),
            );
        }

        return array(
            array('value' => 0, 'label'=>Mage::helper('adminhtml')->__('No')),
        );
    }

}