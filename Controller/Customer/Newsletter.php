<?php

namespace Dotdigitalgroup\Email\Controller\Customer;


class Newsletter extends \Magento\Framework\App\Action\Action
{

    protected $_helper;
    protected $_customerSession;
    protected $_formKeyValidator;

    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        $this->_helper = $helper;
        $this->_customerSession = $session;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct( $context );
    }

    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest()) or !$this->_customerSession->getConnectorContactId()) {
            return $this->_redirect('customer/account/');
        }

        //params
        $additional_subscriptions = $this->getRequest()->getParam('additional_subscriptions');
        $data_fields = $this->getRequest()->getParam('data_fields');
        $customer_id = $this->_customerSession->getConnectorContactId();
        $customer_email = $this->_customerSession->getCustomer()->getEmail();

        //client
        $website = $this->_customerSession->getCustomer()->getStore()->getWebsite();
        $client = $this->_helper->getWebsiteApiClient($website);
        $client->setApiUsername($this->_helper->getApiUsername($website))
            ->setApiPassword($this->_helper->getApiPassword($website));

        $contact = $client->getContactById($customer_id);
        if(isset($contact->id)){
            //contact address books
            $bookError = false;
            $addressBooks = $client->getContactAddressBooks($contact->id);
            $subscriberAddressBook = $this->_helper->getSubscriberAddressBook($website);
            $processedAddressBooks = array();
            if(is_array($addressBooks)){
                foreach($addressBooks as $addressBook){
                    if($subscriberAddressBook != $addressBook->id)
                        $processedAddressBooks[$addressBook->id] = $addressBook->name;
                }
            }
            if(isset($additional_subscriptions)){
                foreach($additional_subscriptions as $additional_subscription){
                    if(!isset($processedAddressBooks[$additional_subscription])){
                        $bookResponse = $client->postAddressBookContacts($additional_subscription, $contact);
                        if(isset($bookResponse->message))
                            $bookError = true;

                    }
                }
                foreach($processedAddressBooks as $bookId => $name){
                    if(!in_array($bookId, $additional_subscriptions)) {
                        $bookResponse = $client->deleteAddressBookContact($bookId, $contact->id);
                        if(isset($bookResponse->message))
                            $bookError = true;
                    }
                }
            }
            else{
                foreach($processedAddressBooks as $bookId => $name){
                    $bookResponse = $client->deleteAddressBookContact($bookId, $contact->id);
                    if(isset($bookResponse->message))
                        $bookError = true;
                }
            }

            //contact data fields
            $data = array();
            $dataFields = $client->getDataFields();
            $processedFields = array();
            foreach($dataFields as $dataField){
                $processedFields[$dataField->name] = $dataField->type;
            }
            foreach($data_fields as $key => $value){
                if(isset($processedFields[$key]) && $value){
                    if($processedFields[$key] == 'Numeric'){
                        $data_fields[$key] = (int)$value;
                    }
                    if($processedFields[$key] == 'String'){
                        $data_fields[$key] = (string)$value;
                    }
                    if($processedFields[$key] == 'Date'){
                        $date = new \Zend_Date($value, "Y/M/d");
                        $data_fields[$key] = $date->toString(\Zend_Date::ISO_8601);
                    }
                    $data[] = array(
                        'Key' => $key,
                        'Value' => $data_fields[$key]
                    );
                }
            }
            $contactResponse = $client->updateContactDatafieldsByEmail($customer_email, $data);

            if(isset($contactResponse->message) && $bookError)
                $this->messageManager->addError(__('An error occurred while saving your subscription preferences.'));
            else
                $this->messageManager->addSuccess(__('The subscription preferences has been saved.'));
        }
        else{
            $this->messageManager->addError(__('An error occurred while saving your subscription preferences.'));
        }
        $this->_redirect('customer/account/');
    }
}