<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection as CampaignCollection;
use Dotdigitalgroup\Email\Setup\Schema;
use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\ObjectManager;

class CampaignORMTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    protected $campaignResource;

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
        $this->campaignResource->save($model);
        return $model;
    }

    /**
     * @param Campaign $campaign
     * @param CampaignCollection $collection
     * @return void
     */
    private function assertCollectionContains(Campaign $campaign, CampaignCollection $collection)
    {
        $message = sprintf('Expected campaign with ID "%s" not found in collection', $campaign->getId());
        $this->assertContains($campaign->getId(), array_keys($collection->getItems()), $message);
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        /** @var CampaignCollection $collection */
        $collection = ObjectManager::getInstance()->create(CampaignCollection::class);
        $this->campaignResource = ObjectManager::getInstance()->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Campaign::class
        );
        $collection->walk(function (Campaign $campaign) {
            $this->campaignResource->delete($campaign);
        });
    }

    /**
     * @return void
     */
    public function testCampaignTableExists()
    {
        /** @var ResourceConnection $resource */
        $resource = ObjectManager::getInstance()->get(ResourceConnection::class);
        $tableName = $resource->getTableName(Schema::EMAIL_CAMPAIGN_TABLE);
        $this->assertTrue($resource->getConnection('default')->isTableExists($tableName));
    }

    /**
     * @magentoDbIsolation enabled
     * @return void
     */
    public function testModelAndResourceModelORMConfiguration()
    {
        $model = $this->createCampaign('1', 'test@example.com');

        $loadedModel = $this->createCampaignModel();
        $this->campaignResource->load($loadedModel, $model->getId());

        $this->assertNotNull($model->getId());
        $this->assertSame($model->getData('email'), $loadedModel->getData('email'));
    }

    /**
     * @magentoDbIsolation enabled
     * @return void
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
     * @return void
     */
    public function testCanBeLoadedByQuoteId()
    {
        $storeId = 1;
        $dummyQuoteId = 3;
        $model = $this->createCampaign(1, 'foo@example.com');
        $model->setData('store_id', $storeId);
        $model->setData('quote_id', $dummyQuoteId);
        $this->campaignResource->save($model);

        /** @var Campaign $emptyModel */
        $emptyModel = ObjectManager::getInstance()->create(Campaign::class);
        $loadedModel = $emptyModel->loadByQuoteId($dummyQuoteId, $storeId);

        $this->assertSame($model->getId(), $loadedModel->getId());
    }
}
