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
     * @var \Magento\Framework\Escaper
     */
    public $escaper;

    /**
     * Logviewer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\File $file,
        \Magento\Framework\Escaper $escaper,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->file = $file;
        $this->escaper = $escaper;
        parent::__construct($context, $data);
    }

    public function _construct()
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
        return nl2br($this->escaper->escapeHtml($this->file->getLogFileContent()));
    }
}
