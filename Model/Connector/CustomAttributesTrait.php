<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Magento\Eav\Model\Entity\Attribute;

trait CustomAttributesTrait
{
    /**
     * Get selected dropdown value.
     *
     * @param Attribute $attribute
     * @param string $attributeCode
     * @return string
     */
    private function getSelectedDropDownValue(Attribute $attribute, $attributeCode)
    {
        $options = $this->getOptions($attribute);

        if (!is_array($options)) {
            return '';
        }

        foreach ($options as $option) {
            if ($option['value'] == $this->model->getData($attributeCode)) {
                return $option['label'];
            }
        }
        return '';
    }

    /**
     * Get multi-select values.
     *
     * @param Attribute $attribute
     * @param string $attributeCode
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
     * Get an attribute's options.
     *
     * @param Attribute $attribute
     * @return string|array
     */
    private function getOptions(Attribute $attribute)
    {
        try {
            return $attribute->getSource()->getAllOptions();
        } catch (\Exception $exception) {
            $this->logger->debug('Could not fetch options', [(string) $exception]);
            return '';
        }
    }
}
