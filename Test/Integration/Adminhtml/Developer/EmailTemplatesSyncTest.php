<?php

namespace Dotdigitalgroup\Email\Tests\Integration\Adminhtml\Developer;

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

    /**
     * @var string
     */
    public $url = 'backend/dotdigitalgroup_email/run/templatesync';

    public function setUp() :void
    {
        parent::setUp();
        $this->resource = 'Dotdigitalgroup_Email::config';
        $this->uri = $this->url;
    }
    /**
     * Test the redirection when sync email templates.
     */
    public function testEmailTemplatesSync()
    {
        $this->dispatch($this->url);

        $this->assertTrue($this->getResponse()->isRedirect(), 'Redirect back was expected.');
        $this->assertEquals(302, $this->getResponse()->getHttpResponseCode());
    }
}
