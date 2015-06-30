<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Fontpicker
{
    /**
     * Options getter. web safe fonts
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => "Arial, Helvetica, sans-serif",
                'label' => Mage::helper('ddg')->__("Arial, Helvetica")),
            array('value' => "'Arial Black', Gadget, sans-serif",
                'label' => Mage::helper('ddg')->__("Arial Black, Gadget")),
            array('value' => "'Courier New', Courier, monospace",
                'label' => Mage::helper('ddg')->__("Courier New, Courier")),
            array('value' => "Georgia, serif",
                'label' => Mage::helper('ddg')->__("Georgia")),
            array('value' => "'MS Sans Serif', Geneva, sans-serif",
                'label' => Mage::helper('ddg')->__("MS Sans Serif, Geneva")),
            array('value' => "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
                'label' => Mage::helper('ddg')->__("Palatino Linotype, Book Antiqua")),
            array('value' => "Tahoma, Geneva, sans-serif",
                'label' => Mage::helper('ddg')->__("Tahoma, Geneva")),
            array('value' => "'Times New Roman', Times, serif",
                'label' => Mage::helper('ddg')->__("Times New Roman, Times")),
            array('value' => "'Trebuchet MS', Helvetica, sans-serif",
                'label' => Mage::helper('ddg')->__("Trebuchet MS, Helvetica")),
            array('value' => "Verdana, Geneva, sans-serif",
                'label' => Mage::helper('ddg')->__("Verdana, Geneva")),
        );
    }
}