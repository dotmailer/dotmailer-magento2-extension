<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    private $ruleFactory;
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Save constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Escaper $escaper
    ) {
        parent::__construct($context);
        $this->rulesResource = $rulesResource;
        $this->ruleFactory  = $rulesFactory;
        $this->storeManager = $storeManagerInterface;
        $this->escaper      = $escaper;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Dotdigitalgroup_Email::exclusion_rules');
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        if ($this->getRequest()->getParams()) {
            $data = $this->getRequest()->getParams();
            try {
                $ruleModel = $this->ruleFactory->create();
                $id = $data['id'];

                if ($data['website_ids']) {
                    foreach ($data['website_ids'] as $websiteId) {
                        $result = $ruleModel->checkWebsiteBeforeSave(
                            $websiteId,
                            $data['type'],
                            $id
                        );
                        if (!$result) {
                            $websiteName = $this->storeManager->getWebsite(
                                $websiteId
                            )->getName();
                            $this->messageManager->addErrorMessage(
                                __(
                                    'Rule already exist for website '
                                    . $websiteName
                                    . '. You can only have one rule per website.'
                                )
                            );
                            $this->_redirect(
                                '*/*/edit',
                                [
                                    'id' => $id
                                ]
                            );
                            return;
                        }
                    }
                }

                $this->rulesResource->load($ruleModel, $id);

                if ($id != $ruleModel->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Wrong rule specified.')
                    );
                }

                $this->evaluateRequestParams($data, $ruleModel);

                $this->_getSession()->setPageData($ruleModel->getData());

                $this->rulesResource->save($ruleModel);

                $this->messageManager->addSuccessMessage(
                    __('The rule has been saved.')
                );
                $this->_getSession()->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect(
                        '*/*/edit',
                        ['id' => $ruleModel->getId()]
                    );

                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __(
                        'An error occurred while saving the rule data. Please review the log and try again.'
                    )
                );
                $this->_getSession()->setPageData($data);
                $this->_redirect(
                    '*/*/edit',
                    ['id' => $id]
                );

                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * @param $data
     * @param $ruleModel
     */
    private function evaluateRequestParams($data, $ruleModel)
    {
        foreach ($data as $key => $value) {
            if ($key != 'form_key') {
                if ($key == 'condition') {
                    if (is_array($value)) {
                        unset($value['__empty']);
                    }
                }
                $ruleModel->setData($key, $value);
            }
        }
    }
}
