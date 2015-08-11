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

	public function __construct(
		ProductRepositoryInterface $productRepository,
		SearchCriteriaBuilder $searchCriteriaBuilder,
		FilterBuilder $filterBuilder,
		Csv $csv,
		\Psr\Log\LoggerInterface $logger,
		Apiconnector\Contact $contact
	) {
		$this->productRepository = $productRepository;
		$this->searchCriteriaBuilder = $searchCriteriaBuilder;
		$this->filterBuilder = $filterBuilder;
		$this->csv = $csv;
		$this->_logger = $logger;
		$this->contact = $contact;

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
//		$subscriberResult = $this->subscribersAndGuestSync();

//		if(isset($subscriberResult['message']) && isset($result['message']))
//			$result['message'] = $result['message'] . ' - ' . $subscriberResult['message'];
//		return $result;
	}

	public function export()
	{
		$this->_logger->error('cron is running');
		$items = $this->getProducts();
		$this->writeToFile($items);
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