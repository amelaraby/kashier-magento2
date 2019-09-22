<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Helper;

use DateTime;
use Magento\Framework\ObjectManagerInterface;

class Data
{
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }


    public function formatYear($year, $from_format = 'y', $to_format = 'Y')
    {
        $dt = DateTime::createFromFormat($from_format, $year);
        return $dt->format($to_format);
    }

    public function dataToKashierModel($data, $className)
    {
        return $this->objectManager->create($className, [
            'data' => $data
        ]);
    }
}
