<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Account
{
    private const EMAIL_PROPERTY_NAME = 'MainEmail';
    private const APIENDPOINT_PROPERTY_NAME = 'ApiEndpoint';

    /**
     * Get account owner email.
     *
     * @param object $accountDetails
     * @return string
     */
    public function getAccountOwnerEmail(object $accountDetails)
    {
        if (isset($accountDetails->properties)) {
            foreach ($accountDetails->properties as $property) {
                if ($property->name === self::EMAIL_PROPERTY_NAME) {
                    return $property->value;
                }
            }
        }

        return '';
    }

    /**
     * Get account region.
     *
     * @param object $accountDetails
     * @return string
     */
    public function getApiEndpoint(object $accountDetails)
    {
        if (isset($accountDetails->properties)) {
            foreach ($accountDetails->properties as $property) {
                if ($property->name == self::APIENDPOINT_PROPERTY_NAME && !empty($property->value)) {
                    return $property->value;
                }
            }
        }

        return '';
    }

    /**
     * Get region prefix.
     *
     * @param string $apiEndpoint
     * @return string
     */
    public function getRegionPrefix($apiEndpoint)
    {
        preg_match("/https:\/\/(.*)api.(dotmailer|dotdigital).com/", $apiEndpoint, $matches);
        return $matches[1] ?? '';
    }
}
