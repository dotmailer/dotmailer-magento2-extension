<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

class UniqueList extends Value
{
    public const DOTDIGITAL_LIST_CONFIG_PATH = 'sync_settings/addressbook';

    /**
     * Validate unique list selection.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $selectedValues = [];
        $inheritedValues = $this->_config->getValue(static::DOTDIGITAL_LIST_CONFIG_PATH);
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
            if (empty($value) && array_key_exists($key, $inheritedValues)) {
                $configData[$key] = $inheritedValues[$key];
            }
        }
        return $configData;
    }
}
