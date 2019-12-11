<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\ResourceModel;

use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use PHPUnit\Framework\TestCase;

class ContactTest extends TestCase
{
    private $constructorMocks = [];
    private $resourceConnectionMock;

    /**
     * @var Contact
     */
    private $contactResourceModel;

    protected function setUp()
    {
        $this->makeMocks();

        $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->constructorMocks[\Magento\Framework\Model\ResourceModel\Db\Context::class]
            ->expects($this->any())
            ->method('getResources')
            ->willReturn($this->resourceConnectionMock);

        $contactResourceModel = new \ReflectionClass(Contact::class);
        $this->contactResourceModel = $contactResourceModel->newInstanceArgs($this->constructorMocks);
    }

    /**
     * Test unsubscribing with removed_at data from EC
     */
    public function testUnsubscribes()
    {
        $this->constructorMocks[\Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory::class]
            ->expects($this->once())
            ->method('create')
            ->willReturn(new class() {
                public function addFieldToSelect()
                {
                    return $this;
                }
                public function addFieldToFilter($table, array $filter)
                {
                    return $this;
                }
                public function getData()
                {
                    return [
                        ['email' => 'i@resubscribedrecently.com', 'last_subscribed_at' => '2019-05-02 11:00:00'],
                        ['email' => 'someone@example.com', 'last_subscribed_at' => null],
                        ['email' => 'someoneelse@example.com', 'last_subscribed_at' => '2019-05-02 10:00:00'],
                        [
                            'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
                            'last_subscribed_at' => '2019-04-25 10:00:00'
                        ],
                    ];
                }
            });

        // anonymous class which will pretend to be the resource connection and steal it's warez
        $connection = new class() {
            public $wheres;
            public function update($table, array $columns, array $wheres)
            {
                $this->wheres = reset($wheres);
            }
        };

        $this->resourceConnectionMock->expects($this->at(0))
            ->method('getConnection')
            ->willReturn($connection);

        // call unsubscribe with resubscription check with some test data from EC
        $this->contactResourceModel->unsubscribeWithResubscriptionCheck([[
            'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
            'removed_at' => '2019-05-01 00:00:00',
        ], [
            'email' => 'someone@example.com',
            'removed_at' => '2019-05-01 00:00:00',
        ], [
            'email' => 'someoneelse@example.com',
            'removed_at' => '2019-05-01 00:00:00',
        ], [
            'email' => 'unknowninmagento@example.com',
            'removed_at' => '2019-05-01 00:00:00',
        ], [
            'email' => 'i@resubscribedrecently.com',
            'removed_at' => '2019-05-01 00:00:00',
        ]]);

        // we expect this address to be unsubscribed as it was suppressed more recently in EC
        $this->assertContains('ben@onboardingforlivechatforengagementcloudformagento.co.uk', $connection->wheres);
        // this one because it did not have a last subscribed date
        $this->assertContains('someone@example.com', $connection->wheres);
        // and not this one, as they resubscribed in Magento more recently than they were suppressed in EC
        $this->assertNotContains('i@resubscribedrecently.com', $connection->wheres);
    }

    /**
     * Make all constructor mocks
     */
    private function makeMocks()
    {
        foreach ([
             \Magento\Framework\Model\ResourceModel\Db\Context::class,
             \Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory::class,
             \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory::class,
             \Magento\Cron\Model\ScheduleFactory::class,
             \Dotdigitalgroup\Email\Model\Sql\ExpressionFactory::class,
             \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory::class,
             \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class,
             \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory::class,
             \Dotdigitalgroup\Email\Helper\Config::class,
        ] as $class) {
            $this->constructorMocks[$class] = $this->getMockBuilder($class)->disableOriginalConstructor()->getMock();
        }
    }
}
