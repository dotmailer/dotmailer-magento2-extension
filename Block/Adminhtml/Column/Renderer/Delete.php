<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Delete extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render the grid columns.
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $url = HtmlSpecialChars(json_encode($this->getUrl('*/*/delete', array('id' => $row->getId()))));
        return '<button title="Delete" onclick="visitPage(' . $url . ')" type="button" style=""><span><span><span>Delete</span></span></span></button>';
    }

}