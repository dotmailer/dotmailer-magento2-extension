<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Fontpicker
{

    /**
     * Options getter. web safe fonts
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => "Arial, Helvetica, sans-serif",
                'label' => "Arial, Helvetica"
            ),
            array(
                'value' => "'Arial Black', Gadget, sans-serif",
                'label' => "Arial Black, Gadget"
            ),
            array(
                'value' => "'Courier New', Courier, monospace",
                'label' => "Courier New, Courier"
            ),
            array(
                'value' => "Georgia, serif",
                'label' => "Georgia"
            ),
            array(
                'value' => "'MS Sans Serif', Geneva, sans-serif",
                'label' => "MS Sans Serif, Geneva"
            ),
            array(
                'value' => "'Palatino Linotype', 'Book Antiqua', Palatino, serif",
                'label' => "Palatino Linotype, Book Antiqua"
            ),
            array(
                'value' => "Tahoma, Geneva, sans-serif",
                'label' => "Tahoma, Geneva"
            ),
            array(
                'value' => "'Times New Roman', Times, serif",
                'label' => "Times New Roman, Times"
            ),
            array(
                'value' => "'Trebuchet MS', Helvetica, sans-serif",
                'label' => "Trebuchet MS, Helvetica"
            ),
            array(
                'value' => "Verdana, Geneva, sans-serif",
                'label' => "Verdana, Geneva"
            ),
        );
    }
}