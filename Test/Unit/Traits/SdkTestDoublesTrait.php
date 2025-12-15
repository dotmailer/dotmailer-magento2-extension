<?php

namespace Dotdigitalgroup\Email\Test\Unit\Traits;

use Dotdigital\V3\Models\Contact as ContactModel;

trait SdkTestDoublesTrait
{
    /**
     * Create a Contact model with channel properties for testing.
     *
     * In PHPUnit 10+ we can no longer use addMethods() to mock magic getter methods.
     * Because the SDK models use such getters heavily, it's easier to create real instances.
     *
     * @param int $contactId
     * @param string $status
     * @return ContactModel
     * @throws \Exception
     */
    protected function createContactModelWithChannelProperties(int $contactId, string $status): ContactModel
    {
        $contact = new ContactModel([
            'contactId' => $contactId,
            'channelProperties' => [
                'email' => [
                    'status' => $status
                ]
            ]
        ]);

        return $contact;
    }
}
