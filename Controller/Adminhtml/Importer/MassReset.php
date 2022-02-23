<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Importer;

use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Magento\Framework\App\Request\Http;
use Magento\Backend\App\Action\Context;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;

class MassReset extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::importer';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Importer
     */
    protected $collectionResource;

    /**
     * @var Http
     */
    protected $_request;

    /**
     * @param Context $context
     * @param Importer $collectionResource
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Http $_request
     */
    public function __construct(
        Context $context,
        Importer $collectionResource,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Http $_request
    ) {
        $this->_request = $_request;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->collectionResource = $collectionResource;
        parent::__construct($context);
    }

    /**
     * Reset imports selected
     *
     * @return ResultInterface
     * @throws LocalizedException
     */
    public function execute(): ResultInterface
    {

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('*/*/');

        if (!$this->_request->isPost()) {
            throw new \Magento\Framework\Exception\NotFoundException(__('Page not found.'));
        }

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionFilteredIds  = $collection->addFieldToFilter('import_status', ['gt' => 1])->getAllIds();

        if (empty($collectionFilteredIds)) {
            $this->messageManager
                ->addNoticeMessage(
                    __('Selected record(s) are not eligible for reset.')
                );
            return $resultRedirect;
        }

        try {

            $massUpdatedRecordsCount = $this->collectionResource->massReset($collectionFilteredIds);
            $this->messageManager
                ->addSuccessMessage(
                    __('Total of %1 record(s) have been reset.', $massUpdatedRecordsCount)
                );

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect;
    }
}
