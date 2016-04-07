<?php
namespace Dotdigitalgroup\Email\Model\Sync\Contact;

class Delete extends \Dotdigitalgroup_Email\Model\Sync\Contact\Bulk
{
    public function __construct($collection)
    {
        parent::__construct($collection);
    }

    protected function _processCollection($collection)
    {
        foreach($collection as $item)
        {
            $websiteId = $item->getWebsiteId();
            $email = unserialize($item->getImportData());
            $this->_client = $this->_helper->getWebsiteApiClient($websiteId);

            if ($this->_client) {
                $apiContact = $this->_client->postContacts($email);
                if (!isset($apiContact->message) && isset($apiContact->id))
                    $result = $this->_client->deleteContact($apiContact->id);
                elseif (isset($apiContact->message) && !isset($apiContact->id))
                    $result = $apiContact;

                $this->_handleSingleItemAfterSync($item, $result);
            }
        }
    }

    protected function _handleSingleItemAfterSync($item, $result)
    {
        $curlError = $this->_checkCurlError($item);

        if(!$curlError){
            if (isset($result->message)){
                $message = (isset($result->message))? $result->message : 'Error unknown';

                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::FAILED)
                    ->setMessage($message)
                    ->save();
            }else {
                $now = Mage::getSingleton('core/date')->gmtDate();
                $item->setImportStatus(Dotdigitalgroup_Email_Model_Importer::IMPORTED)
                    ->setImportFinished($now)
                    ->setImportStarted($now)
                    ->setMessage('')
                    ->save();
            }
        }
    }
}