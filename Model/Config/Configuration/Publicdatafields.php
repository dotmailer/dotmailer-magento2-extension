<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;

class Publicdatafields implements OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * Publicdatafields constructor.
     *
     * @param Data $data
     */
    public function __construct(
        Data $data
    ) {
        $this->helper = $data;
    }

    /**
     * Get data fields.
     *
     * @return mixed
     */
    public function getDataFields()
    {
        $website = $this->helper->getWebsite();
        $client = $this->helper->getWebsiteApiClient($website->getId());

        //grab the datafields request and save to register
        $datafields = $client->getDataFields();

        return $datafields;
    }

    /**
     *  Datafields option.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];
        $apiEnabled = $this->helper->isEnabled($this->helper->getWebsite());
        //get datafields options
        if ($apiEnabled && $datafields = $this->getDataFields()) {
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
                    if (isset($datafield->name) &&
                        isset($datafield->visibility) &&
                        $datafield->visibility == 'Public'
                    ) {
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
