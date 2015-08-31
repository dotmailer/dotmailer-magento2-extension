<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Reset extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render the grid columns.
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $url = HtmlSpecialChars(json_encode($this->getUrl('*/*/reset', array('id' => $row->getId()))));
        return '<button title="Reset" onclick="visitPage(' . $url . '); return false" type="button" style=""><span><span><span>Reset</span></span></span></button>';
    }

}