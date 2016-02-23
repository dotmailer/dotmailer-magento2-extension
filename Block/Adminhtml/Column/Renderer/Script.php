<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Script
    extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * Render the grid columns.
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $html
            = "<script type='application/javascript'>
            function visitPage(url){document.location.href = url;}
            </script>";

        return $html;
    }

}