<?php

class Dotdigitalgroup_Email_Model_Newsletter_Sub extends Mage_Newsletter_Model_Subscriber
{
    public function sendConfirmationSuccessEmail()
    {
        if (Mage::helper('ddg')->isNewsletterSuccessDisabled($this->getStoreId()))
            return $this;
        else
            parent::sendConfirmationSuccessEmail();
    }
}