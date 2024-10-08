<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Export;

class DataFieldMapper
{
    /**
     * Map exported data to named data field keys.
     *
     * @param array $data
     * @param array $fieldMap
     *
     * @return array
     */
    public function mapFields(array $data, array $fieldMap): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, array_keys($fieldMap))
                && !empty($fieldMap[$key])
            ) {
                $data[$fieldMap[$key]] = $value;
                unset($data[$key]);
            }
        }

        return $data;
    }
}
