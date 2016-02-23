<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Reset
    extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $url = HtmlSpecialChars(
            json_encode(
                $this->getUrl('*/*/reset', array('id' => $row->getId()))
            )
        );

        return '<button title="Reset" onclick="visitPage(' . $url
        . '); return false" type="button" style=""><span><span><span>Reset</span></span></span></button>';
    }

}