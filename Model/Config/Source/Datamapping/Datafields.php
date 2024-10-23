<?php

namespace Dotdigitalgroup\Email\Model\Config\Source\Datamapping;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class Datafields implements OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * Datafields constructor.
     *
     * @param Registry $registry
     * @param Data $data
     */
    public function __construct(
        Registry $registry,
        Data $data
    ) {
        $this->helper = $data;
        $this->registry = $registry;
    }

    /**
     *  Datafields option.
     *
     * @return array
     * @throws LocalizedException
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

            //get saved datafields from registry
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
