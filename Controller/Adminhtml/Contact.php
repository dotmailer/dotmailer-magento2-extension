<?php
namespace Dotdigitalgroup\Email\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Magento\Framework\Registry;



class Contact extends Action
{

    protected $contactFactory;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @param Registry $registry
     * @param RedirectFactory $resultRedirectFactory
     * @param Context $context
     */
    public function __construct(
        Registry $registry,
        ContactFactory $contactFactory,
        RedirectFactory $resultRedirectFactory,
        Context $context

    )
    {
        $this->coreRegistry = $registry;
        $this->contactFactory = $contactFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        parent::__construct($context);
    }


    protected function initContact()
    {
        $authorId  = (int) $this->getRequest()->getParam('contact_id');
        /** @var \Sample\News\Model\Author $author */
        $author    = $this->contactFactory->create();
        if ($authorId) {
            $author->load($authorId);
        }
        $this->coreRegistry->register('sample_news_author', $author);
        return $author;
    }

}
