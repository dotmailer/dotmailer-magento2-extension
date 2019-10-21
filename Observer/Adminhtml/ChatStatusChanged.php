<?php
namespace Dotdigitalgroup\Email\Observer\Adminhtml;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;

/**
 * Validate api when saving creds in admin.
 */
class ChatStatusChanged implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * ApiValidator constructor.
     * @param Context $context
     * @param Config $config
     * @param ManagerInterface $messageManager
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Config $config,
        ManagerInterface $messageManager,
        Data $helper
    ) {
        $this->context = $context;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * Check API credentials when live chat is enabled
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();
        $this->config->setScopeAndWebsiteId($website);

        $groups = $this->context->getRequest()->getPost('groups');

        $enabled = $this->getEnabled($groups);

        if (!$enabled) {
            $this->config->deleteChatApiCredentials();
            return;
        } elseif (!is_null($this->config->getApiSpaceId())) {
            // if an API space ID is already set for this scope/website, we don't need to do anything more
            return;
        }

        $client = $this->helper->getWebsiteApiClient($website);
        $response = $client->setUpChatAccount();

        if (!$response || isset($response->message)) {
            $this->messageManager->addErrorMessage(__("There was a problem creating your chat account"));
            $this->config->setLiveChatStatus(false);
            $this->config->deleteChatApiCredentials();
            return;
        }

        $this->config->saveChatApiSpaceIdAndToken($response->apiSpaceID, $response->token)
            ->reinitialiseConfig();
    }

    /**
     * @param $groups
     * @return mixed
     */
    private function getEnabled($groups)
    {
        if (isset($groups['settings']['fields']['enabled']['value'])) {
            return (bool) $groups['settings']['fields']['enabled']['value'];
        }

       return 'Default';
    }
}
