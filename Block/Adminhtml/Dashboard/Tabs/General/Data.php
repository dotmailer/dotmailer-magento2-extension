<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_General_Data extends  Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Tabs_General
{
    protected $data = array();
    protected $title = "";

    /**
     * set template
     * @param $info
     * @throws Exception
     */
    public function __construct($info = array())
    {
        $this->title = $info['Title'];
        unset($info['Title']);
        $this->data = $info;

        parent::_construct();
        $this->setTemplate('connector/dashboard/tabs/data.phtml');
    }

    /**
     * Prepare the layout.
     *
     * @return Mage_Core_Block_Abstract|void
     * @throws Exception
     */
    protected function _prepareLayout()
    {
        foreach($this->data as $key => $value){
            $this->addTotal($this->__($key), $value, true);
        }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}