<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Rules_Edit_Tab_Conditions
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Prepare content for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('ddg')->__('Conditions');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('ddg')->__('Conditions');
    }

    /**
     * Returns status flag about this tab can be showen or not
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
        $model = Mage::registry('current_ddg_rule');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset',
            array('legend' => Mage::helper('ddg')->__('Exclusion Rule Conditions'))
        );

        $fieldset->addField('combination', 'select', array(
            'label'     => Mage::helper('ddg')->__('Conditions Combination Match'),
            'title'     => Mage::helper('ddg')->__('Conditions Combination Match'),
            'name'      => 'combination',
            'required'  => true,
            'options'   => array(
                '1' => Mage::helper('ddg')->__('ALL'),
                '2' => Mage::helper('ddg')->__('ANY'),
            ),
            'after_element_html' => '<small>Choose ANY if using multi line conditions for same attribute.
If multi line conditions for same attribute is used and ALL is chosen then multiple lines for same attribute will be ignored.</small>',
        ));

        $field = $fieldset->addField('condition', 'select', array(
            'name' => 'condition',
            'label' => Mage::helper('ddg')->__('Condition'),
            'title' => Mage::helper('ddg')->__('Condition'),
            'required' => true,
            'options'    => Mage::getModel('ddg_automation/adminhtml_source_rules_type')->toOptionArray(),
        ));
        $renderer = $this->getLayout()->createBlock('ddg_automation/adminhtml_config_rules_customdatafields');
        $field->setRenderer($renderer);

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
