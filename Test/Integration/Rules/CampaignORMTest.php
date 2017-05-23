<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection as CampaignCollection;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\ObjectManager;

/**
 * Class CampaignORMTest
 * @package Dotdigitalgroup\Email\Model
 */
class CampaignORMTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Campaign
     */
    private function createCampaignModel()
    {
        return ObjectManager::getInstance()->create(Campaign::class);
    }

    /**
     * @param int|string $customerId
     * @param string $email
     * @return Campaign
     */
    private function createCampaign($customerId, $email)
    {
        $model = $this->createCampaignModel();
        $model->setData('email', $email);
        $model->setData('customer_id', $customerId);
        $model->setData('message', 'Test Message');
        $model->getResource()->save($model);
        return $model;
    }

    /**
     * @param Campaign $campaign
     * @param CampaignCollection $collection
     */
    private function assertCollectionContains(Campaign $campaign, CampaignCollection $collection)
    {
        $message = sprintf('Expected campaign with ID "%s" not found in collection', $campaign->getId());
        $this->assertContains($campaign->getId(), array_keys($collection->getItems()), $message);
    }

    protected function setUp()
    {
        /** @var CampaignCollection $collection */
        $collection = ObjectManager::getInstance()->create(CampaignCollection::class);
        $collection->walk(function (Campaign $campaign) {
            $campaign->getResource()->delete($campaign);
        });
    }

    public function testCampaignTableExists()
    {
        /** @var ResourceConnection $resource */
        $resource = ObjectManager::getInstance()->get(ResourceConnection::class);
        $tableName = $resource->getTableName('email_campaign');
        $this->assertTrue($resource->getConnection('default')->isTableExists($tableName));
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testModelAndResourceModelORMConfiguration()
    {
        $model = $this->createCampaign('1', 'test@example.com');

        $loadedModel = $this->createCampaignModel();
        $loadedModel->getResource()->load($loadedModel, $model->getId());

        $this->assertNotNull($model->getId());
        $this->assertSame($model->getData('email'), $loadedModel->getData('email'));
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testCollectionORMConfiguration()
    {
        $campaignA = $this->createCampaign(1, 'customerA@example.com');
        $campaignB = $this->createCampaign(2, 'customerB@example.com');

        /** @var CampaignCollection $collection */
        $collection = ObjectManager::getInstance()->create(CampaignCollection::class);
        $collection->load();

        $this->assertCollectionContains($campaignA, $collection);
        $this->assertCollectionContains($campaignB, $collection);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testCanBeLoadedByQuoteId()
    {
        $storeId = 1;
        $dummyQuoteId = 3;
        $model = $this->createCampaign(1, 'foo@example.com');
        $model->setData('store_id', $storeId);
        $model->setData('quote_id', $dummyQuoteId);
        $model->getResource()->save($model);

        /** @var Campaign $emptyModel */
        $emptyModel = ObjectManager::getInstance()->create(Campaign::class);
        $loadedModel = $emptyModel->loadByQuoteId($dummyQuoteId, $storeId);

        $this->assertSame($model->getId(), $loadedModel->getId());
    }
}
