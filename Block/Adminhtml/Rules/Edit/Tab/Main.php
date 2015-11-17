<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit\Tab;

class Main extends \Magento\Config\Block\System\Config\Form\Field
{

	protected $_registry;
	protected $_objectManager;
	/**
	 * Initialize form
	 * Add standard buttons
	 * Add "Save and Continue" button
	 */
	public function __construct(
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		\Magento\Framework\Registry $registry,
		\Magento\Backend\Block\Widget\Context $context)
	{
		$this->_objectManager = $objectManagerInterface;
		$this->_registry = $registry;
		parent::__construct($context);
	}
    /**
     * Prepare content for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Rule Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Rule Information');
    }

    /**
     * Returns status flag about this tab can be showed or not
     *
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Returns status flag about this tab hidden or not
     *
     * @return true
     */
    public function isHidden()
    {
        return false;
    }

    protected function _prepareForm()
    {
        $model = $this->_registry->registry('current_ddg_rule');

        $form = $this->_objectManager('Magento\Framework\Data\Form');
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset',
            array('legend' => __('Rule Information'))
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name' => 'name',
            'label' => __('Rule Name'),
            'title' => __('Rule Name'),
            'required' => true,
        ));

        $fieldset->addField('type', 'select', array(
            'label'     => __('Rule Type'),
            'title'     => __('Rule Type'),
            'name'      => 'type',
            'required' => true,
            'options'   => array(
                \Dotdigitalgroup\Email\Model\Rules::ABANDONED => 'Abandoned Cart Exclusion Rule',
                \Dotdigitalgroup\Email\Model\Rules::REVIEW => 'Review Email Exclusion Rule',
            ),
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => __('Status'),
            'title'     => __('Status'),
            'name'      => 'status',
            'required' => true,
            'options'    => array(
                '1' => __('Active'),
                '0' => __('Inactive'),
            ),
        ));

        if (!$model->getId()) {
            $model->setData('status', '0');
        }


        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
