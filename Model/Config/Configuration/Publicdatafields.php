<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Publicdatafields
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;

    /**
     * Publicdatafields constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_helper = $data;
    }

    /**
     * Get data fields.
     *
     * @return mixed
     */
    protected function getDataFields()
    {
        $website = $this->_helper->getWebsite();
        $client = $this->_helper->getWebsiteApiClient($website);

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
        $fields = [];
        $apiEnabled = $this->_helper->isEnabled($this->_helper->getWebsite());
        //get datafields options
        if ($apiEnabled) {
            $datafields = $this->getDataFields();

            //set the api error message for the first option
            if (isset($datafields->message)) {
                //message
                $fields[] = [
                    'value' => 0,
                    'label' => __($datafields->message)
                ];
            } else {
                //loop for all datafields option
                foreach ($datafields as $datafield) {
                    if (isset($datafield->name)
                        && $datafield->visibility == 'Public'
                    ) {
                        $fields[] = [
                            'value' => $datafield->name,
                            'label' => __($datafield->name)
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
