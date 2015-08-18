<?php

namespace Dotdigitalgroup\Email\Model;

use Magento\ImportExport\Model\Export\Adapter\Csv;
use Dotdigitalgroup\Email\Model\Apiconnector;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use \Psr\Log\LoggerInterface;

class Cron
{
	protected $csv;

	protected $_logger;
	protected $_objectManager;

	public function __construct(
		FilterBuilder $filterBuilder,
		Csv $csv,
		\Psr\Log\LoggerInterface $logger,
		SearchCriteriaBuilder $searchCriteriaBuilder,
		ProductRepositoryInterface $productRepository,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		Apiconnector\Contact $contact
	) {
		$this->productRepository = $productRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->filterBuilder = $filterBuilder;
		$this->csv = $csv;
		$this->_logger = $logger;
		$this->contact = $contact;
		$this->_objectManager = $objectManager;

		//mark the running state
		$this->_logger->error('cron is running');
	}

	/**
	 * CRON FOR CONTACTS SYNC
	 *
	 * @return mixed
	 */
	public function contactSync()
	{
		//run the sync for contacts
		$this->contact->sync();
		//run subscribers and guests sync
		$subscriberResult = $this->subscribersAndGuestSync();

		if(isset($subscriberResult['message']) && isset($result['message']))
			$result['message'] = $result['message'] . ' - ' . $subscriberResult['message'];
		return $result;
	}

	/**
	 * CRON FOR CATALOG SYNC
	 */
	public function catalogSync()
	{
		$result = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Sync\Catalog')->sync();
		return $result;
	}

	public function export()
	{
		$this->_logger->error('cron is running');
		$items = $this->getProducts();
		$this->writeToFile($items);
	}


	/**
	 * CRON FOR EMAIL IMPORTER PROCESSOR
	 */
	public function emailImporter()
	{
		$importer = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor');
		return $importer->processQueue();
	}

	/**
	 * CRON FOR SUBSCRIBERS AND GUEST CONTACTS
	 */
	public function subscribersAndGuestSync()
	{
		//sync subscribers
		$subscriberModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Newsletter\Subscriber')->sync();

		//unsubscribe suppressed contacts
		$subscriberModel->unsubscribe();

		//sync guests
		$this->_objectManager->create('Dotdigitalgroup\Email\Model\Customer\Guest')->sync();

		return;
	}

	/**
	 * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS
	 */
	public function reviewsAndWishlist()
	{
		//sync reviews
		$this->reviewSync();
		//sync wishlist
		$this->_objectManager->create('Dotdigitalgroup\Email\Model\Wishlist')->sync();
	}

	/**
	 * review sync
	 */
	public function reviewSync()
	{
		//find orders to review and register campaign
		$this->_objectManager->create('Dotdigitalgroup\Email\Model\Sales\Order')
			->createReviewCampaigns();
		//sync reviews
		$result = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Review')->sync();
		return $result;
	}


	/**
	 * CRON FOR ABANDONED CARTS
	 */
	public function abandonedCarts()
	{
		$this->_objectManager->create('Dotdigitalgroup\Email\Model\Sales\Quote')
			->proccessAbandonedCarts();
	}

	/**
	 * order sync
	 *
	 * @return mixed
	 */
	public function orderSync()
	{
		// send order
		$orderResult = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Sync\Order')->sync();
		return $orderResult;
	}

	public function getProducts()
	{
		$filters = [];
		$now = new \DateTime();
		$interval = new \DateInterval('P1Y');
		$lastWeek = $now->sub($interval);

		$filters[] = $this->filterBuilder
			->setField('created_at')
			->setConditionType('gt')
			->setValue($lastWeek->format('Y-m-d H:i:s'))
			->create();

		$this->searchCriteriaBuilder->addFilters($filters);

		$searchCriteria = $this->searchCriteriaBuilder->create();
		$searchResults = $this->productRepository->getList($searchCriteria);


		return $searchResults->getItems();
	}

	protected function writeToFile($items)
	{
		if (count($items) > 0) {
			$this->csv->setHeaderCols(['id', 'created_at', 'sku']);
			foreach ($items as $item) {
				$this->csv->writeRow(['id'=>$item->getId(), 'created_at' => $item->getCreatedAt(), 'sku' => $item->getSku()]);
			}
		}
	}
}