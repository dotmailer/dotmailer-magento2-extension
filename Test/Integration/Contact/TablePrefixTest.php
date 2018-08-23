<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\Schema;
use Magento\TestFramework\ObjectManager;

class TablePrefixTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    public $objectManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    public $contactResource;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @return void
     */
    public function setup()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->contactResource = $this->objectManager->get(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class);
        $this->deploymentConfig = $this->objectManager->get(\Magento\Framework\App\DeploymentConfig::class);
    }

    public function testTableWithPrefix()
    {
        $tablePrefix = (string)$this->deploymentConfig->get(
            \Magento\Framework\Config\ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
        );
        $tableName = Schema::EMAIL_CONTACT_TABLE;
        if ($tablePrefix) {
            $tableName = $tablePrefix . $tableName;
        }
        $this->assertEquals($tableName, $this->contactResource->getTable(Schema::EMAIL_CONTACT_TABLE));
    }
}
