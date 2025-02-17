<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Connector;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Account;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TranslateInterface;

class DataFieldTranslator
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var TranslateInterface
     */
    private $translateInterface;

    /**
     * @param Data $helper
     * @param Logger $logger
     * @param Account $account
     * @param TranslateInterface $translateInterface
     */
    public function __construct(
        Data $helper,
        Logger $logger,
        Account $account,
        TranslateInterface $translateInterface
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->account = $account;
        $this->translateInterface = $translateInterface;
    }

    /**
     * Translate data field.
     *
     * @param string $name
     * @param int $websiteId
     *
     * @return string
     */
    public function translate(string $name, int $websiteId): string
    {
        try {
            $client = $this->helper->getWebsiteApiClient(
                $websiteId
            );

            $ddAccountLocale = $this->getLocaleFromAccount($websiteId, $client);
            if (strpos($ddAccountLocale, 'en') !== false) {
                return $name;
            }

            $translatedName = $this->getTranslatedString($name, $ddAccountLocale);
            if ($translatedName === $name) {
                return $name;
            }

            if ($this->isValidDataField($translatedName, $client)) {
                return $translatedName;
            }
        } catch (\Exception $e) {
            $this->logger->debug((string) $e, []);
        }

        return $name;
    }

    /**
     * Get locale from account.
     *
     * @param int $websiteId
     * @param Client $client
     *
     * @return string
     * @throws \Exception
     */
    private function getLocaleFromAccount(int $websiteId, Client $client): string
    {
        return $this->account->getAccountLocale(
            $client->getAccountInfo($websiteId)
        );
    }

    /**
     * Get translated string.
     *
     * @param string $name
     * @param string $locale
     *
     * @return string
     */
    private function getTranslatedString(string $name, string $locale): string
    {
        $this->translateInterface->setLocale($locale)
            ->loadData(Area::AREA_GLOBAL);
        $translationData = $this->translateInterface->getData();
        return $translationData[$name] ?? $name;
    }

    /**
     * Is valid data field.
     *
     * Confirm that the translated data field is actually in the account data fields.
     * Data fields are only translated if an alternate locale is set at account creation.
     * So locale could be de-DE, but the data field name is still in English.
     *
     * @param string $name
     * @param Client $client
     *
     * @return bool
     * @throws LocalizedException
     */
    private function isValidDataField(string $name, Client $client): bool
    {
        $accountDataFields = $client->getDataFields();
        return in_array($name, array_column($accountDataFields, 'name'));
    }
}
