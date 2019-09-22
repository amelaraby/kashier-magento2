<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Gateway\Request;

use InvalidArgumentException;
use ITeam\Kashier\Helper\Data;
use ITeam\Kashier\Validation\CreditCardValidator;
use LogicException;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Exception\ValidatorException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CaptureRequest implements BuilderInterface
{
    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @param Data $dataHelper
     */
    public function __construct(Data $dataHelper, SessionFactory $customerSessionFactory)
    {
        $this->dataHelper = $dataHelper;
        $this->customerSession = $customerSessionFactory->create();
    }

    /**
     * @param array $buildSubject
     *
     * @throws ValidatorException
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new LogicException('Order payment should be provided.');
        }

        return [
            'amount' => $buildSubject['amount'],
            'cardToken' => $payment->getAdditionalInformation('cc_token'),
            'ccvToken' => $payment->getAdditionalInformation('ccv_token'),
            'shopper_reference' => $this->customerSession->getId(),
            'orderId' => $order->getOrderIncrementId(),
            'currency' => $order->getCurrencyCode(),
            'display' => 'en',
            'three_ds_response' => $payment->getAdditionalInformation('three_ds_response')
        ];
    }
}
