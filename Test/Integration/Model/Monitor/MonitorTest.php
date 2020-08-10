<?php

namespace Dotdigitalgroup\Email\Test\Integration\Model\Monitor;

use Dotdigitalgroup\Email\Model\Monitor;
use Dotdigitalgroup\Email\Model\Monitor\EmailNotifier;
use Dotdigitalgroup\Email\Model\Monitor\Importer\Monitor as ImporterMonitor;
use Magento\Framework\FlagManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use PHPUnit\Framework\TestCase;

class MonitorTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var mixed
     */
    private $monitor;

    /**
     * @var mixed
     */
    private $flagManager;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->monitor = $this->objectManager->create(Monitor::class);
        $this->flagManager = $this->objectManager->create(FlagManager::class);
    }

    public static function loadFixture()
    {
        Resolver::getInstance()->requireDataFixture(
            'Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_email_importer_review_record.php'
        );

        Resolver::getInstance()->requireDataFixture(
            // @codingStandardsIgnoreLine
        'Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_email_importer_catalog_record_status_failed.php'
        );
    }

    /**
     * @magentoDataFixture Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_email_importer_catalog_record_status_failed.php
     * @magentoConfigFixture connector_developer_settings/system_alerts/email_notifications 1
     */
    public function testThatGivenCatalogImporterErrorWePopulateFlagWithCorrectCode()
    {
        $this->monitor->run();
        $flagData = $this->flagManager->getFlagData(ImporterMonitor::MONITOR_ERROR_FLAG_CODE);

        $this->assertEquals("Catalog", reset($flagData));
    }

    /**
     * @magentoDataFixture Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_email_importer_catalog_record_status_failed.php
     * @magentoConfigFixture connector_developer_settings/system_alerts/email_notifications 1
     */
    public function testThatSentFlagIsNotChangedIfMonitorRunsInsideTimeWindow()
    {
        $this->monitor->run();
        $firstSentTime = $this->flagManager->getFlagData(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE);

        $this->monitor->run();
        $secondSentTime = $this->flagManager->getFlagData(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE);

        $this->assertEquals($firstSentTime, $secondSentTime);
    }

    /**
     * @magentoDataFixture Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_email_importer_catalog_record.php
     * @magentoConfigFixture connector_developer_settings/system_alerts/email_notifications 1
     */
    public function testThatIfErrorsNotFoundFlagsAreNotSet()
    {
        $this->monitor->run();

        $flagData = $this->flagManager->getFlagData(ImporterMonitor::MONITOR_ERROR_FLAG_CODE);
        $emailSentTime = $this->flagManager->getFlagData(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE);

        $this->assertNull($flagData);
        $this->assertNull($emailSentTime);
    }

    /**
     * @magentoDataFixture Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_multiple_email_importer_records_status_failed.php
     * @magentoConfigFixture connector_developer_settings/system_alerts/email_notifications 1
     */
    public function testThatForMultipleTypesWithErrorsSentFlagIsSet()
    {
        $this->monitor->run();

        $flagData = $this->flagManager->getFlagData(ImporterMonitor::MONITOR_ERROR_FLAG_CODE);
        $emailSentTime = $this->flagManager->getFlagData(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE);

        $this->assertTrue(in_array("Catalog", $flagData));
        $this->assertTrue(in_array("Reviews", $flagData));

        $this->assertNotNull($emailSentTime);
    }

    /**
     * @magentoDataFixture Dotdigitalgroup_Email::Test/Integration/_files/Importer/create_multiple_email_importer_records.php
     * @magentoConfigFixture connector_developer_settings/system_alerts/email_notifications 1
     */
    public function testThatForMultipleTypesWithNoErrorsFlagsAreNotSet()
    {
        $this->monitor->run();

        $flagData = $this->flagManager->getFlagData(ImporterMonitor::MONITOR_ERROR_FLAG_CODE);
        $emailSentTime = $this->flagManager->getFlagData(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE);

        $this->assertNull($flagData);
        $this->assertNull($emailSentTime);
    }

    /**
     * @magentoDataFixture loadFixture
     * @magentoConfigFixture connector_developer_settings/system_alerts/email_notifications 1
     */
    public function testThatForMultipleTypesWithMixedErrorsSentFlagIsSet()
    {
        $this->monitor->run();

        $flagData = $this->flagManager->getFlagData(ImporterMonitor::MONITOR_ERROR_FLAG_CODE);
        $emailSentTime = $this->flagManager->getFlagData(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE);

        $this->assertTrue(in_array("Catalog", $flagData));
        $this->assertFalse(in_array("Reviews", $flagData));

        $this->assertNotNull($emailSentTime);
    }
}
