<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Automation\UpdateFields;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Automation\UpdateFields\Update;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DdgQuoteFactory;
use Magento\Quote\Model\QuoteFactory as MagentoQuoteFactory;
use PHPUnit\Framework\TestCase;

class UpdateAbandonedCartFieldsTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var MagentoQuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $magentoQuoteFactoryMock;

    /**
     * @var DdgQuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ddgQuoteFactoryMock;

    /**
     * @var StoreManagerMock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var Update
     */
    private $class;

    /**
     * @var
     */
    private $websiteId = 10;

    /**
     * @var \Magento\Store\Model\Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteMock;

    /**
     * @var
     */
    private $quoteid;

    /**
     * @var
     */
    private $parentStoreName;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * @var
     */
    private $engagementCloudApi;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sales\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ddgQuoteMock;

    /**
     * Prepare data
     */
    protected function setUp()
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->magentoQuoteFactoryMock = $this->createMock(MagentoQuoteFactory::class);
        $this->ddgQuoteFactoryMock = $this->createMock(DdgQuoteFactory::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->websiteMock = $this->createMock(\Magento\Store\Model\Website::class);

        $this->storeManagerMock->method("getWebsite")
            ->with($this->websiteId)
            ->willReturn($this->websiteMock);

        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteMock->expects($this->once())
            ->method("loadByIdWithoutStore")
            ->with($this->quoteid)
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())
            ->method("getAllItems")
            ->willReturn($this->quoteMock->toArray());

        $this->magentoQuoteFactoryMock
            ->expects($this->once())
            ->method("create")
            ->willReturn($this->quoteMock);

        $this->ddgQuoteMock = $this->createMock(\Dotdigitalgroup\Email\Model\Sales\Quote::class);
        $this->ddgQuoteFactoryMock
            ->expects($this->once())
            ->method("create")
            ->willReturn($this->ddgQuoteMock);

        $this->ddgQuoteMock
            ->expects($this->once())
            ->method("getMostExpensiveItems")
            ->with($this->quoteMock->toArray())
            ->willReturn($this->quoteMock);

        $this->engagementCloudApi = $this->createMock(\Dotdigitalgroup\Email\Model\Apiconnector\Client::class);

        $this->helperMock->method("getWebsiteApiClient")
            ->with($this->websiteMock)
            ->willReturn($this->engagementCloudApi);

        $this->class = new Update(
            $this->helperMock,
            $this->magentoQuoteFactoryMock,
            $this->ddgQuoteFactoryMock,
            $this->storeManagerMock
        );
    }

    public function testlastQuoteIdSentToEC()
    {
        $expectedECDataFieldname = "LAST_QUOTE_ID";
        $expectedEmail = "test.email@emailsim.io";
        $this->mockWebsite($expectedECDataFieldname, "", "", "");

        $this->engagementCloudApi->method("updateContactDatafieldsByEmail")
            ->will($this->returnCallback(
                function ($email, $data) use (&$actualEmail, &$actualData) {
                    $actualEmail = $email;
                    $actualData = $data;
                }
            ));

        $this->class->updateAbandonedCartDatafields($expectedEmail, $this->websiteId, $this->quoteid, "");
        $this->assertEquals($expectedEmail, $actualEmail);
        $this->assertEquals($expectedECDataFieldname, $actualData[0]["Key"]);
        $this->assertEquals($this->quoteid, $actualData[0]["Value"]);
    }

    public function testCustomerStoreNameSentToEC()
    {
        $expectedECDataFieldname = "CUSTOMER_STORE_NAME";
        $expectedEmail = "test.email@emailsim.io";
        $this->mockWebsite("", "", $expectedECDataFieldname, "");

        $this->engagementCloudApi->method("updateContactDatafieldsByEmail")
            ->will($this->returnCallback(
                function ($email, $data) use (&$actualEmail, &$actualData) {
                    $actualEmail = $email;
                    $actualData = $data;
                }
            ));

        $this->class->updateAbandonedCartDatafields($expectedEmail, $this->websiteId, $this->quoteid, $this->parentStoreName);
        $this->assertEquals($expectedEmail, $actualEmail);
        $this->assertEquals($expectedECDataFieldname, $actualData[0]["Key"]);
        $this->assertEquals($this->quoteid, $actualData[0]["Value"]);
    }

    public function testCustomerWebsiteNameSentToEC()
    {
        $expectedECDataFieldname = "CUSTOMER_WEBSITE_NAME";
        $expectedEmail = "test.email@emailsim.io";
        $this->mockWebsite("", "", "", $expectedECDataFieldname);

        $this->engagementCloudApi->method("updateContactDatafieldsByEmail")
            ->will($this->returnCallback(
                function ($email, $data) use (&$actualEmail, &$actualData) {
                    $actualEmail = $email;
                    $actualData = $data;
                }
            ));

        $this->class->updateAbandonedCartDatafields($expectedEmail, $this->websiteId, $this->quoteid, $this->parentStoreName);
        $this->assertEquals($expectedEmail, $actualEmail);
        $this->assertEquals($expectedECDataFieldname, $actualData[0]["Key"]);
        $this->assertEquals($this->quoteid, $actualData[0]["Value"]);
    }

    public function testAbandonedProductNameSentToEC()
    {
        $expectedECDataFieldname = "ABANDONED_PRODUCT_NAME";
        $expectedEmail = "test.email@emailsim.io";
        $this->mockWebsite("", $expectedECDataFieldname, "", "");

        $this->engagementCloudApi->method("updateContactDatafieldsByEmail")
            ->will($this->returnCallback(
                function ($email, $data) use (&$actualEmail, &$actualData) {
                    $actualEmail = $email;
                    $actualData = $data;
                }
            ));

        $this->class->updateAbandonedCartDatafields($expectedEmail, $this->websiteId, $this->quoteid, $this->parentStoreName);
        $this->assertEquals($expectedEmail, $actualEmail);
        $this->assertEquals($expectedECDataFieldname, $actualData[0]["Key"]);
        $this->assertEquals($this->quoteid, $actualData[0]["Value"]);
    }

    public function testIfDataIsEmptyApiDontCall()
    {
        $expectedEmail = "test.email@emailsim.io";

        $this->mockWebsite("", "", "", "");

        $this->engagementCloudApi
            ->expects($this->never())
            ->method("updateContactDatafieldsByEmail");

        $this->class->updateAbandonedCartDatafields($expectedEmail, $this->websiteId, $this->quoteid, $this->parentStoreName);
    }

    public function testIfDataIsEmptyApiClientDontCall()
    {
        $expectedEmail = "test.email@emailsim.io";

        $this->mockWebsite("", "", "", "");

        $this->helperMock
            ->expects($this->never())
            ->method("getWebsiteApiClient");

        $this->class->updateAbandonedCartDatafields($expectedEmail, $this->websiteId, $this->quoteid, $this->parentStoreName);
    }

    /**
     * @param $expectedECDataFieldname1
     * @param $expectedECDataFieldname2
     * @param $expectedECDataFieldname3
     * @param $expectedECDataFieldname4
     */
    private function mockWebsite($expectedECDataFieldname1, $expectedECDataFieldname2, $expectedECDataFieldname3, $expectedECDataFieldname4)
    {
        $this->websiteMock
            ->method("getConfig")
            ->withConsecutive(
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_MAPPING_LAST_QUOTE_ID],
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_PRODUCT_NAME],
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_STORE_NAME],
                [\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_WEBSITE_NAME]
            )
            ->willReturnOnConsecutiveCalls(
                $expectedECDataFieldname1,
                $expectedECDataFieldname2,
                $expectedECDataFieldname3,
                $expectedECDataFieldname4
            );
    }
}
