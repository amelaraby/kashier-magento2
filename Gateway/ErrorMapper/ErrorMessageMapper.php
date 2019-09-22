<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Gateway\ErrorMapper;

class ErrorMessageMapper extends \Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapper
{
    public function getMessage(string $code)
    {
        $message = parent::getMessage($code);
        return $message ?: __($code);
    }
}
