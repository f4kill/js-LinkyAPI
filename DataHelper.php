<?php

namespace Linky;

class DataHelper
{
    /**
     * @param array|null $data
     * @param string     $dataset
     * @param array|null $newData
     */
    public static function merge(?array &$data, string $dataset, ?array $newData)
    {
        if ($data[$dataset] !== null) {
            $data[$dataset] = $data[$dataset] + $newData;
        } else {
            $data[$dataset] = $newData;
        }
        ksort($data[$dataset]);
    }
}
