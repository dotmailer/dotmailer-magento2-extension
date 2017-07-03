<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Publicdatafields implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * Publicdatafields constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data
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
        $client = $this->helper->getWebsiteApiClient($website);

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
        if ($apiEnabled) {
            $datafields = $this->getDataFields();

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
                    if (isset($datafield->name)
                        && $datafield->visibility == 'Public'
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
