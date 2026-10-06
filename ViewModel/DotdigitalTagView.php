<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * ViewModel class for rendering Dotdigital tags.
 */
class DotdigitalTagView implements ArgumentInterface
{
    private const TRACKING_HOST_DEFAULT = 'ddlnk.net';

    /**
     * Context instance.
     *
     * @var Context
     */
    private $context;

    /**
     * SecureHtmlRenderer instance.
     *
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Dotdigital Tag page-type integer for a completed login, fired via the
     * `customer_login` backend event rather than a layout handle.
     */
    private const PAGE_TYPE_LOGIN_COMPLETE = 8;

    /**
     * Dotdigital Tag page-type integer for a completed registration, fired
     * via the `customer_register_success` backend event rather than a
     * layout handle.
     */
    private const PAGE_TYPE_REGISTER_COMPLETE = 10;

    /**
     * Maps Magento full action names to Dotdigital Tag page-type integers.
     *
     * Values >= 0 are passed directly to ddg.track({ pageType: N }).
     * Values < 0 indicate pages tracked by dedicated named events (e.g. product
     * view, search results) — these cause trackPageVisit() to return early so
     * the generic page-type tracking does not double-fire.
     *
     * Note: successful login (pageType 8) and registration (pageType 10) are
     * NOT resolved from this map. `customer_account_loginpost` and
     * `customer_account_createpost` are POST-only redirect targets that never
     * render a layout, so `getFullActionName()` never matches them. Those
     * page types are instead resolved via one-shot customer-session flags set
     * by TrackLoginComplete/TrackRegisterComplete observers on the
     * `customer_login`/`customer_register_success` backend events — see
     * resolveDotdigitalTagPageType().
     */
    private const DOTDIGITAL_TAG_PAGE_TYPE_MAP = [
        'cms_index_index'                => 1,  // Home_Page
        'catalog_category_view'           => -1, // Tracked by ProductList
        'catalogsearch_result_index'     => -1, // Tracked by ProductList
        'catalogsearch_advanced_result'  => -1, // Tracked by ProductList (Advanced Search)
        'catalog_product_view'            => -1, // Tracked by ProductBrowse
        'checkout_cart_index'             => 4,  // Cart
        'checkout_index_index'            => 5,  // Checkout
        'checkout_onepage_success'        => 6,  // Purchase_Complete
        'customer_account_login'          => 7,  // Login
        'customer_account_create'         => 9,  // Register
        'customer_account_index'          => 11, // Account
        'newsletter_manage_index'         => 12, // Newsletter
    ];

    /**
     * Constructor.
     *
     * @param Context $context
     * @param SecureHtmlRenderer $secureRenderer
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        SecureHtmlRenderer $secureRenderer,
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Session $customerSession,
    ) {
        $this->context = $context;
        $this->secureRenderer = $secureRenderer;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Renders the Dotdigital tag.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function renderTag()
    {
        return $this->secureRenderer->renderTag(
            "script",
            ['src' => $this->context->getAssetRepository()->getUrl(
                'Dotdigitalgroup_Email::js/dotdigital-tag.js',
            )],
            null,
            false
        );
    }

    /**
     * Renders the Dotdigital script with the region and tag ID.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function renderScript(): string
    {
        return $this->secureRenderer->renderTag(
            'script',
            [],
            sprintf(
                'window.ddg.init("%s", "%s");',
                preg_replace(
                    '/^https?:\/\//',
                    '',
                    $this->getUrl()
                ),
                $this->getTagId()
            ),
            false
        );
    }

    /**
     * Renders a ddg.track() call for the current page type.
     *
     * Page types handled by dedicated named events (e.g. product view, category)
     * return early. All other pages are mapped to a Dotdigital page-type integer
     * and tracked via ddg.track({ pageType: N }).
     *
     * @return string
     */
    public function trackPageVisit(): string
    {
        $pageType = $this->resolveDotdigitalTagPageType();

        if ($pageType < 0) {
            // handled by the named events
            return '';
        }

        return $this->secureRenderer->renderTag(
            'script',
            [],
            '
                window.ddg.track({pageType: ' . $pageType . '});
            ',
            false
        );
    }

    /**
     * Determines if the Dotdigital tag should be rendered.
     *
     * @deprecated Replaced with layout block ifconfig argument
     * @see view/frontend/layout/default.xml
     * @throws LocalizedException
     */
    public function shouldRender():bool
    {
        $wbt = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getWebsite()->getId()
        );

        return !empty($wbt);
    }

    /**
     * Retrieves the tag ID from the configuration.
     *
     * @return string|null
     * @throws NoSuchEntityException
     */
    private function getTagId(): ?string
    {
        return $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID,
            ScopeInterface::SCOPE_WEBSITE,
            $this->storeManager->getStore()->getWebsiteId()
        );
    }

    /**
     * Get the tracking URL.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getUrl(): string
    {
        $websiteId = (int)$this->storeManager->getStore()->getWebsiteId();
        $trackingEndpoint = $this->helper->getTrackingEndPointFromConfig($websiteId);

        if (!$trackingEndpoint || $trackingEndpoint === self::TRACKING_HOST_DEFAULT) {
            $trackingEndpoint = $this->helper->getTrackingRegionPrefix($websiteId) . '.' . self::TRACKING_HOST_DEFAULT;
        }

        return $trackingEndpoint;
    }

    /**
     * Resolves the current page to a Dotdigital Tag page-type integer.
     *
     * Returns -1 (or any negative value) for pages that are tracked by
     * dedicated named events rather than the generic ddg.track() call —
     * the caller is responsible for skipping tracking in that case.
     * Returns 0 (Other) for any page not explicitly mapped.
     *
     * Login/Register completion are resolved from one-shot customer-session
     * flags (set by TrackLoginComplete/TrackRegisterComplete observers)
     * rather than the full-action-name map, since the completing controllers
     * never render a layout. The flag is consumed (read then cleared) so it
     * only fires on the very next page render.
     *
     * @return int
     */
    private function resolveDotdigitalTagPageType(): int
    {
        if ($this->customerSession->getDotdigitalTrackLoginComplete()) {
            $this->customerSession->unsDotdigitalTrackLoginComplete();
            return self::PAGE_TYPE_LOGIN_COMPLETE;
        }

        if ($this->customerSession->getDotdigitalTrackRegisterComplete()) {
            $this->customerSession->unsDotdigitalTrackRegisterComplete();
            return self::PAGE_TYPE_REGISTER_COMPLETE;
        }

        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $this->context->getRequest();
        return self::DOTDIGITAL_TAG_PAGE_TYPE_MAP[$request->getFullActionName()] ?? 0;
    }
}
