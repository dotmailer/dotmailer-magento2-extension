<?php


namespace Dotdigitalgroup\Email\Block\Adminhtml\Grid\Edit\Tab;

/**
 * Blog post edit form main tab
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
	/**
	 * @var \Magento\Store\Model\System\Store
	 */
	protected $_systemStore;

	/**
	 * @var \Magento\Cms\Model\Wysiwyg\Config
	 */
	protected $_wysiwygConfig;

	protected $_status;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Framework\Registry $registry
	 * @param \Magento\Framework\Data\FormFactory $formFactory
	 * @param \Magento\Store\Model\System\Store $systemStore
	 * @param array $data
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Data\FormFactory $formFactory,
		\Magento\Store\Model\System\Store $systemStore,
		\Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
		array $data = []
	) {
		$this->_systemStore = $systemStore;
		$this->_wysiwygConfig = $wysiwygConfig;
		parent::__construct($context, $registry, $formFactory, $data);
	}

	/**
	 * Prepare form
	 *
	 * @return $this
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	protected function _prepareForm()
	{
		$model = $this->_coreRegistry->registry('email_contact_data');

		$isElementDisabled = false;

		/** @var \Magento\Framework\Data\Form $form */
		$form = $this->_formFactory->create();

		$form->setHtmlIdPrefix('page_');

		$fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Grid Information')]);

		if ($model->getId()) {
			$fieldset->addField('email_contact_id', 'hidden', ['name' => 'email_contact_id']);
		}

		$fieldset->addField(
			'title',
			'text',
			[
				'name' => 'title',
				'label' => __('Contact Title'),
				'title' => __('Contact Title'),
				'required' => true,
				'disabled' => $isElementDisabled
			]
		);

		$wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);

		$contentField = $fieldset->addField(
			'content',
			'editor',
			[
				'name' => 'content',
				'style' => 'height:36em;',
				'required' => true,
				'disabled' => $isElementDisabled,
				'config' => $wysiwygConfig
			]
		);

		// Setting custom renderer for content field to remove label column
		$renderer = $this->getLayout()->createBlock(
			'Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element'
		)->setTemplate(
			'Magento_Cms::page/edit/form/renderer/content.phtml'
		);
		$contentField->setRenderer($renderer);


		$dateFormat = $this->_localeDate->getDateFormat(
			\IntlDateFormatter::SHORT
		);

//		$fieldset->addField(
//			'email',
//			'date',
//			[
//				'name' => 'publish_date',
//				'label' => __('Publish Date'),
//				'date_format' => $dateFormat,
//				'disabled' => $isElementDisabled,
//				'class' => 'validate-date validate-date-range date-range-custom_theme-from'
//			]
//		);

		$fieldset->addField(
			'email',
			'select',
			[
				'label' => __('Email'),
				'title' => __('Email'),
				'name' => 'email',
				'required' => true,
				//'options' => $this->_status->getOptionArray(),
				//'disabled' => $isElementDisabled
			]
		);
		if (!$model->getId()) {
			$model->setData('email', $isElementDisabled ? '0' : '1');
		}

		$form->setValues($model->getData());
		$this->setForm($form);

		return parent::_prepareForm();
	}

	/**
	 * Prepare label for tab
	 *
	 * @return \Magento\Framework\Phrase
	 */
	public function getTabLabel()
	{
		return __('Contact Information');
	}

	/**
	 * Prepare title for tab
	 *
	 * @return \Magento\Framework\Phrase
	 */
	public function getTabTitle()
	{
		return __('Contact Information');
	}

	/**
	 * {@inheritdoc}
	 */
	public function canShowTab()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isHidden()
	{
		return false;
	}

	/**
	 * Check permission for passed action
	 *
	 * @param string $resourceId
	 * @return bool
	 */
	protected function _isAllowedAction($resourceId)
	{
		return $this->_authorization->isAllowed($resourceId);
	}
}