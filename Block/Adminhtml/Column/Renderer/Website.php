<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Website
	extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

	protected $storeManager;


	public function __construct(
		\Magento\Backend\Block\Context $context,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		$data = []
	) {
		$this->storeManger = $storeManagerInterface;
		parent::__construct($context, $data);

	}

	/**
	 * @param \Magento\Framework\DataObject $row
	 *
	 * @return int
	 */
	public function render(\Magento\Framework\DataObject $row)
	{
		return $this->storeManger->getStore($this->_getValue($row))
			->getWebsiteId();
	}
}
