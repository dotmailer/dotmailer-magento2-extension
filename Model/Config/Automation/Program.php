<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

/**
 * Class Program
 * @package Dotdigitalgroup\Email\Model\Config\Automation
 */
class Program implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request;

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
        $this->helper       = $data;
        $this->request      = $requestInterface;
        $this->storeManager = $storeManagerInterface;
        $this->registry     = $registry;
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

        $websiteName = $this->request->getParam('website', false);
        $website = ($websiteName)
            ? $this->storeManager->getWebsite($websiteName) : 0;

        if ($this->helper->isEnabled($website)) {
            $savedPrograms = $this->registry->registry('programs');

            //get saved datafileds from registry
            if (is_array($savedPrograms)) {
                $programs = $savedPrograms;
            } else {
                //grab the datafields request and save to register
                $client = $this->helper->getWebsiteApiClient($website);
                $programs = $client->getPrograms();
                $this->registry->unregister('programs');
                $this->registry->register('programs', $programs);
            }

            //set the api error message for the first option
            if (isset($programs->message)) {
                //message
                $fields[] = ['value' => 0, 'label' => $programs->message];
            } elseif (!empty($programs)) {
                //loop for all programs option
                foreach ($programs as $program) {
                    if (isset($program->id) && $program->status == 'Active') {
                        //@codingStandardsIgnoreStart
                        $fields[] = [
                            'value' => $program->id,
                            'label' => addslashes($program->name),
                        ];
                        //@codingStandardsIgnoreEnd
                    }
                }
            }
        }

        return $fields;
    }
}
