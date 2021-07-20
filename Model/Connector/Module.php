<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Magento\Framework\Module\ModuleListInterface;

class Module
{
    const MODULE_NAME = 'Dotdigitalgroup_Email';
    const MODULE_DESCRIPTION = 'Engagement Cloud for Magento 2';

    /**
     * @var ModuleListInterface
     */
    private $fullModuleList;

    /**
     * Module constructor.
     * @param ModuleListInterface $moduleListInterface
     */
    public function __construct(
        ModuleListInterface $moduleListInterface
    ) {
        $this->fullModuleList = $moduleListInterface;
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
                'version' => $this->fullModuleList->getOne(self::MODULE_NAME)['setup_version']
            ]
        );
        return $modules;
    }
}
