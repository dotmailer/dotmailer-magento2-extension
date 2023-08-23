<?php
declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Lists;

use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Magento\Framework\Exception\LocalizedException;

class ListFetcher
{
    /**
     * Fetch all lists.
     *
     * @param Client $client
     * @return array
     * @throws LocalizedException
     */
    public function fetchAllLists(Client $client)
    {
        $lists = [];
        do {
            if (!is_array($listsResponse = $client->getAddressBooks(count($lists)))) {
                return (array) $listsResponse;
            }
            $lists = array_merge($lists, $listsResponse);
        } while (count($listsResponse) === 1000);

        return $lists;
    }
}
