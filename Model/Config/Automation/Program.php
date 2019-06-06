<?php

namespace Dotdigitalgroup\Email\Model\Config\Automation;

class Program implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;
    
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * Program constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Escaper $escaper
    ) {
        $this->helper       = $data;
        $this->registry     = $registry;
        $this->escaper      = $escaper;
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
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();

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
                        $fields[] = [
                            'value' => $program->id,
                            'label' => $program->name,
                        ];
                    }
                }
            }
        }

        return $fields;
    }
}
