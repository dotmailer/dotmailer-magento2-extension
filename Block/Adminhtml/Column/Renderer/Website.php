<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Website extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
	protected $storeManager;


	public function __construct(
		\Magento\Backend\Block\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		$data = []
	)
	{
		$this->storeManger = $storeManagerInterface;
		parent::__construct($context, $data);

	}
    /**
     * Render grid columns.
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->storeManger->getStore($this->_getValue($row))->getWebsiteId();
    }
}
