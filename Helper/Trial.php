<?php

namespace Dotdigitalgroup\Email\Helper;

class Trial extends \Dotdigitalgroup\Email\Helper\Data
{
    protected $_localeDate;
    protected $_dataField;

    /**
     * Data constructor.
     *
     * @param \Magento\Backend\Model\Auth\Session $sessionModel
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\ResourceConnection $adapter
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $localeDate
     * @param \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $sessionModel,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\ResourceConnection $adapter,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\Timezone $localeDate,
        \Dotdigitalgroup\Email\Model\Connector\Datafield $dataField
    ) {
        $this->_localeDate = $localeDate;
        $this->_dataField = $dataField;

        parent::__construct(
            $sessionModel,
            $productMetadata,
            $contactFactory,
            $resourceConfig,
            $adapter,
            $context,
            $objectManager,
            $storeManager
        );
    }

    /**
     * generate url for iframe for trial account popup
     *
     * @return string
     */
    public function getIframeFormUrl()
    {
        $formUrl = \Dotdigitalgroup\Email\Helper\Config::API_CONNECTOR_TRIAL_FORM_URL;
        $ipAddress = $this->_remoteAddress->getRemoteAddress();
        $timezone = $this->getTimeZoneId();
        $culture = $this->getCultureId();
        $company = $this->getWebsiteConfig(\Magento\Store\Model\Information::XML_PATH_STORE_INFO_NAME);
        $callback = $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true) . 'connector/email/accountcallback';
        $secret = \Dotdigitalgroup\Email\Helper\Config::API_CONNECTOR_TRIAL_FORM_SECRET;
        //query params
        $params = array(
            'callback' => $callback,
            'company' => $company,
            'culture' => $culture,
            'timezone' => $timezone,
            'ip' => $ipAddress,
            'secret' => $secret
        );
        $url = $formUrl . '?' . http_build_query($params);
        return $url;
    }

    /**
     * get time zone id for trial account
     *
     * @return string
     */
    public function getTimeZoneId()
    {
        $timeZone = $this->_localeDate->getConfigTimezone();
        $result = '085';
        if ($timeZone) {
            $timeZones = Array
            (
                Array("MageTimeZone" => "Australia/Darwin", "MicrosoftTimeZoneIndex" => "250"),
                Array("MageTimeZone" => "Australia/Melbourne", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "Australia/Sydney", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "Asia/Kabul", "MicrosoftTimeZoneIndex" => "175"),
                Array("MageTimeZone" => "America/Anchorage", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Juneau", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Nome", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Sitka", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "America/Yakutat", "MicrosoftTimeZoneIndex" => "003"),
                Array("MageTimeZone" => "Asia/Aden", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Bahrain", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Kuwait", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Qatar", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Riyadh", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Asia/Dubai", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Asia/Muscat", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Etc/GMT-4", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Asia/Baghdad", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "America/Argentina/La_Rioja", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Rio_Gallegos", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Salta", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/San_Juan", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/San_Luis", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Tucuman", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Argentina/Ushuaia", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Buenos_Aires", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Catamarca", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Cordoba", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Jujuy", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Mendoza", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Glace_Bay", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Goose_Bay", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Halifax", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Moncton", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "America/Thule", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "Atlantic/Bermuda", "MicrosoftTimeZoneIndex" => "050"),
                Array("MageTimeZone" => "Asia/Baku", "MicrosoftTimeZoneIndex" => "170"),
                Array("MageTimeZone" => "America/Scoresbysund", "MicrosoftTimeZoneIndex" => "080"),
                Array("MageTimeZone" => "Atlantic/Azores", "MicrosoftTimeZoneIndex" => "080"),
                Array("MageTimeZone" => "America/Bahia", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Asia/Dhaka", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Thimphu", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "America/Regina", "MicrosoftTimeZoneIndex" => "025"),
                Array("MageTimeZone" => "America/Swift_Current", "MicrosoftTimeZoneIndex" => "025"),
                Array("MageTimeZone" => "Atlantic/Cape_Verde", "MicrosoftTimeZoneIndex" => "083"),
                Array("MageTimeZone" => "Etc/GMT+1", "MicrosoftTimeZoneIndex" => "083"),
                Array("MageTimeZone" => "Asia/Yerevan", "MicrosoftTimeZoneIndex" => "170"),
                Array("MageTimeZone" => "Australia/Adelaide", "MicrosoftTimeZoneIndex" => "250"),
                Array("MageTimeZone" => "Australia/Broken_Hill", "MicrosoftTimeZoneIndex" => "250"),
                Array("MageTimeZone" => "America/Belize", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Costa_Rica", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/El_Salvador", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Guatemala", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Managua", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "America/Tegucigalpa", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "Etc/GMT+6", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "Pacific/Galapagos", "MicrosoftTimeZoneIndex" => "033"),
                Array("MageTimeZone" => "Antarctica/Vostok", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Almaty", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Bishkek", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Asia/Qyzylorda", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Etc/GMT-6", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "Indian/Chagos", "MicrosoftTimeZoneIndex" => "195"),
                Array("MageTimeZone" => "America/Campo_Grande", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "America/Cuiaba", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Europe/Belgrade", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Bratislava", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Budapest", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Ljubljana", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Podgorica", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Prague", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Tirane", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Sarajevo", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Skopje", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Warsaw", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Europe/Zagreb", "MicrosoftTimeZoneIndex" => "095"),
                Array("MageTimeZone" => "Antarctica/Macquarie", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Etc/GMT-11", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Efate", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Guadalcanal", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Kosrae", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Noumea", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Ponape", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "America/Chicago", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Indiana/Knox", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Indiana/Tell_City", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Matamoros", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Menominee", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/North_Dakota/Beulah", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/North_Dakota/Center", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/North_Dakota/New_Salem", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Rainy_River", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Rankin_Inlet", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Resolute", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Winnipeg", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "CST6CDT", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Bahia_Banderas", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Cancun", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Merida", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Mexico_City", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "America/Monterrey", "MicrosoftTimeZoneIndex" => "020"),
                Array("MageTimeZone" => "Asia/Chongqing", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Harbin", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Hong_Kong", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Kashgar", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Macau", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Shanghai", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Asia/Urumqi", "MicrosoftTimeZoneIndex" => "210"),
                Array("MageTimeZone" => "Etc/GMT+12", "MicrosoftTimeZoneIndex" => "000"),
                Array("MageTimeZone" => "Africa/Addis_Ababa", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Asmera", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Dar_es_Salaam", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Djibouti", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Juba", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Kampala", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Khartoum", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Mogadishu", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Africa/Nairobi", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Antarctica/Syowa", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Etc/GMT-3", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Indian/Antananarivo", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Indian/Comoro", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Indian/Mayotte", "MicrosoftTimeZoneIndex" => "115"),
                Array("MageTimeZone" => "Australia/Brisbane", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "Australia/Lindeman", "MicrosoftTimeZoneIndex" => "260"),
                Array("MageTimeZone" => "America/Sao_Paulo", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "America/Detroit", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Grand_Turk", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Havana", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Indiana/Petersburg", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Indiana/Vincennes", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Indiana/Winamac", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Iqaluit", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Kentucky/Monticello", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Louisville", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Montreal", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Nassau", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/New_York", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Nipigon", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Pangnirtung", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Port-au-Prince", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Thunder_Bay", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "America/Toronto", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "EST5EDT", "MicrosoftTimeZoneIndex" => "035"),
                Array("MageTimeZone" => "Africa/Cairo", "MicrosoftTimeZoneIndex" => "120"),
                Array("MageTimeZone" => "Asia/Yekaterinburg", "MicrosoftTimeZoneIndex" => "180"),
                Array("MageTimeZone" => "Europe/Helsinki", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Kiev", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Riga", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Simferopol", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Sofia", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Tallinn", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Uzhgorod", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Vilnius", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Europe/Zaporozhye", "MicrosoftTimeZoneIndex" => "125"),
                Array("MageTimeZone" => "Pacific/Fiji", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Atlantic/Canary", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Atlantic/Faeroe", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Atlantic/Madeira", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Dublin", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Guernsey", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Isle_of_Man", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Jersey", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/Lisbon", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Europe/London", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Asia/Nicosia", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Athens", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Bucharest", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Chisinau", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Asia/Tbilisi", "MicrosoftTimeZoneIndex" => "170"),
                Array("MageTimeZone" => "America/Godthab", "MicrosoftTimeZoneIndex" => "073"),
                Array("MageTimeZone" => "Africa/Abidjan", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Accra", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Bamako", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Banjul", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Bissau", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Conakry", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Dakar", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Freetown", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Lome", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Monrovia", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Nouakchott", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Ouagadougou", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Africa/Sao_Tome", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Atlantic/Reykjavik", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Atlantic/St_Helena", "MicrosoftTimeZoneIndex" => "090"),
                Array("MageTimeZone" => "Etc/GMT+10", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Honolulu", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Johnston", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Rarotonga", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Pacific/Tahiti", "MicrosoftTimeZoneIndex" => "002"),
                Array("MageTimeZone" => "Asia/Calcutta", "MicrosoftTimeZoneIndex" => "190"),
                Array("MageTimeZone" => "Asia/Tehran", "MicrosoftTimeZoneIndex" => "160"),
                Array("MageTimeZone" => "Asia/Jerusalem", "MicrosoftTimeZoneIndex" => "135"),
                Array("MageTimeZone" => "Asia/Amman", "MicrosoftTimeZoneIndex" => "150"),
                Array("MageTimeZone" => "Europe/Kaliningrad", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Europe/Minsk", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "Asia/Pyongyang", "MicrosoftTimeZoneIndex" => "230"),
                Array("MageTimeZone" => "Asia/Seoul", "MicrosoftTimeZoneIndex" => "230"),
                Array("MageTimeZone" => "Africa/Tripoli", "MicrosoftTimeZoneIndex" => "120"),
                Array("MageTimeZone" => "Asia/Anadyr", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Asia/Kamchatka", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Asia/Magadan", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Indian/Mahe", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Indian/Mauritius", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Indian/Reunion", "MicrosoftTimeZoneIndex" => "165"),
                Array("MageTimeZone" => "Asia/Beirut", "MicrosoftTimeZoneIndex" => "158"),
                Array("MageTimeZone" => "America/Montevideo", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Africa/Casablanca", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/El_Aaiun", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "America/Boise", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Cambridge_Bay", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Denver", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Edmonton", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Inuvik", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Ojinaga", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Shiprock", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Yellowknife", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "MST7MDT", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Chihuahua", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "America/Mazatlan", "MicrosoftTimeZoneIndex" => "010"),
                Array("MageTimeZone" => "Asia/Rangoon", "MicrosoftTimeZoneIndex" => "203"),
                Array("MageTimeZone" => "Indian/Cocos", "MicrosoftTimeZoneIndex" => "203"),
                Array("MageTimeZone" => "Asia/Novokuznetsk", "MicrosoftTimeZoneIndex" => "201"),
                Array("MageTimeZone" => "Asia/Novosibirsk", "MicrosoftTimeZoneIndex" => "201"),
                Array("MageTimeZone" => "Asia/Omsk", "MicrosoftTimeZoneIndex" => "201"),
                Array("MageTimeZone" => "Africa/Windhoek", "MicrosoftTimeZoneIndex" => "120"),
                Array("MageTimeZone" => "Asia/Katmandu", "MicrosoftTimeZoneIndex" => "193"),
                Array("MageTimeZone" => "Antarctica/McMurdo", "MicrosoftTimeZoneIndex" => "290"),
                Array("MageTimeZone" => "Antarctica/South_Pole", "MicrosoftTimeZoneIndex" => "290"),
                Array("MageTimeZone" => "Pacific/Auckland", "MicrosoftTimeZoneIndex" => "290"),
                Array("MageTimeZone" => "America/St_Johns", "MicrosoftTimeZoneIndex" => "060"),
                Array("MageTimeZone" => "Asia/Irkutsk", "MicrosoftTimeZoneIndex" => "207"),
                Array("MageTimeZone" => "Asia/Krasnoyarsk", "MicrosoftTimeZoneIndex" => "207"),
                Array("MageTimeZone" => "America/Santiago", "MicrosoftTimeZoneIndex" => "056"),
                Array("MageTimeZone" => "Antarctica/Palmer", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Dawson", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Los_Angeles", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Tijuana", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Vancouver", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Whitehorse", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "America/Santa_Isabel", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "PST8PDT", "MicrosoftTimeZoneIndex" => "004"),
                Array("MageTimeZone" => "Asia/Karachi", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "America/Asuncion", "MicrosoftTimeZoneIndex" => "065"),
                Array("MageTimeZone" => "Africa/Ceuta", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Brussels", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Copenhagen", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Madrid", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Paris", "MicrosoftTimeZoneIndex" => "105"),
                Array("MageTimeZone" => "Europe/Moscow", "MicrosoftTimeZoneIndex" => "145"),
                Array("MageTimeZone" => "Europe/Samara", "MicrosoftTimeZoneIndex" => "145"),
                Array("MageTimeZone" => "Europe/Volgograd", "MicrosoftTimeZoneIndex" => "145"),
                Array("MageTimeZone" => "America/Araguaina", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Belem", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Cayenne", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Fortaleza", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Maceio", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Paramaribo", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Recife", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Santarem", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "Antarctica/Rothera", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "Atlantic/Stanley", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "Etc/GMT+3", "MicrosoftTimeZoneIndex" => "070"),
                Array("MageTimeZone" => "America/Bogota", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Cayman", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Coral_Harbour", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Eirunepe", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Guayaquil", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Jamaica", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Lima", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Panama", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Rio_Branco", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "Etc/GMT+5", "MicrosoftTimeZoneIndex" => "045"),
                Array("MageTimeZone" => "America/Anguilla", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Antigua", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Aruba", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Barbados", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Blanc-Sablon", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Boa_Vista", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Curacao", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Dominica", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Grenada", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Guadeloupe", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Guyana", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Kralendijk", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/La_Paz", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Lower_Princes", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Manaus", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Marigot", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Martinique", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Montserrat", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Port_of_Spain", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Porto_Velho", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Puerto_Rico", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Santo_Domingo", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Barthelemy", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Kitts", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Lucia", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Thomas", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/St_Vincent", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "America/Tortola", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "Etc/GMT+4", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "Antarctica/Davis", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Bangkok", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Hovd", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Jakarta", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Phnom_Penh", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Pontianak", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Saigon", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Asia/Vientiane", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Etc/GMT-7", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Indian/Christmas", "MicrosoftTimeZoneIndex" => "205"),
                Array("MageTimeZone" => "Pacific/Apia", "MicrosoftTimeZoneIndex" => "001"),
                Array("MageTimeZone" => "Asia/Brunei", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Kuala_Lumpur", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Kuching", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Makassar", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Manila", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Asia/Singapore", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Etc/GMT-8", "MicrosoftTimeZoneIndex" => "215"),
                Array("MageTimeZone" => "Africa/Blantyre", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Bujumbura", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Gaborone", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Harare", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Johannesburg", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Kigali", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Lubumbashi", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Lusaka", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Maputo", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Maseru", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Africa/Mbabane", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Etc/GMT-2", "MicrosoftTimeZoneIndex" => "140"),
                Array("MageTimeZone" => "Asia/Colombo", "MicrosoftTimeZoneIndex" => "200"),
                Array("MageTimeZone" => "Asia/Damascus", "MicrosoftTimeZoneIndex" => "158"),
                Array("MageTimeZone" => "Asia/Taipei", "MicrosoftTimeZoneIndex" => "220"),
                Array("MageTimeZone" => "Australia/Currie", "MicrosoftTimeZoneIndex" => "265"),
                Array("MageTimeZone" => "Australia/Hobart", "MicrosoftTimeZoneIndex" => "265"),
                Array("MageTimeZone" => "Asia/Dili", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Asia/Jayapura", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Asia/Tokyo", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Etc/GMT-9", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Pacific/Palau", "MicrosoftTimeZoneIndex" => "235"),
                Array("MageTimeZone" => "Etc/GMT-13", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Pacific/Enderbury", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Pacific/Fakaofo", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Pacific/Tongatapu", "MicrosoftTimeZoneIndex" => "300"),
                Array("MageTimeZone" => "Europe/Istanbul", "MicrosoftTimeZoneIndex" => "130"),
                Array("MageTimeZone" => "America/Indiana/Marengo", "MicrosoftTimeZoneIndex" => "040"),
                Array("MageTimeZone" => "America/Indiana/Vevay", "MicrosoftTimeZoneIndex" => "040"),
                Array("MageTimeZone" => "America/Indianapolis", "MicrosoftTimeZoneIndex" => "040"),
                Array("MageTimeZone" => "America/Creston", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Dawson_Creek", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Hermosillo", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Phoenix", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "Etc/GMT+7", "MicrosoftTimeZoneIndex" => "015"),
                Array("MageTimeZone" => "America/Danmarkshavn", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Etc/GMT", "MicrosoftTimeZoneIndex" => "085"),
                Array("MageTimeZone" => "Etc/GMT-12", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Funafuti", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Kwajalein", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Majuro", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Nauru", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Tarawa", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Wake", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "Pacific/Wallis", "MicrosoftTimeZoneIndex" => "285"),
                Array("MageTimeZone" => "America/Noronha", "MicrosoftTimeZoneIndex" => "075"),
                Array("MageTimeZone" => "Atlantic/South_Georgia", "MicrosoftTimeZoneIndex" => "075"),
                Array("MageTimeZone" => "Etc/GMT+2", "MicrosoftTimeZoneIndex" => "075"),
                Array("MageTimeZone" => "Etc/GMT+11", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Midway", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Niue", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Pacific/Pago_Pago", "MicrosoftTimeZoneIndex" => "280"),
                Array("MageTimeZone" => "Asia/Choibalsan", "MicrosoftTimeZoneIndex" => "227"),
                Array("MageTimeZone" => "Asia/Ulaanbaatar", "MicrosoftTimeZoneIndex" => "227"),
                Array("MageTimeZone" => "America/Caracas", "MicrosoftTimeZoneIndex" => "055"),
                Array("MageTimeZone" => "Asia/Sakhalin", "MicrosoftTimeZoneIndex" => "270"),
                Array("MageTimeZone" => "Asia/Ust-Nera", "MicrosoftTimeZoneIndex" => "270"),
                Array("MageTimeZone" => "Asia/Vladivostok", "MicrosoftTimeZoneIndex" => "270"),
                Array("MageTimeZone" => "Antarctica/Casey", "MicrosoftTimeZoneIndex" => "225"),
                Array("MageTimeZone" => "Australia/Perth", "MicrosoftTimeZoneIndex" => "225"),
                Array("MageTimeZone" => "Africa/Algiers", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Bangui", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Brazzaville", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Douala", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Kinshasa", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Lagos", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Libreville", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Luanda", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Malabo", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Ndjamena", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Niamey", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Porto-Novo", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Africa/Tunis", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Etc/GMT-1", "MicrosoftTimeZoneIndex" => "113"),
                Array("MageTimeZone" => "Arctic/Longyearbyen", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Amsterdam", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Andorra", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Berlin", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Busingen", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Gibraltar", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Luxembourg", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Malta", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Monaco", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Oslo", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Rome", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/San_Marino", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Stockholm", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Vaduz", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Vatican", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Vienna", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Europe/Zurich", "MicrosoftTimeZoneIndex" => "110"),
                Array("MageTimeZone" => "Antarctica/Mawson", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Aqtau", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Aqtobe", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Ashgabat", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Dushanbe", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Oral", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Samarkand", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Asia/Tashkent", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Etc/GMT-5", "MicrosoftTimeZoneIndex" => "TEST"),
                Array("MageTimeZone" => "Indian/Kerguelen", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Indian/Maldives", "MicrosoftTimeZoneIndex" => "185"),
                Array("MageTimeZone" => "Antarctica/DumontDUrville", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Etc/GMT-10", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Guam", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Port_Moresby", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Saipan", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Pacific/Truk", "MicrosoftTimeZoneIndex" => "275"),
                Array("MageTimeZone" => "Asia/Khandyga", "MicrosoftTimeZoneIndex" => "240"),
                Array("MageTimeZone" => "Asia/Yakutsk", "MicrosoftTimeZoneIndex" => "240"),
            );
            foreach ($timeZones as $time) {
                if ($time['MageTimeZone'] == $timeZone) {
                    $result = $time['MicrosoftTimeZoneIndex'];
                }
            }
        }
        return $result;
    }

    /**
     * get culture id needed for trial account
     *
     * @return mixed
     */
    public function getCultureId()
    {
        $fallback = 'en_US';
        $supportedCultures = array(
            'en_US' => '1033',
            'en_GB' => '2057',
            'fr_FR' => '1036',
            'es_ES' => '3082',
            'de_DE' => '1031',
            'it_IT' => '1040',
            'ru_RU' => '1049',
            'pt_PT' => '2070'
        );
        $localeCode = $this->getWebsiteConfig(\Magento\Directory\Helper\Data::XML_PATH_DEFAULT_LOCALE);
        if (isset($supportedCultures[$localeCode])) {
            return $supportedCultures[$localeCode];
        }
        return $supportedCultures[$fallback];
    }

    /**
     * save api credentials
     *
     * @param $apiUser
     * @param $apiPass
     * @return bool
     */
    public function saveApiCreds($apiUser, $apiPass)
    {
        try {
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED, '1', 'default', 0
            );
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_USERNAME, $apiUser, 'default', 0
            );
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_PASSWORD, $apiPass, 'default', 0
            );

            //Clear config cache
            $this->_objectManager->get('Magento\Framework\App\Config\ReinitableConfigInterface')->reinit();
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * setup data fields
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */

    public function setupDataFields($username, $password)
    {
        $error = false;
        $apiModel = $this->getWebsiteApiClient(0, $username, $password);
        if (!$apiModel) {
            $error = true;
            $this->log('setupDataFields client is not enabled');
        } else {
            $dataFields = $this->_dataField->getContactDatafields();
            foreach ($dataFields as $key => $dataField) {
                $response = $apiModel->postDataFields($dataField);
                //ignore existing datafields message
                if (isset($response->message) &&
                    $response->message != \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_DATAFIELD_EXISTS
                ) {
                    $error = true;
                } else {
                    try {
                        //map the successfully created data field
                        $this->saveConfigData(
                            'connector_data_mapping/customer_data/' . $key,
                            strtoupper($dataField['name']), 'default', 0);
                        $this->log('successfully connected : ' . $dataField['name']);
                    } catch (\Exception $e) {
                        $this->_logger->critical($e);
                        $error = true;
                    }
                }
            }
        }
        return $error == true ? false : true;
    }

    /**
     * create certain address books
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    public function createAddressBooks($username, $password)
    {
        $addressBooks = array(
            array('name' => 'Magento_Customers', 'visibility' => 'Private'),
            array('name' => 'Magento_Subscribers', 'visibility' => 'Private'),
            array('name' => 'Magento_Guests', 'visibility' => 'Private'),
        );
        $addressBookMap = array(
            'Magento_Customers'
            => \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID,
            'Magento_Subscribers'
            => \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
            'Magento_Guests'
            => \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_GUEST_ADDRESS_BOOK_ID
        );
        $error = false;
        $client = $this->getWebsiteApiClient(0, $username, $password);
        if (!$client) {
            $error = true;
            $this->log('createAddressBooks client is not enabled');
        } else {
            foreach ($addressBooks as $addressBook) {
                $addressBookName = $addressBook['name'];
                $visibility = $addressBook['visibility'];
                if (strlen($addressBookName)) {
                    $response = $client->postAddressBooks($addressBookName, $visibility);
                    if (isset($response->message)) {
                        $error = true;
                    } else {
                        try {
                            //map the successfully created address book
                            $this->saveConfigData($addressBookMap[$addressBookName], $response->id, 'default', 0);
                            $this->log('successfully connected address book : ' . $addressBookName);
                        } catch (\Exception $e) {
                            $this->_logger->critical($e);
                            $error = true;
                        }
                    }
                }
            }
        }
        return $error == true ? false : true;
    }

    /**
     * enable certain syncs for newly created trial account
     *
     * @return bool
     */
    public function enableSyncForTrial()
    {
        try {
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED, '1', 'default', 0
            );
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED, '1', 'default', 0
            );
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED, '1', 'default', 0
            );
            $this->saveConfigData(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, '1', 'default', 0
            );
            return true;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return false;
        }
    }

    /**
     * save api endpoint
     *
     * @param $value
     */
    public function saveApiEndPoint($value)
    {
        $this->saveConfigData(
            \Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT, $value, 'default', 0
        );
    }

    /**
     * check if both frotnend and backend secure(HTTPS)
     *
     * @return bool
     */
    public function isFrontendAdminSecure()
    {
        $frontend = $this->_storeManager->getStore()->isFrontUrlSecure();
        $admin = $this->getWebsiteConfig(\Magento\Store\Model\Store::XML_PATH_SECURE_IN_ADMINHTML);
        $current = $this->_storeManager->getStore()->isCurrentlySecure();

        if ($frontend && $admin && $current) {
            return true;
        }

        return false;
    }
}