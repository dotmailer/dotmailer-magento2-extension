<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Logviewer
 * @package Dotdigitalgroup\Email\Block\Adminhtml
 */
class Logviewer extends \Magento\Backend\Block\Widget\Container
{

    /**
     * @var string
     */
    public $_template = 'log.phtml'; //@codingStandardsIgnoreLine

    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;

    /**
     * Logviewer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\File $file,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->file = $file;
        parent::__construct($context, $data);
    }

    public function _construct() //@codingStandardsIgnoreLine
    {
        $this->_blockGroup = 'Dotdigitalgroup_Email';
        $this->_controller = 'adminhtml_logviewer';
        $this->_headerText = __('Log Viewer');
        parent::_construct();
    }

    /**
     * Get log file content
     *
     * @return string
     */
    public function getLogFileContent()
    {
        return nl2br($this->_escaper->escapeHtml($this->file->getLogFileContent()));
    }
}
