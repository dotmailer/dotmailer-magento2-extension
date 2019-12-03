<?php

namespace Dotdigitalgroup\Email\Setup;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\User\Model\ResourceModel\User;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    /**
     * @var ReinitableConfigInterface
     */
    private $config;

    /**
     * @var UserCollectionFactory
     */
    private $userCollectionFactory;

    /**
     * @var User
     */
    private $userResource;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * UpgradeData constructor.
     * @param Data $helper
     * @param CollectionFactory $configCollectionFactory
     * @param ReinitableConfigInterface $config
     * @param UserCollectionFactory $userCollectionFactory
     * @param User $userResource
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Data $helper,
        CollectionFactory $configCollectionFactory,
        ReinitableConfigInterface $config,
        UserCollectionFactory $userCollectionFactory,
        User $userResource,
        EncryptorInterface $encryptor
    ) {
        $this->configCollectionFactory = $configCollectionFactory;
        $this->helper = $helper;
        $this->config = $config;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->userResource = $userResource;
        $this->encryptor = $encryptor;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (version_compare($context->getVersion(), '2.4.4', '<')) {
            //Encrypt api & transactional password for all websites
            $this->encryptAllPasswords();

            //Encrypt refresh token saved against admin users
            $this->encryptAllRefreshTokens();

            //Clear config cache
            $this->config->reinit();
        }

        if (version_compare($context->getVersion(), '2.5.0', '<')) {
            // Save config for allow non subscriber for features; AC and order review trigger campaign
            //For AC
            $this->helper->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEW_ALLOW_NON_SUBSCRIBERS,
                1,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );

            //For order review
            $this->helper->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CONTENT_ALLOW_NON_SUBSCRIBERS,
                1,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );

            //Clear config cache
            $this->config->reinit();
        }

        if (version_compare($context->getVersion(), '2.5.1', '<')) {
            // Save config for allow non subscriber contacts to sync
            $this->helper->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ALLOW_NON_SUBSCRIBERS,
                1,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );

            //Clear config cache
            $this->config->reinit();
        }

        $this->upgradeFourOhOne($setup, $context);

        $installer->endSetup();
    }

    /**
     * Encrypt all tokens
     */
    private function encryptAllRefreshTokens()
    {
        $userCollection = $this->userCollectionFactory->create()
            ->addFieldToFilter('refresh_token', ['notnull' => true]);

        foreach ($userCollection as $user) {
            $this->encryptAndSaveRefreshToken($user);
        }
    }

    /**
     * Encrypt token and save
     *
     * @param \Magento\User\Model\User $user
     */
    private function encryptAndSaveRefreshToken($user)
    {
        $user->setRefreshToken(
            $this->encryptor->encrypt($user->getRefreshToken())
        );
        $this->userResource->save($user);
    }

    /**
     * Encrypt passwords and save for all websites
     */
    private function encryptAllPasswords()
    {
        $websites = $this->helper->getWebsites(true);
        $paths = [
            Config::XML_PATH_CONNECTOR_API_PASSWORD,
            Transactional::XML_PATH_DDG_TRANSACTIONAL_PASSWORD
        ];
        foreach ($websites as $website) {
            if ($website->getId() > 0) {
                $scope = ScopeInterface::SCOPE_WEBSITES;
            } else {
                $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            }

            foreach ($paths as $path) {
                $this->encryptAndSavePassword(
                    $path,
                    $scope,
                    $website->getId()
                );
            }
        }
    }

    /**
     * Encrypt already saved passwords
     *
     * @param string $path
     * @param string $scope
     * @param int $id
     */
    private function encryptAndSavePassword($path, $scope, $id)
    {
        $configCollection = $this->configCollectionFactory->create()
            ->addFieldToFilter('scope', $scope)
            ->addFieldToFilter('scope_id', $id)
            ->addFieldToFilter('path', $path)
            ->setPageSize(1);

        if ($configCollection->getSize()) {
            $value = $configCollection->getFirstItem()->getValue();
            if ($value) {
                $this->helper->saveConfigData(
                    $path,
                    $this->encryptor->encrypt($value),
                    $scope,
                    $id
                );
            }
        }
    }

    /**
     * Maps 'imported' data to 'processed' in email_catalog.
     * Released in 4.0.1, this replaces the previous updateThreeFourTwo.
     * For merchants on 3.4.2 <> 4.0.0 no data upgrade is required.
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    private function upgradeFourOhOne(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '3.4.2', '<')) {
            $catalogTable = $setup->getTable(Schema::EMAIL_CATALOG_TABLE);

            $setup->getConnection()->update(
                $catalogTable,
                [
                    'processed' => 1
                ],
                [
                    'imported' => 1,
                    'modified IS NULL OR modified = 0'
                ]
            );
        }
    }
}
