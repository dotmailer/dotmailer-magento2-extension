<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules
     */
    private $rulesResource;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
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
     * Execute method.
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParams()) {
            $data = $this->getRequest()->getParams();
            $id = $this->getRequest()->getParam('id');
            try {
                $ruleModel = $this->ruleFactory->create();

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
                            return $this->_redirect(
                                '*/*/edit',
                                [
                                    'id' => $id
                                ]
                            );
                        }
                    }
                }

                $this->rulesResource->load($ruleModel, $id);

                if ($id != $ruleModel->getId()) {
                    $this->messageManager->addErrorMessage('Wrong rule specified.');
                    return $this->_redirect('*/*/');
                }

                $ruleModel = $this->evaluateRequestParams($data, $ruleModel);
                $this->rulesResource->save($ruleModel);

                $this->messageManager->addSuccessMessage(
                    __('The rule has been saved.')
                );
                $this->_getSession()->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $this->_redirect(
                        '*/*/edit',
                        ['id' => $ruleModel->getId()]
                    );
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $this->_redirect('*/*/');
    }

    /**
     * @param array $data
     * @param \Dotdigitalgroup\Email\Model\Rules $ruleModel
     *
     * @return null
     */
    private function evaluateRequestParams($data, $ruleModel)
    {
        foreach ($data as $key => $value) {
            if ($key !== 'form_key' && $key !== 'key' && $key !== 'active_tab') {
                if ($key == 'condition') {
                    if (is_array($value)) {
                        unset($value['__empty']);
                    }
                    $ruleModel->setData('conditions', $value);
                } else {
                    $ruleModel->setData($key, $value);
                }
            }
        }

        return $ruleModel;
    }
}
