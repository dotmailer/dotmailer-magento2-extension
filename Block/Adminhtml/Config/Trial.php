<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

/**
 * Class Trial
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Config
 */
class Trial extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    public $remoteAddress;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    public $localeDate;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    const TRIAL_EXTERNAL_URL = 'https://www.dotmailer.com/trial/';

    /**
     * Trial constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\View\Helper\Js $jsHelper
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $localeDate
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\Timezone $localeDate,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        array $data = []
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->storeManager  = $storeManager;
        $this->localeDate    = $localeDate;
        $this->helper        = $helper;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @codingStandardsIgnoreStart
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //@codingStandardsIgnoreEnd
        if (!$this->helper->isFrontEndAdminSecure()) {
            $html = '<a href=' .
                self::TRIAL_EXTERNAL_URL .
                ' target="_blank"><img style="margin-bottom:15px;" src=' .
                $this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png') .
                ' alt="Open Trial Account"></a>';
            $script = "
            <script>
            require(['jquery', 'domReady'], function($){
                  $('.various').fancybox();
                });
            </script>";
        } else {
            $html = '<a class="various fancybox.iframe" data-fancybox-type="iframe" href=' .
                $this->_getIframeFormUrl() . '><img style="margin-bottom:15px;" src=' .
                $this->getViewFileUrl('Dotdigitalgroup_Email::images/banner.png') .
                ' alt="Open Trial Account"></a>';
            $script = "<script type='text/javascript'>
            require(['jquery', 'domReady'], function($){
                $('.various').fancybox({
                    width	: 508,
                    height	: 670,
                    scrolling   : 'no',
                    hideOnOverlayClick : false,
                    helpers   : { 
                        overlay : { 
                            closeClick: false 
                        } 
                    }
                });
                
                $(document).on('click', 'a.fancybox-close', function(){
                    location.reload();
                });
            }); 
        </script>
        ";
        }

        return $html . $script;
    }

    /**
     * Generate url for iframe for trial account popup.
     *
     * @return string
     */
    public function _getIframeFormUrl()
    {
        $formUrl = \Dotdigitalgroup\Email\Helper\Config::API_CONNECTOR_TRIAL_FORM_URL;
        $ipAddress = $this->remoteAddress->getRemoteAddress();
        $timezone = $this->_getTimeZoneId();
        $culture = $this->_getCultureId();
        $company = $this->helper->getWebsiteConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME);
        $callback = $this->storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true) . 'connector/email/accountcallback';
        //query params
        $params = [
            'callback' => $callback,
            'company' => $company,
            'culture' => $culture,
            'timezone' => $timezone,
            'ip' => $ipAddress,
        ];
        $url = $formUrl . '?' . http_build_query($params);

        return $url;
    }

    /**
     * Get time zone id for trial account.
     *
     * @return string
     */
    public function _getTimeZoneId()
    {
        $timeZone = $this->localeDate->getConfigTimezone();
        $result = '085';
        if ($timeZone) {
            $timeZones = [
                ['MageTimeZone' => 'Australia/Darwin', 'MicrosoftTimeZoneIndex' => '250'],
                ['MageTimeZone' => 'Australia/Melbourne', 'MicrosoftTimeZoneIndex' => '260'],
                ['MageTimeZone' => 'Australia/Sydney', 'MicrosoftTimeZoneIndex' => '260'],
                ['MageTimeZone' => 'Asia/Kabul', 'MicrosoftTimeZoneIndex' => '175'],
                ['MageTimeZone' => 'America/Anchorage', 'MicrosoftTimeZoneIndex' => '003'],
                ['MageTimeZone' => 'America/Juneau', 'MicrosoftTimeZoneIndex' => '003'],
                ['MageTimeZone' => 'America/Nome', 'MicrosoftTimeZoneIndex' => '003'],
                ['MageTimeZone' => 'America/Sitka', 'MicrosoftTimeZoneIndex' => '003'],
                ['MageTimeZone' => 'America/Yakutat', 'MicrosoftTimeZoneIndex' => '003'],
                ['MageTimeZone' => 'Asia/Aden', 'MicrosoftTimeZoneIndex' => '150'],
                ['MageTimeZone' => 'Asia/Bahrain', 'MicrosoftTimeZoneIndex' => '150'],
                ['MageTimeZone' => 'Asia/Kuwait', 'MicrosoftTimeZoneIndex' => '150'],
                ['MageTimeZone' => 'Asia/Qatar', 'MicrosoftTimeZoneIndex' => '150'],
                ['MageTimeZone' => 'Asia/Riyadh', 'MicrosoftTimeZoneIndex' => '150'],
                ['MageTimeZone' => 'Asia/Dubai', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'Asia/Muscat', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'Etc/GMT-4', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'Asia/Baghdad', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'America/Argentina/La_Rioja', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Argentina/Rio_Gallegos', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Argentina/Salta', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Argentina/San_Juan', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Argentina/San_Luis', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Argentina/Tucuman', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Argentina/Ushuaia', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Buenos_Aires', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Catamarca', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Cordoba', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Jujuy', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Mendoza', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Glace_Bay', 'MicrosoftTimeZoneIndex' => '050'],
                ['MageTimeZone' => 'America/Goose_Bay', 'MicrosoftTimeZoneIndex' => '050'],
                ['MageTimeZone' => 'America/Halifax', 'MicrosoftTimeZoneIndex' => '050'],
                ['MageTimeZone' => 'America/Moncton', 'MicrosoftTimeZoneIndex' => '050'],
                ['MageTimeZone' => 'America/Thule', 'MicrosoftTimeZoneIndex' => '050'],
                ['MageTimeZone' => 'Atlantic/Bermuda', 'MicrosoftTimeZoneIndex' => '050'],
                ['MageTimeZone' => 'Asia/Baku', 'MicrosoftTimeZoneIndex' => '170'],
                ['MageTimeZone' => 'America/Scoresbysund', 'MicrosoftTimeZoneIndex' => '080'],
                ['MageTimeZone' => 'Atlantic/Azores', 'MicrosoftTimeZoneIndex' => '080'],
                ['MageTimeZone' => 'America/Bahia', 'MicrosoftTimeZoneIndex' => '065'],
                ['MageTimeZone' => 'Asia/Dhaka', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'Asia/Thimphu', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'America/Regina', 'MicrosoftTimeZoneIndex' => '025'],
                ['MageTimeZone' => 'America/Swift_Current', 'MicrosoftTimeZoneIndex' => '025'],
                ['MageTimeZone' => 'Atlantic/Cape_Verde', 'MicrosoftTimeZoneIndex' => '083'],
                ['MageTimeZone' => 'Etc/GMT+1', 'MicrosoftTimeZoneIndex' => '083'],
                ['MageTimeZone' => 'Asia/Yerevan', 'MicrosoftTimeZoneIndex' => '170'],
                ['MageTimeZone' => 'Australia/Adelaide', 'MicrosoftTimeZoneIndex' => '250'],
                ['MageTimeZone' => 'Australia/Broken_Hill', 'MicrosoftTimeZoneIndex' => '250'],
                ['MageTimeZone' => 'America/Belize', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'America/Costa_Rica', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'America/El_Salvador', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'America/Guatemala', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'America/Managua', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'America/Tegucigalpa', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'Etc/GMT+6', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'Pacific/Galapagos', 'MicrosoftTimeZoneIndex' => '033'],
                ['MageTimeZone' => 'Antarctica/Vostok', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'Asia/Almaty', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'Asia/Bishkek', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'Asia/Qyzylorda', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'Etc/GMT-6', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'Indian/Chagos', 'MicrosoftTimeZoneIndex' => '195'],
                ['MageTimeZone' => 'America/Campo_Grande', 'MicrosoftTimeZoneIndex' => '065'],
                ['MageTimeZone' => 'America/Cuiaba', 'MicrosoftTimeZoneIndex' => '065'],
                ['MageTimeZone' => 'Europe/Belgrade', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Bratislava', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Budapest', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Ljubljana', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Podgorica', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Prague', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Tirane', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Sarajevo', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Skopje', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Warsaw', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Europe/Zagreb', 'MicrosoftTimeZoneIndex' => '095'],
                ['MageTimeZone' => 'Antarctica/Macquarie', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Etc/GMT-11', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Efate', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Guadalcanal', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Kosrae', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Noumea', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Ponape', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'America/Chicago', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Indiana/Knox', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Indiana/Tell_City', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Matamoros', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Menominee', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/North_Dakota/Beulah', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/North_Dakota/Center', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/North_Dakota/New_Salem', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Rainy_River', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Rankin_Inlet', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Resolute', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Winnipeg', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'CST6CDT', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Bahia_Banderas', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Cancun', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Merida', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Mexico_City', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'America/Monterrey', 'MicrosoftTimeZoneIndex' => '020'],
                ['MageTimeZone' => 'Asia/Chongqing', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Asia/Harbin', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Asia/Hong_Kong', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Asia/Kashgar', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Asia/Macau', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Asia/Shanghai', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Asia/Urumqi', 'MicrosoftTimeZoneIndex' => '210'],
                ['MageTimeZone' => 'Etc/GMT+12', 'MicrosoftTimeZoneIndex' => '000'],
                ['MageTimeZone' => 'Africa/Addis_Ababa', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Asmera', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Dar_es_Salaam', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Djibouti', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Juba', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Kampala', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Khartoum', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Mogadishu', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Africa/Nairobi', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Antarctica/Syowa', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Etc/GMT-3', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Indian/Antananarivo', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Indian/Comoro', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Indian/Mayotte', 'MicrosoftTimeZoneIndex' => '115'],
                ['MageTimeZone' => 'Australia/Brisbane', 'MicrosoftTimeZoneIndex' => '260'],
                ['MageTimeZone' => 'Australia/Lindeman', 'MicrosoftTimeZoneIndex' => '260'],
                ['MageTimeZone' => 'America/Sao_Paulo', 'MicrosoftTimeZoneIndex' => '065'],
                ['MageTimeZone' => 'America/Detroit', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Grand_Turk', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Havana', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Indiana/Petersburg', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Indiana/Vincennes', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Indiana/Winamac', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Iqaluit', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Kentucky/Monticello', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Louisville', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Montreal', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Nassau', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/New_York', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Nipigon', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Pangnirtung', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Port-au-Prince', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Thunder_Bay', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'America/Toronto', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'EST5EDT', 'MicrosoftTimeZoneIndex' => '035'],
                ['MageTimeZone' => 'Africa/Cairo', 'MicrosoftTimeZoneIndex' => '120'],
                ['MageTimeZone' => 'Asia/Yekaterinburg', 'MicrosoftTimeZoneIndex' => '180'],
                ['MageTimeZone' => 'Europe/Helsinki', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Kiev', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Riga', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Simferopol', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Sofia', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Tallinn', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Uzhgorod', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Vilnius', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Europe/Zaporozhye', 'MicrosoftTimeZoneIndex' => '125'],
                ['MageTimeZone' => 'Pacific/Fiji', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Atlantic/Canary', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Atlantic/Faeroe', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Atlantic/Madeira', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Europe/Dublin', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Europe/Guernsey', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Europe/Isle_of_Man', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Europe/Jersey', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Europe/Lisbon', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Europe/London', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Asia/Nicosia', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'Europe/Athens', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'Europe/Bucharest', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'Europe/Chisinau', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'Asia/Tbilisi', 'MicrosoftTimeZoneIndex' => '170'],
                ['MageTimeZone' => 'America/Godthab', 'MicrosoftTimeZoneIndex' => '073'],
                ['MageTimeZone' => 'Africa/Abidjan', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Accra', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Bamako', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Banjul', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Bissau', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Conakry', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Dakar', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Freetown', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Lome', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Monrovia', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Nouakchott', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Ouagadougou', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Africa/Sao_Tome', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Atlantic/Reykjavik', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Atlantic/St_Helena', 'MicrosoftTimeZoneIndex' => '090'],
                ['MageTimeZone' => 'Etc/GMT+10', 'MicrosoftTimeZoneIndex' => '002'],
                ['MageTimeZone' => 'Pacific/Honolulu', 'MicrosoftTimeZoneIndex' => '002'],
                ['MageTimeZone' => 'Pacific/Johnston', 'MicrosoftTimeZoneIndex' => '002'],
                ['MageTimeZone' => 'Pacific/Rarotonga', 'MicrosoftTimeZoneIndex' => '002'],
                ['MageTimeZone' => 'Pacific/Tahiti', 'MicrosoftTimeZoneIndex' => '002'],
                ['MageTimeZone' => 'Asia/Calcutta', 'MicrosoftTimeZoneIndex' => '190'],
                ['MageTimeZone' => 'Asia/Tehran', 'MicrosoftTimeZoneIndex' => '160'],
                ['MageTimeZone' => 'Asia/Jerusalem', 'MicrosoftTimeZoneIndex' => '135'],
                ['MageTimeZone' => 'Asia/Amman', 'MicrosoftTimeZoneIndex' => '150'],
                ['MageTimeZone' => 'Europe/Kaliningrad', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'Europe/Minsk', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'Asia/Pyongyang', 'MicrosoftTimeZoneIndex' => '230'],
                ['MageTimeZone' => 'Asia/Seoul', 'MicrosoftTimeZoneIndex' => '230'],
                ['MageTimeZone' => 'Africa/Tripoli', 'MicrosoftTimeZoneIndex' => '120'],
                ['MageTimeZone' => 'Asia/Anadyr', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Asia/Kamchatka', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Asia/Magadan', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Indian/Mahe', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'Indian/Mauritius', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'Indian/Reunion', 'MicrosoftTimeZoneIndex' => '165'],
                ['MageTimeZone' => 'Asia/Beirut', 'MicrosoftTimeZoneIndex' => '158'],
                ['MageTimeZone' => 'America/Montevideo', 'MicrosoftTimeZoneIndex' => '065'],
                ['MageTimeZone' => 'Africa/Casablanca', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/El_Aaiun', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'America/Boise', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Cambridge_Bay', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Denver', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Edmonton', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Inuvik', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Ojinaga', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Shiprock', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Yellowknife', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'MST7MDT', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Chihuahua', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'America/Mazatlan', 'MicrosoftTimeZoneIndex' => '010'],
                ['MageTimeZone' => 'Asia/Rangoon', 'MicrosoftTimeZoneIndex' => '203'],
                ['MageTimeZone' => 'Indian/Cocos', 'MicrosoftTimeZoneIndex' => '203'],
                ['MageTimeZone' => 'Asia/Novokuznetsk', 'MicrosoftTimeZoneIndex' => '201'],
                ['MageTimeZone' => 'Asia/Novosibirsk', 'MicrosoftTimeZoneIndex' => '201'],
                ['MageTimeZone' => 'Asia/Omsk', 'MicrosoftTimeZoneIndex' => '201'],
                ['MageTimeZone' => 'Africa/Windhoek', 'MicrosoftTimeZoneIndex' => '120'],
                ['MageTimeZone' => 'Asia/Katmandu', 'MicrosoftTimeZoneIndex' => '193'],
                ['MageTimeZone' => 'Antarctica/McMurdo', 'MicrosoftTimeZoneIndex' => '290'],
                ['MageTimeZone' => 'Antarctica/South_Pole', 'MicrosoftTimeZoneIndex' => '290'],
                ['MageTimeZone' => 'Pacific/Auckland', 'MicrosoftTimeZoneIndex' => '290'],
                ['MageTimeZone' => 'America/St_Johns', 'MicrosoftTimeZoneIndex' => '060'],
                ['MageTimeZone' => 'Asia/Irkutsk', 'MicrosoftTimeZoneIndex' => '207'],
                ['MageTimeZone' => 'Asia/Krasnoyarsk', 'MicrosoftTimeZoneIndex' => '207'],
                ['MageTimeZone' => 'America/Santiago', 'MicrosoftTimeZoneIndex' => '056'],
                ['MageTimeZone' => 'Antarctica/Palmer', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'America/Dawson', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'America/Los_Angeles', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'America/Tijuana', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'America/Vancouver', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'America/Whitehorse', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'America/Santa_Isabel', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'PST8PDT', 'MicrosoftTimeZoneIndex' => '004'],
                ['MageTimeZone' => 'Asia/Karachi', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'America/Asuncion', 'MicrosoftTimeZoneIndex' => '065'],
                ['MageTimeZone' => 'Africa/Ceuta', 'MicrosoftTimeZoneIndex' => '105'],
                ['MageTimeZone' => 'Europe/Brussels', 'MicrosoftTimeZoneIndex' => '105'],
                ['MageTimeZone' => 'Europe/Copenhagen', 'MicrosoftTimeZoneIndex' => '105'],
                ['MageTimeZone' => 'Europe/Madrid', 'MicrosoftTimeZoneIndex' => '105'],
                ['MageTimeZone' => 'Europe/Paris', 'MicrosoftTimeZoneIndex' => '105'],
                ['MageTimeZone' => 'Europe/Moscow', 'MicrosoftTimeZoneIndex' => '145'],
                ['MageTimeZone' => 'Europe/Samara', 'MicrosoftTimeZoneIndex' => '145'],
                ['MageTimeZone' => 'Europe/Volgograd', 'MicrosoftTimeZoneIndex' => '145'],
                ['MageTimeZone' => 'America/Araguaina', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Belem', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Cayenne', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Fortaleza', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Maceio', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Paramaribo', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Recife', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Santarem', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'Antarctica/Rothera', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'Atlantic/Stanley', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'Etc/GMT+3', 'MicrosoftTimeZoneIndex' => '070'],
                ['MageTimeZone' => 'America/Bogota', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Cayman', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Coral_Harbour', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Eirunepe', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Guayaquil', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Jamaica', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Lima', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Panama', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Rio_Branco', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'Etc/GMT+5', 'MicrosoftTimeZoneIndex' => '045'],
                ['MageTimeZone' => 'America/Anguilla', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Antigua', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Aruba', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Barbados', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Blanc-Sablon', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Boa_Vista', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Curacao', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Dominica', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Grenada', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Guadeloupe', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Guyana', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Kralendijk', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/La_Paz', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Lower_Princes', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Manaus', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Marigot', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Martinique', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Montserrat', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Port_of_Spain', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Porto_Velho', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Puerto_Rico', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Santo_Domingo', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/St_Barthelemy', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/St_Kitts', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/St_Lucia', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/St_Thomas', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/St_Vincent', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'America/Tortola', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'Etc/GMT+4', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'Antarctica/Davis', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Bangkok', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Hovd', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Jakarta', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Phnom_Penh', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Pontianak', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Saigon', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Asia/Vientiane', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Etc/GMT-7', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Indian/Christmas', 'MicrosoftTimeZoneIndex' => '205'],
                ['MageTimeZone' => 'Pacific/Apia', 'MicrosoftTimeZoneIndex' => '001'],
                ['MageTimeZone' => 'Asia/Brunei', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Asia/Kuala_Lumpur', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Asia/Kuching', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Asia/Makassar', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Asia/Manila', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Asia/Singapore', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Etc/GMT-8', 'MicrosoftTimeZoneIndex' => '215'],
                ['MageTimeZone' => 'Africa/Blantyre', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Bujumbura', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Gaborone', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Harare', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Johannesburg', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Kigali', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Lubumbashi', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Lusaka', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Maputo', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Maseru', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Africa/Mbabane', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Etc/GMT-2', 'MicrosoftTimeZoneIndex' => '140'],
                ['MageTimeZone' => 'Asia/Colombo', 'MicrosoftTimeZoneIndex' => '200'],
                ['MageTimeZone' => 'Asia/Damascus', 'MicrosoftTimeZoneIndex' => '158'],
                ['MageTimeZone' => 'Asia/Taipei', 'MicrosoftTimeZoneIndex' => '220'],
                ['MageTimeZone' => 'Australia/Currie', 'MicrosoftTimeZoneIndex' => '265'],
                ['MageTimeZone' => 'Australia/Hobart', 'MicrosoftTimeZoneIndex' => '265'],
                ['MageTimeZone' => 'Asia/Dili', 'MicrosoftTimeZoneIndex' => '235'],
                ['MageTimeZone' => 'Asia/Jayapura', 'MicrosoftTimeZoneIndex' => '235'],
                ['MageTimeZone' => 'Asia/Tokyo', 'MicrosoftTimeZoneIndex' => '235'],
                ['MageTimeZone' => 'Etc/GMT-9', 'MicrosoftTimeZoneIndex' => '235'],
                ['MageTimeZone' => 'Pacific/Palau', 'MicrosoftTimeZoneIndex' => '235'],
                ['MageTimeZone' => 'Etc/GMT-13', 'MicrosoftTimeZoneIndex' => '300'],
                ['MageTimeZone' => 'Pacific/Enderbury', 'MicrosoftTimeZoneIndex' => '300'],
                ['MageTimeZone' => 'Pacific/Fakaofo', 'MicrosoftTimeZoneIndex' => '300'],
                ['MageTimeZone' => 'Pacific/Tongatapu', 'MicrosoftTimeZoneIndex' => '300'],
                ['MageTimeZone' => 'Europe/Istanbul', 'MicrosoftTimeZoneIndex' => '130'],
                ['MageTimeZone' => 'America/Indiana/Marengo', 'MicrosoftTimeZoneIndex' => '040'],
                ['MageTimeZone' => 'America/Indiana/Vevay', 'MicrosoftTimeZoneIndex' => '040'],
                ['MageTimeZone' => 'America/Indianapolis', 'MicrosoftTimeZoneIndex' => '040'],
                ['MageTimeZone' => 'America/Creston', 'MicrosoftTimeZoneIndex' => '015'],
                ['MageTimeZone' => 'America/Dawson_Creek', 'MicrosoftTimeZoneIndex' => '015'],
                ['MageTimeZone' => 'America/Hermosillo', 'MicrosoftTimeZoneIndex' => '015'],
                ['MageTimeZone' => 'America/Phoenix', 'MicrosoftTimeZoneIndex' => '015'],
                ['MageTimeZone' => 'Etc/GMT+7', 'MicrosoftTimeZoneIndex' => '015'],
                ['MageTimeZone' => 'America/Danmarkshavn', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Etc/GMT', 'MicrosoftTimeZoneIndex' => '085'],
                ['MageTimeZone' => 'Etc/GMT-12', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Funafuti', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Kwajalein', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Majuro', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Nauru', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Tarawa', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Wake', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'Pacific/Wallis', 'MicrosoftTimeZoneIndex' => '285'],
                ['MageTimeZone' => 'America/Noronha', 'MicrosoftTimeZoneIndex' => '075'],
                ['MageTimeZone' => 'Atlantic/South_Georgia', 'MicrosoftTimeZoneIndex' => '075'],
                ['MageTimeZone' => 'Etc/GMT+2', 'MicrosoftTimeZoneIndex' => '075'],
                ['MageTimeZone' => 'Etc/GMT+11', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Midway', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Niue', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Pacific/Pago_Pago', 'MicrosoftTimeZoneIndex' => '280'],
                ['MageTimeZone' => 'Asia/Choibalsan', 'MicrosoftTimeZoneIndex' => '227'],
                ['MageTimeZone' => 'Asia/Ulaanbaatar', 'MicrosoftTimeZoneIndex' => '227'],
                ['MageTimeZone' => 'America/Caracas', 'MicrosoftTimeZoneIndex' => '055'],
                ['MageTimeZone' => 'Asia/Sakhalin', 'MicrosoftTimeZoneIndex' => '270'],
                ['MageTimeZone' => 'Asia/Ust-Nera', 'MicrosoftTimeZoneIndex' => '270'],
                ['MageTimeZone' => 'Asia/Vladivostok', 'MicrosoftTimeZoneIndex' => '270'],
                ['MageTimeZone' => 'Antarctica/Casey', 'MicrosoftTimeZoneIndex' => '225'],
                ['MageTimeZone' => 'Australia/Perth', 'MicrosoftTimeZoneIndex' => '225'],
                ['MageTimeZone' => 'Africa/Algiers', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Bangui', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Brazzaville', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Douala', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Kinshasa', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Lagos', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Libreville', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Luanda', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Malabo', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Ndjamena', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Niamey', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Porto-Novo', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Africa/Tunis', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Etc/GMT-1', 'MicrosoftTimeZoneIndex' => '113'],
                ['MageTimeZone' => 'Arctic/Longyearbyen', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Amsterdam', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Andorra', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Berlin', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Busingen', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Gibraltar', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Luxembourg', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Malta', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Monaco', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Oslo', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Rome', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/San_Marino', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Stockholm', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Vaduz', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Vatican', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Vienna', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Europe/Zurich', 'MicrosoftTimeZoneIndex' => '110'],
                ['MageTimeZone' => 'Antarctica/Mawson', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Aqtau', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Aqtobe', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Ashgabat', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Dushanbe', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Oral', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Samarkand', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Asia/Tashkent', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Etc/GMT-5', 'MicrosoftTimeZoneIndex' => 'TEST'],
                ['MageTimeZone' => 'Indian/Kerguelen', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Indian/Maldives', 'MicrosoftTimeZoneIndex' => '185'],
                ['MageTimeZone' => 'Antarctica/DumontDUrville', 'MicrosoftTimeZoneIndex' => '275'],
                ['MageTimeZone' => 'Etc/GMT-10', 'MicrosoftTimeZoneIndex' => '275'],
                ['MageTimeZone' => 'Pacific/Guam', 'MicrosoftTimeZoneIndex' => '275'],
                ['MageTimeZone' => 'Pacific/Port_Moresby', 'MicrosoftTimeZoneIndex' => '275'],
                ['MageTimeZone' => 'Pacific/Saipan', 'MicrosoftTimeZoneIndex' => '275'],
                ['MageTimeZone' => 'Pacific/Truk', 'MicrosoftTimeZoneIndex' => '275'],
                ['MageTimeZone' => 'Asia/Khandyga', 'MicrosoftTimeZoneIndex' => '240'],
                ['MageTimeZone' => 'Asia/Yakutsk', 'MicrosoftTimeZoneIndex' => '240'],
            ];
            foreach ($timeZones as $time) {
                if ($time['MageTimeZone'] == $timeZone) {
                    $result = $time['MicrosoftTimeZoneIndex'];
                }
            }
        }

        return $result;
    }

    /**
     * Get culture id needed for trial account.
     *
     * @return mixed
     */
    public function _getCultureId()
    {
        $fallback = 'en_US';
        $supportedCultures = [
            'en_US' => '1033',
            'en_GB' => '2057',
            'fr_FR' => '1036',
            'es_ES' => '3082',
            'de_DE' => '1031',
            'it_IT' => '1040',
            'ru_RU' => '1049',
            'pt_PT' => '2070',
        ];
        $localeCode = $this->helper->getWebsiteConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE);
        if (isset($supportedCultures[$localeCode])) {
            return $supportedCultures[$localeCode];
        }

        return $supportedCultures[$fallback];
    }
}
