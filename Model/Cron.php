<?php

namespace Dotdigitalgroup\Email\Model;

use Magento\ImportExport\Model\Export\Adapter\Csv;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;

class Cron
{
	protected $csv;

	protected $_automationFactory;
	protected $_proccessorFactory;
	protected $_catalogFactory;
	protected $_subscriberFactory;
	protected $_guestFactory;
	protected $_wishlistFactory;
	protected $_orderFactory;
	protected $_reviewFactory;
	protected $_quoteFactory;
	protected $_syncOrderFactory;
	protected $_campaignFactory;


	public function __construct(
		\Dotdigitalgroup\Email\Model\Sync\CampaignFactory $campaignFactory,
		\Dotdigitalgroup\Email\Model\Sync\OrderFactory  $syncOrderFactory,
		\Dotdigitalgroup\Email\Model\Sales\QuoteFactory $quoteFactory,
		\Dotdigitalgroup\Email\Model\Sync\ReviewFactory $reviewFactory,
		\Dotdigitalgroup\Email\Model\Sales\OrderFactory $orderFactory,
		\Dotdigitalgroup\Email\Model\Sync\WishlistFactory $wishlistFactory,
		\Dotdigitalgroup\Email\Model\Customer\GuestFactory $guestFactory,
		\Dotdigitalgroup\Email\Model\Newsletter\SubscriberFactory $subscriberFactory,
		\Dotdigitalgroup\Email\Model\Sync\CatalogFactory $catalogFactorty,
		\Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
		\Dotdigitalgroup\Email\Model\Sync\AutomationFactory $automationFactory,
		FilterBuilder $filterBuilder,
		Csv $csv,
		\Psr\Log\LoggerInterface $logger,
		SearchCriteriaBuilder $searchCriteriaBuilder,
		ProductRepositoryInterface $productRepository,
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Dotdigitalgroup\Email\Model\Apiconnector\Contact $contact
	) {
		$this->_campaignFactory = $campaignFactory;
		$this->_syncOrderFactory = $syncOrderFactory;
		$this->_quoteFactory = $quoteFactory;
		$this->_reviewFactory = $reviewFactory;
		$this->_orderFactory = $orderFactory;
		$this->_wishlistFactory = $wishlistFactory->create();
		$this->_guestFactory = $guestFactory;
		$this->_subscriberFactory = $subscriberFactory;
		$this->_catalogFactory = $catalogFactorty;
		$this->_proccessorFactory = $proccessorFactory;
		$this->_automationFactory = $automationFactory;
		$this->productRepository = $productRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->filterBuilder = $filterBuilder;
		$this->csv = $csv;
		$this->contact = $contact;
	}

	/**
	 * CRON FOR CONTACTS SYNC
	 *
	 * @return mixed
	 */
	public function contactSync()
	{

		//run the sync for contacts
		$result = $this->contact->sync();
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
		$result = $this->_catalogFactory->create()
			->sync();
		return $result;
	}

	public function export()
	{
		$items = $this->getProducts();
		$this->writeToFile($items);
	}


	/**
	 * CRON FOR EMAIL IMPORTER PROCESSOR
	 */
	public function emailImporter()
	{
		return $this->_proccessorFactory->create()->processQueue();
	}

	/**
	 * CRON FOR SUBSCRIBERS AND GUEST CONTACTS
	 */
	public function subscribersAndGuestSync()
	{
		//sync subscribers
		$subscriberModel = $this->_subscriberFactory->create();
		$result = $subscriberModel->sync();

		//sync guests
		$this->_guestFactory->create()->sync();

		return $result;
	}

	/**
	 * CRON FOR SYNC REVIEWS and REGISTER ORDER REVIEW CAMPAIGNS
	 */
	public function reviewsAndWishlist()
	{
		//sync reviews
        $this->reviewSync();
		//sync wishlist
		$this->_wishlistFactory->sync();
	}

	/**
	 * review sync
	 */
	public function reviewSync()
	{
		//find orders to review and register campaign
		$this->_orderFactory->create()->createReviewCampaigns();
		//sync reviews
		$result = $this->_reviewFactory->create()->sync();
		return $result;
	}


	/**
	 * CRON FOR ABANDONED CARTS
	 */
	public function abandonedCarts()
	{
		$this->_quoteFactory->create()->proccessAbandonedCarts();
	}

	/**
	 * CRON FOR AUTOMATION
	 */
	public function syncAutomation()
	{
		$this->_automationFactory->create()->sync();

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

	/**
	 * Send email campaigns.
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function sendCampaigns()
	{
		$this->_campaignFactory->create()->sendCampaigns();
	}


	/**
	 * CRON FOR ORDER TRANSACTIONAL DATA
	 */
	public function orderSync()
	{
		// send order
		$orderResult = $this->_syncOrderFactory->create()->sync();
		return $orderResult;
	}
}