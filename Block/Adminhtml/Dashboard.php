<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Dashboard.
 */
class Dashboard extends \Magento\Backend\Block\Widget\Container
{

    /**
     * @var string
     */
    public $_template = 'dashboard/main.phtml';

    public function _construct()
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_dashboard';
        $this->_headerText = __('Dashboard');
        parent::_construct();

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
    public function getContactSyncLink()
    {
        return $this->getUrl('dotdigitalgroup_email/run/contactsync');
    }

    /**
     * @return string
     */
    public function getImporterLink()
    {
        return $this->getUrl('dotdigitalgroup_email/run/importersync');
    }
}
