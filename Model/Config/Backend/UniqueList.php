<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class UniqueList extends Value
{
    public const DOTDIGITAL_LIST_CONFIG_PATH = 'sync_settings/addressbook';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate unique list selection.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $selectedValues = [];
        $inheritedValues = $this->getInheritedValues();
        $comparisonValues = $this->getComparisonValues($inheritedValues);
        $fields = array_filter(array_keys($comparisonValues), function (string $key) use ($inheritedValues) {
            return in_array($key, array_keys($inheritedValues));
        });

        foreach ($fields as $field) {
            if ($field === 'allow_non_subscribers') {
                continue;
            }
            $value = (array_key_exists($field, $comparisonValues)) ? $comparisonValues[$field] : "0";
            if ($value == "0") {
                continue;
            }
            if ($value !== null && in_array($value, $selectedValues)) {
                throw new ValidatorException(
                    __(
                        'Choose different lists to avoid mixing your Customers, Subscribers and Guests. <a href="https://support.dotdigital.com/en/articles/8199589-map-lists-in-magento-open-source-and-adobe-commerce" target="_blank">Learn more.</a>' //@phpcs:ignore Generic.Files.LineLength.TooLong
                    )
                );
            }
            $selectedValues[] = $value;
        }

        return parent::beforeSave();
    }

    /**
     * Get comparison values.
     *
     * @param array $inheritedValues
     * @return array
     */
    private function getComparisonValues(array $inheritedValues): array
    {
        $configData = $this->_data['fieldset_data'];

        foreach ($configData as $key => $value) {
            if (($value === null || $value === '') && array_key_exists($key, $inheritedValues)) {
                $configData[$key] = $inheritedValues[$key];
            }
        }
        return $configData;
    }

    /**
     * Get the values this scope would inherit from its parent scope.
     *
     * Store scope inherits from its website, website and default scope from default.
     *
     * @return array
     */
    private function getInheritedValues(): array
    {
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = null;

        if ($this->getScope() === ScopeInterface::SCOPE_STORES) {
            try {
                $scope = ScopeInterface::SCOPE_WEBSITE;
                $scopeId = $this->storeManager->getStore($this->getScopeId())->getWebsiteId();
            } catch (NoSuchEntityException $e) {
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
                $scopeId = null;
            }
        }

        return (array) $this->_config->getValue(static::DOTDIGITAL_LIST_CONFIG_PATH, $scope, $scopeId);
    }
}
