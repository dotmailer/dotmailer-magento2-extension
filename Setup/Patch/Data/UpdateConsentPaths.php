<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Setup\Patch\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateConsentPaths implements DataPatchInterface
{
    private const CONSENT_ENABLED = 'dotmailer_consent_subscriber_enabled';
    private const SUBSCRIBER_TEXT = 'dotmailer_consent_subscriber_text';
    private const CUSTOMER_TEXT = 'dotmailer_consent_customer_text';

    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $consentPaths = 'connector_configuration/consent/dotmailer';
        $this->moduleDataSetup->getConnection()->startSetup();
        $select = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['*']
        )->where(
            "path LIKE '%{$consentPaths}%'"
        );

        foreach ($this->moduleDataSetup->getConnection()->fetchAll($select) as $configRow) {
            $elements = explode('/', $configRow['path']);
            $consentConfigType = $elements[2] ?? null;

            switch ($consentConfigType) {
                case self::CONSENT_ENABLED:
                    $path = Config::XML_PATH_CONSENT_EMAIL_ENABLED;
                    break;
                case self::SUBSCRIBER_TEXT:
                    $path = Config::XML_PATH_CONSENT_SUBSCRIBER_TEXT;
                    break;
                case self::CUSTOMER_TEXT:
                    $path = Config::XML_PATH_CONSENT_CUSTOMER_TEXT;
                    break;
            }

            if (isset($path)) {
                $this->updateConsentRow($configRow, $path);
            }
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Update consent record row paths.
     *
     * @param array $configRow
     * @param string $path
     * @return void
     */
    private function updateConsentRow($configRow, $path)
    {
        if ($this->keyAlreadyExists($path)) {
            $this->moduleDataSetup->getConnection()->delete(
                $this->moduleDataSetup->getTable('core_config_data'),
                ['path = ?' => $configRow['path']]
            );
            return;
        }

        $this->moduleDataSetup->getConnection()->update(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'path' => $path,
            ],
            [
                'path = ?' => $configRow['path']
            ]
        );
    }

    /**
     * Check if newer path name equivalent already exists.
     *
     * @param string $path
     *
     * @return bool
     */
    private function keyAlreadyExists($path)
    {
        $existingKey = $this->moduleDataSetup->getConnection()->select()->from(
            $this->moduleDataSetup->getTable('core_config_data'),
            ['*']
        )->where(
            'path = ?',
            $path
        );

        $result = $this->moduleDataSetup->getConnection()->fetchAll($existingKey);
        return count($result) > 0;
    }
}
