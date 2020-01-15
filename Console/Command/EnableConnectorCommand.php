<?php

namespace Dotdigitalgroup\Email\Console\Command;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\DataFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Config\Model\ResourceModel\Config;
use Dotdigitalgroup\Email\Helper\Config as EmailConfig;

class EnableConnectorCommand extends Command
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DataFactory
     */
    private $dataFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $resourceConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param DataFactory $dataFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $resourceConfig
     * @param EncryptorInterface $encryptor
     * @param ReinitableConfigInterface $reinitableConfig
     */
    public function __construct(
        DataFactory $dataFactory,
        StoreManagerInterface $storeManager,
        Config $resourceConfig,
        EncryptorInterface $encryptor,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->dataFactory = $dataFactory;
        $this->storeManager = $storeManager;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->reinitableConfig = $reinitableConfig;
        parent::__construct();
    }

    /**
     * Configure this command
     */
    protected function configure()
    {
        $this->setName('dotdigital:connector:enable')
            ->setDescription(__('Add Dotdigital API credentials and enable the connector'))
            ->addOption('username', null, InputOption::VALUE_REQUIRED, __('API username'))
            ->addOption('password', null, InputOption::VALUE_REQUIRED, __('API password'))
            ->addOption('automap-datafields', null, InputOption::VALUE_OPTIONAL, __('Automap data fields'))
            ->addOption('enable-syncs', null, InputOption::VALUE_OPTIONAL, __('Enable syncs'))
            ->addOption('remove-ip-restriction', null, InputOption::VALUE_OPTIONAL, __('Remove IP restriction'))
            ->addOption('enable-email-capture', null, InputOption::VALUE_OPTIONAL, __('Enable email capture'));

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $username = $input->getOption('username');
        $password = $input->getOption('password');

        if (!$username || !$password) {
            $output->writeln(__('You must specify your API username and password')->getText());
            return;
        }

        $this->output->writeln(__('Saving your API credentials')->getText());

        // get account info
        $accountInfo = $this->getEmailHelper()
            ->getWebsiteApiClient(0, $username, $password)
            ->getAccountInfo();

        if (!$accountInfo || isset($accountInfo->message)) {
            $this->output->writeln(__('There was a problem connecting to the API: ' . $accountInfo->message)->getText());
            return;
        }

        // add credentials
        $this->addConnectorCredentials($accountInfo, $username, $password);

        // execute automap datafields command
        if ((bool) $input->getOption('automap-datafields')) {
            $this->getApplication()
                ->find('dotdigital:connector:automap')
                ->run(
                    new ArrayInput(['command' => 'dotdigital:connector:automap']),
                    $output
                );
        }

        if ((bool) $input->getOption('enable-syncs')) {
            $this->saveConfig([
                EmailConfig::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED => 1,
                EmailConfig::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED => 1,
                EmailConfig::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED => 1,
                EmailConfig::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED => 1,
                EmailConfig::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED => 1,
                EmailConfig::XML_PATH_CONNECTOR_SYNC_REVIEW_ENABLED => 1,
                EmailConfig::XML_PATH_CONNECTOR_SYNC_CATALOG_ENABLED => 1,
            ]);
            $this->output->writeln(__('Enabled syncs')->getText());
        }

        if ((bool) $input->getOption('remove-ip-restriction')) {
            $this->saveConfig([EmailConfig::XML_PATH_CONNECTOR_IP_RESTRICTION_ADDRESSES => null]);
            $this->output->writeln(__('Removed IP restriction')->getText());
        }

        if ((bool) $input->getOption('enable-email-capture')) {
            $this->saveConfig([
                EmailConfig::XML_PATH_CONNECTOR_EMAIL_CAPTURE => 1,
                EmailConfig::XML_PATH_CONNECTOR_EMAIL_CAPTURE_NEWSLETTER => 1,
            ]);
            $this->output->writeln(__('Enabled email capture')->getText());
        }

        $this->output->writeln(__('Dotdigital connector has been enabled')->getText());
    }

    /**
     * @param \stdClass $accountInfo
     * @param string $username
     * @param string $password
     */
    private function addConnectorCredentials(\stdClass $accountInfo, string $username, string $password)
    {
        // save credentials and enable connector
        $configData = [
            EmailConfig::XML_PATH_CONNECTOR_API_USERNAME => $username,
            EmailConfig::XML_PATH_CONNECTOR_API_PASSWORD => $this->encryptor->encrypt($password),
            EmailConfig::XML_PATH_CONNECTOR_API_ENABLED => 1,
        ];

        // get API endpoint from info
        if ($this->getEmailHelper()->getApiEndPointFromConfig(0) === null) {
            $apiEndpointKey = array_search('ApiEndpoint', array_column($accountInfo->properties, 'name'));
            $configData[EmailConfig::PATH_FOR_API_ENDPOINT] = $accountInfo->properties[$apiEndpointKey]->value;
        }

        $this->saveConfig($configData);
    }

    /**
     * @param array $configData
     */
    private function saveConfig(array $configData)
    {
        foreach ($configData as $key => $value) {
            $this->resourceConfig->saveConfig($key, $value, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }
        $this->reinitableConfig->reinit();
    }

    /**
     * @return Data
     */
    private function getEmailHelper()
    {
        return $this->helper
            ?: $this->helper = $this->dataFactory->create();
    }
}
