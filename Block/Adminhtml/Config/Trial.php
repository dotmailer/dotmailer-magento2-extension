<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Trial extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    protected $_helper;

    /**
     * Trial constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        array $data = []
    ) {
        $this->_helper = $helper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        {
            $html = '<a class="various fancybox.iframe" data-fancybox-type="iframe" href=' .
                $this->_helper->getIframeFormUrl() . '><img style="margin-bottom:15px;" src=' .
                $this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png') .
                ' alt="Open Trial Account"></a>';
            $script = "
            <script type='text/javascript'>
                require(['jquery', 'domReady'], function($){
                    $('.various').fancybox({
                        width	: 508,
                        height	: 612,
                        scrolling   : 'no',
                        fitToView	: false,
                        autoSize	: false,
                        closeClick	: false,
                        openEffect	: 'none',
                        closeEffect	: 'none'
                    });
                    
                    $(document).on('click', 'a.fancybox-close', function(){
                        location.reload();
                    });
                }); 
            </script>
        ";
        }
        return $html . $script;
    }
}