<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Gateway\Response;

use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;

class VaultCaptureHandler extends AbstractCaptureHandler
{
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->getPaymentObject($handlingSubject);

        $this->handleTransactionInfo($payment, $response);
    }
}
