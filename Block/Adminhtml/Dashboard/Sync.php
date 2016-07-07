<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Dashboard;

class Sync extends \Magento\Backend\Block\Widget\Container
{

    /**
     * Constructor.
     */
    public function _construct()
    {
        /**
         * Contact sync.
         */
        $this->buttonList->add(
            'contact_sync',
            [
                'label' => __('Run Contact Sync'),
                'onclick' => 'setLocation(\'' . $this->getContactSyncLink(). '\')',
                'class' => 'primary'
            ]
        );

        /**
         * Importer sync.
         */
        $this->buttonList->add(
            'importer_sync',
            [
                'label' => __('Run Importer Sync'),
                'onclick' => 'setLocation(\'' . $this->getImporterLink(). '\')',
                'class' => 'primary'
            ]
        );
    }


    /**
     * @return string
     */
    protected function getContactSyncLink()
    {
        return $this->getUrl('dotdigitalgroup_email/run/contactsync');
    }

    /**
     * @return string
     */
    protected function getImporterLink()
    {
        return $this->getUrl('dotdigitalgroup_email/run/importersync');
    }

}