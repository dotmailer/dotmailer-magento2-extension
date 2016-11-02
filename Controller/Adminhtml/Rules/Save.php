<?php

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Rules;

class Save extends \Magento\Backend\App\AbstractAction
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\Rules
     */
    protected $rules;

    /**
     * Save constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Dotdigitalgroup\Email\Model\Rules $rules
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Dotdigitalgroup\Email\Model\Rules $rules,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        parent::__construct($context);
        $this->rules = $rules;
        $this->_storeManager = $storeManagerInterface;
    }

    /**
     * Check the permission to run it.
     *
     * @return bool
     */
    protected function _isAllowed()
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
                $model = $this->rules;
                $data = $this->getRequest()->getParams();
                $id = $this->getRequest()->getParam('id');

                if ($data['website_ids']) {
                    foreach ($data['website_ids'] as $websiteId) {
                        $result = $model->checkWebsiteBeforeSave(
                            $websiteId,
                            $data['type'],
                            $id
                        );
                        if (!$result) {
                            $websiteName = $this->_storeManager->getWebsite(
                                $websiteId
                            )->getName();
                            $this->messageManager->addError(
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

                $model->load($id);
                if ($id != $model->getId()) {
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
                        $model->setData($key, $value);
                    }
                }

                $this->_getSession()->setPageData($model->getData());

                $model->save();
                $this->messageManager->addSuccess(
                    __('The rule has been saved.')
                );
                $this->_getSession()->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect(
                        '*/*/edit',
                        ['id' => $model->getId()]
                    );

                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addError(
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
