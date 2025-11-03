<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Dotdigitalgroup\Email\Model\Config\Source\Tracking\EmailCaptureLayouts;
use Exception;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Backend model for email capture selectors configuration field
 */
class EmailCaptureSelectors extends Value
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var EmailCaptureLayouts
     */
    private $emailCaptureLayouts;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Json $serializer
     * @param EmailCaptureLayouts $emailCaptureLayouts
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Json $serializer,
        EmailCaptureLayouts $emailCaptureLayouts,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        $this->emailCaptureLayouts = $emailCaptureLayouts;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Process data after load
     *
     * @return void
     */
    protected function _afterLoad()
    {
        $value = $this->getConfigurationValue();

        try {
            if (!is_array($value)) {
                return;
            }
            $fieldArrayFormat = [];
            $index = 0;
            foreach ($value as $layout => $selectors) {
                $fieldArrayFormat['_' . time() . '_' . $index] = [
                    'layout' => $layout,
                    'selectors' => is_array($selectors) ? implode(', ', $selectors) : (string)$selectors
                ];
                $index++;
            }
            $this->setValue($fieldArrayFormat);

        } catch (Exception $e) {
            return;
        }
    }

    /**
     * Prepare data before save - convert back to original format
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        if (is_array($value)) {
            $formattedValue = array_reduce($value, function ($carry, $item) {
                if (isset($item['layout']) && isset($item['selectors'])) {
                    $layout = $this->mapKeyToLayout($item['layout']);
                    if ($layout) {
                        $selectors = array_map('trim', explode(',', $item['selectors']));
                        $carry[$layout] = $selectors;
                    }
                }
                return $carry;
            }, []);
            $this->setValue($this->serializer->serialize($formattedValue));
        }
        return parent::beforeSave();
    }

    /**
     * Get configuration value as array
     *
     * @return array
     */
    public function getConfigurationValue(): array
    {
        $value = $this->getValue() ?? '{}';
        return $this->serializer->unserialize($value) ?: [];
    }

    /**
     * Map numeric array keys to layout identifiers
     *
     * This is a fallback when layout dropdown values aren't submitted properly
     *
     * @param string|int $key
     * @return string|null
     */
    private function mapKeyToLayout($key)
    {
        $layouts =  array_keys($this->emailCaptureLayouts->getLayouts());

        if (is_numeric($key) && isset($layouts[$key])) {
            return $layouts[$key];
        }

        if (in_array($key, $layouts)) {
            return $key;
        }

        return null;
    }
}
