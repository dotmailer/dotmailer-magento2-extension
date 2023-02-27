<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

use Dotdigitalgroup\Email\Model\ExclusionRule\RuleValidator;
use Dotdigitalgroup\Email\Model\Rules;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::exclusion_rules';

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
     * @var RuleValidator
     */
    private $ruleValidator;

    /**
     * Save constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param RuleValidator $ruleValidator
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules $rulesResource,
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        RuleValidator $ruleValidator
    ) {
        parent::__construct($context);
        $this->rulesResource = $rulesResource;
        $this->ruleFactory  = $rulesFactory;
        $this->storeManager = $storeManagerInterface;
        $this->ruleValidator = $ruleValidator;
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

                $this->ruleValidator->validate($ruleModel);
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
            } catch (\Magento\Framework\Exception\ValidatorException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $this->_redirect(
                    '*/*/edit',
                    [
                        'id' => $id
                    ]
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $this->_redirect('*/*/');
    }

    /**
     * Evaluate request parameters.
     *
     * @param array $data
     * @param Rules $ruleModel
     *
     * @return Rules
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
