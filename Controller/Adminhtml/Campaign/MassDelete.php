<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Campaign;

use Dotdigitalgroup\Email\Controller\Adminhtml\Campaign as CampaignController;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends CampaignController
{

    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    public $campaign;

    /**
     * MassDelete constructor.
     *
     * @param \Magento\Backend\App\Action\Context          $context
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaign
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaign
    ) {
    
        $this->campaign = $campaign;

        parent::__construct($context);
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $searchIds = $this->getRequest()->getParam('selected');

        if (!is_array($searchIds)) {
            $this->messageManager->addErrorMessage(__('Please select campaigns.'));
        } else {
            try {
                //@codingStandardsIgnoreStart
                foreach ($searchIds as $searchId) {
                    $model = $this->campaign->create()
                        ->setId($searchId);
                    $model->delete();
                }
                //@codingStandardsIgnoreEnd
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
