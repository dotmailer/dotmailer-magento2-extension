<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

class UniqueList extends Value
{

    /**
     * Validate unique list selection.
     *
     * @return Value
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $selectedValues = [];
        $comparisonValues = $this->getComparisonValues();
        $fields = [
            'customers',
            'subscribers',
            'guests',
            'sms_subscribers'
        ];
        
        foreach ($fields as $field) {
            $value = $comparisonValues[$field] ?? null;
            if ($value == "0") {
                continue;
            }
            if ($value !== null && in_array($value, $selectedValues)) {
                throw new ValidatorException(__(
                    sprintf(
                        str_replace(["\r", "\n"], '', 'Choose different lists to avoid mixing your Customers,
                                     Subscribers, and Guests. <a href="%s" target="_blank">Learn more.</a>
                        '),
                        str_replace(["\r", "\n"], '', 'https://support.dotdigital.com
                        /en/articles/8199589-map-lists-in-magento-open-source-and-adobe-commerce')
                    )
                ));
            }
            $selectedValues[] = $value;
        }

        return parent::beforeSave();
    }

    /**
     * Get comparison values.
     *
     * @return array
     */
    private function getComparisonValues(): array
    {
        $inheritedValues = $this->_config->getValue("sync_settings/addressbook", 'default');
        $configData = $this->_data['fieldset_data'];

        foreach ($configData as $key => $value) {
            if (empty($value)) {
                $configData[$key] = $inheritedValues[$key];
            }
        }
        return $configData;
    }
}
