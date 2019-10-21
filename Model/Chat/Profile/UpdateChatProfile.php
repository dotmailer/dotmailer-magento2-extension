<?php

namespace Dotdigitalgroup\Email\Model\Chat\Profile;

use Dotdigitalgroup\Email\Helper\Data as Helper;
use Dotdigitalgroup\Email\Model\Chat\Api\Requests\UpdateProfile;

class UpdateChatProfile
{
    /**
     * @var Data
     */
    private $data;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var UpdateProfile
     */
    private $updateProfile;

    /**
     * @param Data $data
     * @param UpdateProfile $updateProfile
     * @param Helper $helper
     */
    public function __construct(
        Data $data,
        UpdateProfile $updateProfile,
        Helper $helper
    ) {
        $this->data = $data;
        $this->helper = $helper;
        $this->updateProfile = $updateProfile;
    }

    /**
     * @param string $profileId
     * @param string|null $emailAddress
     * @return void
     */
    public function update(string $profileId, string $emailAddress = null)
    {
        try {
            $data = $this->data->getDataForChatUser();
        } catch (\Exception $e) {
            $this->helper->debug(__('Error fetching customer or scope data for Chat'), [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
        }

        // patch profile if email or first/last names are available
        if (
            (isset($data['customer']['email']) || $emailAddress)
            || isset($data['customer']['firstName'], $data['customer']['lastName'])
        ) {
            $this->updateProfile->send($profileId, array_filter([
                'firstName' => $data['customer']['firstName'] ?? null,
                'lastName' => $data['customer']['lastName'] ?? null,
                'email' => $emailAddress ?: $data['customer']['email'],
            ]));
        }
    }
}
