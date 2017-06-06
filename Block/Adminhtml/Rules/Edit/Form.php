<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{

    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     */
    public function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getData('action'),
                    'method' => 'post',
                ],
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
