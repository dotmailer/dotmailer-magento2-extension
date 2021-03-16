<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules\Edit\Tab\Coupons;

use Dotdigitalgroup\Email\Data\Form\Element\SelectWithDescription;
use Magento\SalesRule\Model\RegistryConstants;
use Dotdigitalgroup\Email\Data\Form\Element\CouponUrlText;
use Dotdigitalgroup\Email\Helper\Data;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * Sales rule coupon
     *
     * @var \Magento\SalesRule\Helper\Coupon
     */
    protected $couponHelper;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\SalesRule\Helper\Coupon $salesRuleCoupon
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\SalesRule\Helper\Coupon $salesRuleCoupon,
        Data $helper,
        array $data = []
    ) {
        $this->couponHelper = $salesRuleCoupon;
        $this->helper = $helper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare coupon codes generation parameters form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        // get active rule ID (if set)
        $ruleId = $this->_coreRegistry
            ->registry(RegistryConstants::CURRENT_SALES_RULE)
            ->getId();

        $enableFields = $this->helper->isEnabled();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('ddg_coupons_');
        $fieldset = $form->addFieldset('information_fieldset', []);
        $fieldset->addType('coupon-url', CouponUrlText::class);
        $fieldset->addClass('ignore-validate');

        if (empty($this->helper->getPasscode())) {
            $enableFields = false;
            $commentField['after_element_html'] = <<<EOT
<small>
You have not set a passcode which will be sent with the coupon code request.
<a href="{$this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_dynamic_content'])}">Set one here</a>.
</small>
EOT;
        }

        if (!($this->helper->isEnabled())) {
            $commentField['after_element_html'] = <<<EOT
<small id="ddg-notice_coupon-builder-disabled">
An active Engagement Cloud account is required to use this feature.
Please enable your account
<a href="{$this->getUrl('adminhtml/system_config/edit', ['section' => 'connector_api_credentials'])}">here</a>.
</small>
EOT;
        }

        if (isset($commentField)) {
            $fieldset->addField('ddg_comment_field', 'note', $commentField);
        }

        $fieldset->addField('rule_id', 'hidden', [
            'name' => 'ddg_rule_id',
            'value' => $ruleId,
        ]);

        $fieldset->addField('enabled', 'hidden', [
            'name' => 'ddg_enabled',
            'value' => $enableFields,
        ]);

        $fieldset->addField('format', 'select', [
            'label' => __('Code Format'),
            'name' => 'format',
            'options' => $this->couponHelper->getFormatsList(),
            'required' => true,
            'value' => $this->couponHelper->getDefaultFormat(),
            'onchange' => 'window.updateEdcCouponUrl()',
        ]);

        $fieldset->addField('prefix', 'text', [
            'name' => 'prefix',
            'label' => __('Code Prefix'),
            'title' => __('Code Prefix'),
            'value' => $this->couponHelper->getDefaultPrefix(),
            'onkeyup' => 'window.updateEdcCouponUrl()',
        ]);

        $fieldset->addField('suffix', 'text', [
            'name' => 'suffix',
            'label' => __('Code Suffix'),
            'title' => __('Code Suffix'),
            'value' => $this->couponHelper->getDefaultSuffix(),
            'onkeyup' => 'window.updateEdcCouponUrl()',
        ]);

        $fieldset->addField('expires_after', 'text', [
            'name' => 'expires_after',
            'label' => __('Expires After (days)'),
            'title' => __('Expires After (days)'),
            'value' => '',
            'onkeyup' => 'window.updateEdcCouponUrl()',
        ]);

        $fieldset->addField('allow_resend', 'select', [
            'label' => __('Allow Resend'),
            'name' => 'allow_resend',
            'options' => $this->getYesNoOption(),
            'required' => true,
            'class' => 'field-has-description',
            'value' => $this->couponHelper->getDefaultFormat(),
            'onchange' => 'window.updateEdcCouponUrl()',
            'after_element_html' => __('This allows a previously generated coupon code ' .
                'to be resent to the same customer. If set to no, a new coupon will be generated.'),
        ]);

        $fieldset->addField('cancel_send', 'select', [
            'label' => __('Cancel Send If Used'),
            'name' => 'allow_resend',
            'options' => $this->getYesNoOption(false),
            'required' => true,
            'class' => 'field-has-description',
            'value' => $this->couponHelper->getDefaultFormat(),
            'onchange' => 'window.updateEdcCouponUrl()',
            'after_element_html' => __('Enable this to ensure the send will be cancelled ' .
                '(if the coupon has been used) or regenerated (if the coupon has expired). ' .
                'If set to no, the latest generated coupon will be displayed.'),
        ]);

        $couponBaseUrl = sprintf(
            'connector/email/coupon/id/%s/code/%s',
            $ruleId,
            $this->helper->getPasscode() ?: '[' . __('PLEASE SET A PASSCODE') . ']'
        );
        $couponFieldParams = [
            'name' => 'edc_url',
            'label' => __('Coupon Codes URL'),
            'title' => __('Coupon Codes URL'),
            'class' => 'ddg-dynamic-content',
            'readonly' => 1,
            'data-baseurl' => $this->helper->generateDynamicUrl() . $couponBaseUrl,
            'data-email-merge-field' => 'code_email/@EMAIL@',
        ];

        $fieldset->addField('edc_url', 'coupon-url', $couponFieldParams);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @param bool $defaultYes
     * @return array
     */
    private function getYesNoOption(bool $defaultYes = true)
    {
        $yesNoOptions = [
            '1' => __('Yes'),
            '0' => __('No')
        ];

        return $defaultYes
            ? $yesNoOptions
            : array_reverse($yesNoOptions);
    }
}
