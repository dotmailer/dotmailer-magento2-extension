<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Catalog;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\Backend\App\Action
{


    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    protected $catalog;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context         $context
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
    ) {
        $this->catalog = $catalogFactory;

        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('id');
        if (!is_array($searchIds)) {
            $this->messageManager->addErrorMessage(__('Please select catalog.'));
        } else {
            try {
                foreach ($searchIds as $searchId) {
                    $model = $this->catalog->create()
                        ->setId($searchId);
                    $model->delete();
                }
                $this->messageManager->addSuccessMessage(__('Total of %1 record(s) were deleted.', count($searchIds)));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }
}
