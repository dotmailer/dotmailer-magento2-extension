<?php

namespace Dotdigitalgroup\Email\Model\Connector;

use Magento\Framework\Module\ModuleListInterface;

class Module
{
    const MODULE_NAME = 'Dotdigitalgroup_Email';
    const CHAT_MODULE = 'Dotdigitalgroup_Chat';
    const ENTERPRISE_MODULE = 'Dotdigitalgroup_Enterprise';
    const B2B_MODULE = 'Dotdigitalgroup_B2b';

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
     * Get current connector version.
     *
     * @return string
     */
    public function getConnectorVersion()
    {
        return $this->fullModuleList->getOne(self::MODULE_NAME)['setup_version'];
    }

    /**
     * @return bool
     */
    public function hasChatModule()
    {
        return $this->fullModuleList->has(self::CHAT_MODULE);
    }

    /**
     * Get chat connector version.
     *
     * @return string
     */
    public function getChatConnectorVersion()
    {
        return $this->fullModuleList->getOne(self::CHAT_MODULE)['setup_version'];
    }

    /**
     * @return bool
     */
    public function hasEnterpriseModule()
    {
        return $this->fullModuleList->has(self::ENTERPRISE_MODULE);
    }

    /**
     * Get Enterprise connector version.
     *
     * @return string
     */
    public function getEnterpriseConnectorVersion()
    {
        return $this->fullModuleList->getOne(self::ENTERPRISE_MODULE)['setup_version'];
    }

    /**
     * @return bool
     */
    public function hasB2bModule()
    {
        return $this->fullModuleList->has(self::B2B_MODULE);
    }

    /**
     * Get B2b connector version.
     *
     * @return string
     */
    public function getB2bConnectorVersion()
    {
        return $this->fullModuleList->getOne(self::B2B_MODULE)['setup_version'];
    }
}
