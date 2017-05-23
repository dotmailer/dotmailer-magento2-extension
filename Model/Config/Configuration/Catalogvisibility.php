<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

class Catalogvisibility implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    private $productVisibility;

    /**
     * Catalogvisibility constructor.
     *
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Visibility $productVisibility
    ) {
        $this->productVisibility = $productVisibility;
    }

    /**
     * Return options.
     *
     * @return mixed
     */
    public function toOptionArray()
    {
        $visibilities
            = $this->productVisibility->getAllOptions();
        $options[] = [
            'label' => __('---- Default Option ----'),
            'value' => '0',
        ];
        foreach ($visibilities as $visibility) {
            $options[] = [
                'label' => $visibility['label'],
                'value' => $visibility['value'],
            ];
        }
        return $options;
    }
}
