<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Magento\Eav\Model\Entity\Attribute;

trait CustomAttributesTrait
{
    /**
     * @param Attribute|Attribute\AbstractAttribute $attribute
     * @param $attributeCode
     * @return mixed
     */
    private function getDropDownValues(Attribute $attribute, $attributeCode)
    {
        $options = $this->getOptions($attribute);

        if (!is_array($options)) {
            return '';
        }

        foreach ($options as $option) {
            if ($option['value'] === $this->model->getData($attributeCode)) {
                return $option['label'];
            }
        }
    }

    /**
     * @param Attribute|Attribute\AbstractAttribute $attribute
     * @param $attributeCode
     * @return string
     */
    private function getMultiSelectValues(Attribute $attribute, $attributeCode)
    {
        $options = $this->getOptions($attribute);
        $selectedOptions = explode(',', $this->model->getData($attributeCode));

        if (!is_array($options)) {
            return '';
        }

        $optionsToReturn = [];
        foreach ($options as $option) {
            if (in_array($option['value'], $selectedOptions)) {
                $optionsToReturn[] = $option['label'];
            }
        }

        return implode(', ', $optionsToReturn);
    }

    /**
     * @param $attribute
     * @return string
     */
    private function getOptions($attribute)
    {
        try {
            return $options = $attribute->getSource()->getAllOptions();
        } catch (\Exception $exception) {
            $this->logger->debug('Could not fetch options', [(string) $exception]);
            return '';
        }
    }
}
