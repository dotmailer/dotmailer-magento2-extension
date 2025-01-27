<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Config\Source\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Program implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * Program constructor.
     *
     * @param Data $data
     * @param Registry $registry
     */
    public function __construct(
        Data $data,
        Registry $registry
    ) {
        $this->helper = $data;
        $this->registry = $registry;
    }

    /**
     * Get options.
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function toOptionArray()
    {
        $fields = [];
        $fields[] = ['value' => '0', 'label' => '-- Disabled --'];
        $website = $this->helper->getWebsiteForSelectedScopeInAdmin();
        $websiteId = (int) $website->getId();

        if ($this->helper->isEnabled($websiteId)) {
            $savedPrograms = $this->registry->registry('programs');

            //get saved datafields from registry
            if (is_array($savedPrograms)) {
                $programs = $savedPrograms;
            } else {
                //grab the datafields request and save to registry
                $programs = [];
                do {
                    $client = $this->helper->getWebsiteApiClient($websiteId);
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
                //sort programs by status
                $statusOrder = ['Active','Draft','ReadOnly','Deactivated','NotAvailableInThisVersion'];

                $statusIndices = array_map(function ($program) use ($statusOrder) {
                    return array_search($program->status, $statusOrder) ?? PHP_INT_MAX;
                }, $programs);

                $nameIndices = array_map(function ($program) {
                    return $program->name; // assuming 'name' is the alphabetically sortable property
                }, $programs);

                array_multisort($statusIndices, SORT_ASC, $nameIndices, SORT_ASC, $programs);

                //loop for all programs option
                foreach ($programs as $program) {
                    if (isset($program->id) && isset($program->name) && isset($program->status)) {
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
     * Get program status.
     *
     * @param string $programStatus
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
