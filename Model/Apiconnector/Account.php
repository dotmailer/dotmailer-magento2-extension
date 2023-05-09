<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

class Account
{
    private const EMAIL_PROPERTY_NAME = 'MainEmail';
    private const API_ENDPOINT_PROPERTY_NAME = 'ApiEndpoint';

    /**
     * Get account owner email.
     *
     * @param object $accountDetails
     * @return string
     */
    public function getAccountOwnerEmail(object $accountDetails): string
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
    public function getApiEndpoint(object $accountDetails): string
    {
        if (isset($accountDetails->properties)) {
            foreach ($accountDetails->properties as $property) {
                if ($property->name == self::API_ENDPOINT_PROPERTY_NAME && !empty($property->value)) {
                    return $property->value;
                }
            }
        }

        return '';
    }

    /**
     * Get account id.
     *
     * @param object $accountDetails
     * @return string|null
     */
    public function getAccountId(object $accountDetails): ?string
    {
        return $accountDetails->id ?? null;
    }

    /**
     * Get region prefix.
     *
     * @param string $apiEndpoint
     * @return string
     */
    public function getRegionPrefix($apiEndpoint)
    {
        preg_match("/https:\/\/(.*)api(.*).(dotmailer|dotdigital).com/", $apiEndpoint, $matches);
        return $matches[1] ?? '';
    }
}
