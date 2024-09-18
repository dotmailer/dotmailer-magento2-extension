<?php

namespace Dotdigitalgroup\Email\ViewModel\Customer\Account;

use Dotdigitalgroup\Email\ViewModel\Customer\AccountSubscriptions;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Preferences implements ArgumentInterface
{
    /**
     * @var AccountSubscriptions
     */
    private $containerViewModel;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param AccountSubscriptions $containerViewModel
     * @param Session $customerSession
     */
    public function __construct(
        AccountSubscriptions $containerViewModel,
        Session $customerSession
    ) {
        $this->containerViewModel = $containerViewModel;
        $this->customerSession = $customerSession;
    }

    /**
     * Get preferences to show.
     *
     * @return array
     * @throws LocalizedException
     */
    public function getPreferencesToShow()
    {
        $preferences = [];
        $processedPreferences = [];
        $contactFromTable = $this->containerViewModel->getContactFromTable();

        if (!$contactFromTable) {
            $preferences = $this->containerViewModel->getApiClient()->getPreferences();
        } elseif ($contactFromTable->getContactId()) {
            $preferences = $this->containerViewModel->getApiClient()->getPreferencesForContact(
                $contactFromTable->getContactId()
            );
        } else {
            $contact = $this->containerViewModel->getConnectorContact();
            if (isset($contact->id)) {
                $preferences = $this->containerViewModel->getApiClient()->getPreferencesForContact($contact->id);
            }
        }

        if (!empty($preferences)) {
            $processedPreferences = $this->processPreferences($preferences, $processedPreferences);
        }
        $this->customerSession->setDmContactPreferences($processedPreferences);
        return $processedPreferences;
    }

    /**
     * Process preferences.
     *
     * @param array $preferences
     * @param array $processedPreferences
     *
     * @return mixed
     */
    private function processPreferences($preferences, $processedPreferences)
    {
        foreach ($preferences as $preference) {
            if (!$preference->isPublic) {
                continue;
            }
            $formattedPreference = [];
            $formattedPreference['isPreference'] = $preference->isPreference;
            if (! $preference->isPreference) {
                if ($this->hasNoPublicChildren($preference)) {
                    continue;
                }
                $formattedPreference['catLabel'] = $preference->publicName;
                $formattedCatPreferences = [];
                foreach ($preference->preferences as $catPreference) {
                    if (!$catPreference->isPublic) {
                        continue;
                    }
                    $formattedCatPreference = [];
                    $formattedCatPreference['label'] = $catPreference->publicName;
                    $formattedCatPreference['isOptedIn'] = $catPreference->isOptedIn ?? false;
                    $formattedCatPreferences[$catPreference->id] = $formattedCatPreference;
                }
                $formattedPreference['catPreferences'] = $formattedCatPreferences;
            } else {
                $formattedPreference['label'] = $preference->publicName;
                isset($preference->isOptedIn) ? $formattedPreference['isOptedIn'] = $preference->isOptedIn :
                    $formattedPreference['isOptedIn'] = false;
            }
            $processedPreferences[$preference->id] = $formattedPreference;
        }
        return $processedPreferences;
    }

    /**
     * Check if preference has no public children.
     *
     * @param \stdClass $preference
     * @return bool
     */
    private function hasNoPublicChildren($preference)
    {
        if (!isset($preference->preferences)) {
            return true;
        }
        foreach ($preference->preferences as $catPreference) {
            if ($catPreference->isPublic) {
                return false;
            }
        }
        return true;
    }
}
