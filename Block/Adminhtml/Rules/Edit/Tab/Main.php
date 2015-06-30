<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Rules_Edit_Tab_Main
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
        return Mage::helper('salesrule')->__('Rule Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('salesrule')->__('Rule Information');
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
        $model = Mage::registry('current_ddg_rule');

        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset',
            array('legend' => Mage::helper('ddg')->__('Rule Information'))
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name' => 'name',
            'label' => Mage::helper('ddg')->__('Rule Name'),
            'title' => Mage::helper('ddg')->__('Rule Name'),
            'required' => true,
        ));

        $fieldset->addField('type', 'select', array(
            'label'     => Mage::helper('ddg')->__('Rule Type'),
            'title'     => Mage::helper('ddg')->__('Rule Type'),
            'name'      => 'type',
            'required' => true,
            'options'   => array(
                Dotdigitalgroup_Email_Model_Rules::ABANDONED => 'Abandoned Cart Exclusion Rule',
                Dotdigitalgroup_Email_Model_Rules::REVIEW => 'Review Email Exclusion Rule',
            ),
        ));

        $fieldset->addField('status', 'select', array(
            'label'     => Mage::helper('ddg')->__('Status'),
            'title'     => Mage::helper('ddg')->__('Status'),
            'name'      => 'status',
            'required' => true,
            'options'    => array(
                '1' => Mage::helper('ddg')->__('Active'),
                '0' => Mage::helper('ddg')->__('Inactive'),
            ),
        ));

        if (!$model->getId()) {
            $model->setData('status', '0');
        }

        if (Mage::app()->isSingleStoreMode()) {
            $websiteId = Mage::app()->getStore(true)->getWebsiteId();
            $fieldset->addField('website_ids', 'hidden', array(
                'name'     => 'website_ids[]',
                'value'    => $websiteId
            ));
            $model->setWebsiteIds($websiteId);
        } else {
            $field = $fieldset->addField('website_ids', 'multiselect', array(
                'name'     => 'website_ids[]',
                'label'     => Mage::helper('ddg')->__('Websites'),
                'title'     => Mage::helper('ddg')->__('Websites'),
                'required' => true,
                'values'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteValuesForForm()
            ));
            $renderer = $this->getLayout()->createBlock('adminhtml/store_switcher_form_renderer_fieldset_element');
            $field->setRenderer($renderer);
        }

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
