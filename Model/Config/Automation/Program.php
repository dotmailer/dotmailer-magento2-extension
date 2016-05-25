<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Program implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * Program constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data         $data
     * @param \Magento\Framework\App\RequestInterface    $requestInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Registry                $registry
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Registry $registry
    ) {
        $this->_helper = $data;
        $this->_request = $requestInterface;
        $this->_storeManager = $storeManagerInterface;
        $this->_registry = $registry;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $fields = [];
        $fields[] = ['value' => '0', 'label' => '-- Disabled --'];

        $websiteName = $this->_request->getParam('website', false);
        $website = ($websiteName)
            ? $this->_storeManager->getWebsite($websiteName) : 0;

        if ($this->_helper->isEnabled($website)) {
            $savedPrograms = $this->_registry->registry('programs');

            //get saved datafileds from registry
            if (is_array($savedPrograms)) {
                $programs = $savedPrograms;
            } else {
                //grab the datafields request and save to register
                $client = $this->_helper->getWebsiteApiClient($website);
                $programs = $client->getPrograms();
                $this->_registry->unregister('programs');
                $this->_registry->register('programs', $programs);
            }

            //set the api error message for the first option
            if (isset($programs->message)) {
                //message
                $fields[] = ['value' => 0, 'label' => $programs->message];
            } elseif (!empty($programs)) {
                //loop for all programs option
                foreach ($programs as $program) {
                    if (isset($program->id) && $program->status == 'Active') {
                        $fields[] = [
                            'value' => $program->id,
                            'label' => addslashes($program->name),
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
