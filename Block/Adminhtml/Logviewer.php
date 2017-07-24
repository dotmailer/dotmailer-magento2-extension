<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

/**
 * Class Dashboard.
 */
class Logviewer extends \Magento\Backend\Block\Widget\Container
{

    /**
     * @var string
     */
    public $_template = 'log.phtml';

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

    /**
     * @return void
     */
    public function _construct()
    {
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

    /**
     * @return string
     */
    public function getAjaxUrl()
    {
        return $this->getUrl('dotdigitalgroup_email/connector/ajaxlogcontent');
    }
}
