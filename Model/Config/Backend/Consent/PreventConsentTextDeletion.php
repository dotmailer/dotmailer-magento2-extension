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

class PreventConsentTextDeletion extends Value
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
     * Prevent consent text being deleted if Email marketing consent is enabled.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (!empty($value)) {
            return parent::beforeSave();
        }

        if ($this->isConsentEnabled()) {
            throw new ValidatorException(
                __(
                    'Opt-in consent text must be set if Email marketing consent is enabled.'
                )
            );
        }

        return parent::beforeSave();
    }

    /**
     * Check if consent is enabled or inherited.
     *
     * @return bool
     */
    private function isConsentEnabled(): bool
    {
        $inheritedIsConsentEnabled = $this->_config->isSetFlag(
            Config::XML_PATH_CONSENT_EMAIL_ENABLED,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );

        /** @var \Laminas\Http\Request $request */
        $request = $this->request;
        $groups = $request->getPost('groups');
        $isConsentBeingEnabled = $groups['email']['fields']['enabled']['value'] ?? false;

        return $inheritedIsConsentEnabled || $isConsentBeingEnabled;
    }
}
