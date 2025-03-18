<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend\Consent;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class RequireConsentText extends Value
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param RequestInterface $request
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        RequestInterface $request,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->request = $request;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Require consent text (both customer and subscriber texts) before enabling Email marketing consent.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if ($value == "0") {
            return parent::beforeSave();
        }

        if ($this->isConsentCustomerTextSetOrInherited() === false ||
            $this->isConsentSubscriberTextSetOrInherited() === false) {
            throw new ValidatorException(
                __(
                    'Please set all required opt-in consent text before enabling Email marketing consent.'
                )
            );
        }

        return parent::beforeSave();
    }

    /**
     * Check if consent customer text is set or inherited.
     *
     * @return bool
     */
    private function isConsentCustomerTextSetOrInherited(): bool
    {
        $inheritedConsentCustomerText = $this->_config->getValue(
            Config::XML_PATH_CONSENT_CUSTOMER_TEXT,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $consentCustomerText = $groups['email']['fields']['text_newsletter_registration_checkout']['value'] ?? null;

        return !empty($inheritedConsentCustomerText) || !empty($consentCustomerText);
    }

    /**
     * Check if consent subscriber text is set or inherited.
     *
     * @return bool
     */
    private function isConsentSubscriberTextSetOrInherited(): bool
    {
        $inheritedConsentSubscriberText = $this->_config->getValue(
            Config::XML_PATH_CONSENT_SUBSCRIBER_TEXT,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $consentSubscriberText = $groups['email']['fields']['text_newsletter_signup_form']['value'] ?? null;

        return !empty($inheritedConsentSubscriberText) || !empty($consentSubscriberText);
    }
}
