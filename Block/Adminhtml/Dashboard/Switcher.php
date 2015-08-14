<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Dashboard_Switcher extends Mage_Adminhtml_Block_Template
{
	protected function _prepareLayout()
	{
		$this->setTemplate('system/config/switcher.phtml');
		return parent::_prepareLayout();
	}

	/**
	 *
	 * @return array
	 */
	public function getStoreSelectOptions()
	{
		$section = $this->getRequest()->getParam('section');

		$curWebsite = $this->getRequest()->getParam('website');
		$curStore   = $this->getRequest()->getParam('store');

		$storeModel = Mage::getSingleton('adminhtml/system_store');
		/* @var $storeModel Mage_Adminhtml_Model_System_Store */

		$url = Mage::getModel('adminhtml/url');

		$options = array();
		$options['default'] = array(
			'label'    => Mage::helper('adminhtml')->__('Default Config'),
			'url'      => $url->getUrl('*/*/*', array('section'=>$section)),
			'selected' => !$curWebsite && !$curStore,
			'style'    => 'background:#ccc; font-weight:bold;',
		);

		foreach ($storeModel->getWebsiteCollection() as $website) {
			$websiteShow = false;
			foreach ($storeModel->getGroupCollection() as $group) {
				if ($group->getWebsiteId() != $website->getId()) {
					continue;
				}
				$groupShow = false;
				foreach ($storeModel->getStoreCollection() as $store) {
					if ($store->getGroupId() != $group->getId()) {
						continue;
					}
					if (!$websiteShow) {
						$websiteShow = true;
						$options['website_' . $website->getId()] = array(
							'label'    => $website->getName(),
							'url'      => $url->getUrl('*/*/*', array('section'=>$section, 'website'=>$website->getId())),
							'selected' => !$curStore && $curWebsite == $website->getId(),
							'style'    => 'padding-left:16px; background:#DDD; font-weight:bold;',
						);
					}
					if (!$groupShow) {
						$groupShow = true;
						$options['group_' . $group->getId() . '_open'] = array(
							'is_group'  => true,
							'is_close'  => false,
							'label'     => $group->getName(),
							'style'     => 'padding-left:32px;'
						);
					}
					$options['store_' . $store->getId()] = array(
						'label'    => $store->getName(),
						'url'      => $url->getUrl('*/*/*', array('section'=>$section, 'store'=>$store->getId())),
						'selected' => $curStore == $store->getId(),
						'style'    => '',
					);
				}
				if ($groupShow) {
					$options['group_' . $group->getId() . '_close'] = array(
						'is_group'  => true,
						'is_close'  => true,
					);
				}
			}
		}

		return $options;
	}

	/**
	 * Return store switcher hint html
	 *
	 * @return mixed
	 */
	public function getHintHtml()
	{
		return Mage::getBlockSingleton('adminhtml/store_switcher')->getHintHtml();
	}
}