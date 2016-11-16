<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    public $ruleFactory;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Dotdigitalgroup\Email\Model\RulesFactory  $rulesFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        parent::__construct($context);
        $this->ruleFactory  = $rulesFactory;
        $this->storeManager = $storeManagerInterface;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed(
            'Dotdigitalgroup_Email::exclusion_rules'
        );
    }

    /**
     * Execute method.
     */
    public function execute()
    {
        if ($this->getRequest()->getParams()) {
            try {
                $ruleModel = $this->ruleFactory->create();
                $data = $this->getRequest()->getParams();
                $id = $this->getRequest()->getParam('id');

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
                                    'id' => $this->getRequest()->getParam(
                                        'id'
                                    )
                                ]
                            );
                            return;
                        }
                    }
                }

                $ruleModel->getResource()->load($ruleModel, $id);

                if ($id != $ruleModel->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Wrong rule specified.')
                    );
                }

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

                $this->_getSession()->setPageData($ruleModel->getData());

                $ruleModel->getResource()->save($ruleModel);

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
                    ['id' => $this->getRequest()->getParam('id')]
                );

                return;
            }
        }
        $this->_redirect('*/*/');
    }
}
