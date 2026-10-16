<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Tracking;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class EndpointResolver
{
    private const TRACKING_HOST_DEFAULT = 'ddlnk.net';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Resolve the full tracking URL, including its scheme.
     *
     * @param int $websiteId
     * @return string
     */
    public function getUrl(int $websiteId): string
    {
        $trackingHost = $this->getConfigValue(Config::TRACKING_HOST, $websiteId);

        if (!$trackingHost || $trackingHost === self::TRACKING_HOST_DEFAULT) {
            $trackingHost = $this->getRegionPrefix($websiteId) . '.' . self::TRACKING_HOST_DEFAULT;
        }

        return preg_match('/^https?:\/\//', $trackingHost) ? $trackingHost : 'https://' . $trackingHost;
    }

    /**
     * Get region prefix for a website ID.
     *
     * @param int $websiteId
     * @return string
     */
    private function getRegionPrefix(int $websiteId): string
    {
        $apiEndpoint = $this->getConfigValue(Config::PATH_FOR_API_ENDPOINT, $websiteId);
        $apiEndpointParts = explode('.', $apiEndpoint);

        return str_replace('-api', '', $apiEndpointParts[0]);
    }

    /**
     * Get config value for a given path and website ID.
     *
     * @param string $path
     * @param int $websiteId
     * @return string
     */
    private function getConfigValue(string $path, int $websiteId): string
    {
        return trim((string)$this->scopeConfig->getValue(
            $path,
            $websiteId > 0 ? ScopeInterface::SCOPE_WEBSITE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $websiteId
        ));
    }
}
