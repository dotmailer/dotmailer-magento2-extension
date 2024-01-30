<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Controller\Adminhtml\Run;

use Dotdigitalgroup\Email\Console\Command\Provider\SyncProvider;
use Dotdigitalgroup\Email\Model\Cron\JobChecker;
use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;

class Sync extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Dotdigitalgroup_Email::config';

    private const CRON_JOB_CODES_MAP = [
        'catalog' => 'ddg_automation_catalog_sync',
        'customer' => 'ddg_automation_customer_sync',
        'subscriber' => 'ddg_automation_subscriber_sync',
        'guest' => 'ddg_automation_guest_sync',
        'order' => 'ddg_automation_order_sync',
        'importer' => 'ddg_automation_importer',
        'review' => 'ddg_automation_reviews_and_wishlist',
        'wishlist' => 'ddg_automation_reviews_and_wishlist',
        'template' => 'ddg_automation_email_templates'
    ];

    /**
     * @var SyncProvider
     */
    private $syncProvider;

    /**
     * @var JobChecker
     */
    private $jobChecker;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * Sync controller.
     *
     * Handles requests from Developer sync buttons.
     *
     * @param SyncProvider $syncProvider
     * @param JobChecker $jobChecker
     * @param Context $context
     */
    public function __construct(
        SyncProvider $syncProvider,
        JobChecker $jobChecker,
        Context $context
    ) {
        $this->syncProvider = $syncProvider;
        $this->jobChecker = $jobChecker;
        $this->request = $context->getRequest();
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * Execute.
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setRefererUrl();

        $params = $this->request->getParams();
        $syncType = $params['sync-type'];
        $syncClass = $this->getSyncClass($syncType);

        /** @var SyncInterface $syncClass */
        if (!$syncClass instanceof SyncInterface) {
            $this->messageManager->addErrorMessage(
                sprintf('Sync type not recognised: "%s".', $syncType)
            );
            return $redirect;
        }

        $cronJobCode = self::CRON_JOB_CODES_MAP[$syncType];

        if ($this->jobChecker->hasAlreadyBeenRun($cronJobCode)) {
            $this->messageManager->addNoticeMessage(
                sprintf('%s cron is currently running.', $cronJobCode)
            );
            return $redirect;
        }

        $result = $syncClass->sync();
        $message = $result['message'] ?? 'Done.';

        $this->messageManager->addSuccessMessage($message);
        return $redirect;
    }

    /**
     * Get sync class.
     *
     * @param string $syncType
     * @return null
     */
    private function getSyncClass(string $syncType)
    {
        $syncClass = null;
        $name = $syncType . 'Factory';
        $availableSyncs = $this->syncProvider->getAvailableSyncs();

        if (isset($availableSyncs[$name])) {
            $syncClass = $availableSyncs[$name]['factory']->create(['data' => ['web' => true]]);
        }

        return $syncClass;
    }
}
