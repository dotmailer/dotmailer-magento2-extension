<?php

namespace Dotdigitalgroup\Email\Model\Config\Configuration;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\ImageType\ImageTypeService;

class ImageTypes implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var ImageTypeService
     */
    private $imageTypeService;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Image role is set via the virtual types defined in di.xml
     *
     * @var string
     */
    private $imageRole;

    /**
     * ImageTypes constructor.
     * @param ImageTypeService $imageTypeService
     * @param Data $helper
     * @param Logger $logger
     * @param $imageRole
     */
    public function __construct(
        ImageTypeService $imageTypeService,
        Data $helper,
        Logger $logger,
        $imageRole
    ) {
        $this->imageTypeService = $imageTypeService;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->imageRole = $imageRole;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = $this->setDefaultOption();
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

        if (!$this->helper->isEnabled($website)) {
            return $options;
        }

        // Use saved options
        if ($savedOptions = $this->imageTypeService->getOptions()) {
            return $options + $savedOptions;
        }

        // Fetch new options
        foreach ($this->imageTypeService->getViewImages() as $viewImageId => $data) {
            $options[] = [
                'value' => $viewImageId,
                'label' => sprintf('%s (%s x %s)', $viewImageId, $data['width'], $data['height'])
            ];
        }

        $this->imageTypeService->setOptions($options);

        return $options;
    }

    /**
     * @return array
     */
    private function setDefaultOption()
    {
        return ['value' => '0', 'label' => 'Default'];
    }
}
