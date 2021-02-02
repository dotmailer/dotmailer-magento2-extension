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

            //get saved datafields from registry
            if (is_array($savedPrograms)) {
                $programs = $savedPrograms;
            } else {
                //grab the datafields request and save to register
                $programs = [];
                do {
                    $client = $this->helper->getWebsiteApiClient($website);
                    $programResponse = $client->getPrograms(count($programs));

                    if (is_object($programResponse)) {
                        $programs = $programResponse;
                        break;
                    }

                    $programs = array_merge($programs, $programResponse);
                } while (count($programResponse) === 1000);
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
                    if (isset($program->id)) {
                        $fields[] = [
                            'value' => $program->id,
                            'label' => $program->name . $this->getProgramStatus($program->status),
                        ];
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * @param $programStatus
     * @return \Magento\Framework\Phrase|string
     */
    private function getProgramStatus($programStatus)
    {
        switch ($programStatus) {
            case 'Deactivated':
                return __(' (Deactivated)');
            case 'Draft':
                return __(' (Draft)');
            default:
                return '';
        }
    }
}
