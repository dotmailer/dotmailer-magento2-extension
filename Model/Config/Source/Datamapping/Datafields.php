<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

class Datafields implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Datafields constructor.
     *
     * @param \Magento\Framework\Registry                $registry
     * @param \Dotdigitalgroup\Email\Helper\Data         $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->helper        = $data;
        $this->registry      = $registry;
        $this->storeManager = $storeManager;
    }

    /**
     *  Datafields option.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = [];
        //default data option
        $fields[] = ['value' => '0', 'label' => '-- Please Select --'];
        $websiteId = $this->helper->getWebsiteForSelectedScopeInAdmin()->getId();
        $apiEnabled = $this->helper->isEnabled($websiteId);
        if ($apiEnabled) {
            $savedDatafields = $this->registry->registry('datafields');

            //get saved datafileds from registry
            if ($savedDatafields) {
                $datafields = $savedDatafields;
            } else {
                //grab the datafields request and save to register
                $client = $this->helper->getWebsiteApiClient($websiteId);
                $datafields = $client->getDatafields();
                $this->registry->unregister('datafields'); // additional measure
                $this->registry->register('datafields', $datafields);
            }

            //set the api error message for the first option
            if (isset($datafields->message)) {
                //message
                $fields[] = [
                    'value' => 0,
                    'label' => $datafields->message,
                ];
            } else {
                //loop for all datafields option
                foreach ($datafields as $datafield) {
                    if (isset($datafield->name)) {
                        $fields[] = [
                            'value' => $datafield->name,
                            'label' => $datafield->name,
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
