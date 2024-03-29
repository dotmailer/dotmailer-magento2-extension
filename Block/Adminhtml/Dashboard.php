<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Dashboard block
 *
 * @api
 */
class Dashboard extends \Magento\Backend\Block\Widget\Container
{

    /**
     * @var string
     */
    public $_template = 'dashboard/main.phtml';

    /**
     * Dashboard constructor
     *
     * @return void
     */
    public function _construct()
    {
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
                'class' => 'primary'
            ]
        );
    }

    /**
     * Get contact sync link.
     *
     * @return string
     */
    public function getContactSyncLink()
    {
        return $this->getUrl('dotdigitalgroup_email/run/contactsync');
    }

    /**
     * Get importer link.
     *
     * @return string
     */
    public function getImporterLink()
    {
        $query = [
            '_query' => [
                'sync-type' => 'importer'
            ]
        ];
        return $this->getUrl(
            'dotdigitalgroup_email/run/sync',
            $query
        );
    }
}
