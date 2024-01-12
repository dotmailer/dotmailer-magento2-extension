<?php

namespace Dotdigitalgroup\Email\Test\Integration\Adminhtml\Developer;

/**
 * @magentoAppArea adminhtml
 */
class EmailTemplatesSyncTest extends \Magento\TestFramework\TestCase\AbstractBackendController
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var string
     */
    public $model = \Dotdigitalgroup\Email\Model\Catalog::class;

    public function setUp() :void
    {
        parent::setUp();
        $this->resource = 'Dotdigitalgroup_Email::config';
    }
    /**
     * Test the redirection when sync email templates.
     */
    public function testEmailTemplatesSync()
    {
        $this->dispatch('backend/dotdigitalgroup_email/run/sync?sync-type=template');

        $this->assertTrue($this->getResponse()->isRedirect(), 'Redirect back was expected.');
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
    }
}
