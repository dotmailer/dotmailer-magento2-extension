<?php

namespace Dotdigitalgroup\Email\Model\Product\ImageType;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\App\Area;
use Magento\Framework\View\ConfigInterface as ViewConfig;
use Magento\Theme\Model\Config\Customization as ThemeCustomizationConfig;
use Magento\Theme\Model\ResourceModel\Theme\Collection as ThemeCollection;

class ImageTypeService
{
    /**
     * @var ViewConfig
     */
    private $viewConfig;

    /**
     * @var ThemeCustomizationConfig
     */
    private $themeCustomizationConfig;

    /**
     * @var ThemeCollection
     */
    private $themeCollection;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var array
     */
    private $viewImages = [];

    /**
     * ImageTypeService constructor.
     * @param ViewConfig $viewConfig
     * @param ThemeCustomizationConfig $themeCustomizationConfig
     * @param ThemeCollection $themeCollection
     */
    public function __construct(
        ViewConfig $viewConfig,
        ThemeCustomizationConfig $themeCustomizationConfig,
        ThemeCollection $themeCollection
    ) {
        $this->viewConfig = $viewConfig;
        $this->themeCustomizationConfig = $themeCustomizationConfig;
        $this->themeCollection = $themeCollection;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get view images data from themes.
     *
     * @return array
     */
    public function getViewImages(): array
    {
        if (empty($this->viewImages)) {
            $this->setViewImages();
        }

        return $this->viewImages;
    }

    /**
     *
     */
    private function setViewImages()
    {
        /** @var Theme $theme */
        foreach ($this->getThemesInUse() as $theme) {
            $config = $this->viewConfig->getViewConfig(
                [
                    'area' => Area::AREA_FRONTEND,
                    'themeModel' => $theme,
                ]
            );
            $images = $config->getMediaEntities(
                'Magento_Catalog',
                ImageHelper::MEDIA_TYPE_CONFIG_NODE
            );
            foreach ($images as $imageId => $imageData) {
                if (isset($imageData['type'])
                    && isset($imageData['width'])
                    && isset($imageData['height'])
                ) {
                    $this->viewImages[$imageId] = $imageData;
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getThemesInUse(): array
    {
        $themesInUse = [];
        $registeredThemes = $this->themeCollection->loadRegisteredThemes();
        $storesByThemes = $this->themeCustomizationConfig->getStoresByThemes();
        $keyType = is_integer(key($storesByThemes)) ? 'getId' : 'getCode';
        foreach ($registeredThemes as $registeredTheme) {
            if (array_key_exists($registeredTheme->$keyType(), $storesByThemes)) {
                $themesInUse[] = $registeredTheme;
            }
        }
        return $themesInUse;
    }
}
