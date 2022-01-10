<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;

class Module
{
    const MODULE_NAME = 'Dotdigitalgroup_Email';
    const MODULE_DESCRIPTION = 'Dotdigital for Magento 2';

    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var ReadFactory
     */
    protected $readFactory;

    /**
     * Module constructor.
     *
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param ReadFactory $readFactory
     */
    public function __construct(
        ComponentRegistrarInterface $componentRegistrar,
        ReadFactory $readFactory
    ) {
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
    }

    /**
     * @param array $modules
     * @return array
     */
    public function fetchActiveModules(array $modules = [])
    {
        array_unshift(
            $modules,
            [
                'name' => self::MODULE_DESCRIPTION,
                'version' => $this->getModuleVersion(self::MODULE_NAME)
            ]
        );
        return $modules;
    }

    /**
     * Get module composer version
     *
     * @param string $moduleName
     * @return \Magento\Framework\Phrase|string|void
     */
    public function getModuleVersion($moduleName)
    {
        $path = $this->componentRegistrar->getPath(
            \Magento\Framework\Component\ComponentRegistrar::MODULE,
            $moduleName
        );
        $directoryRead = $this->readFactory->create($path);
        $composerJsonData = $directoryRead->readFile('composer.json');
        $data = json_decode($composerJsonData);

        return !empty($data->version) ? $data->version : __('Read error!');
    }
}
