<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Gateway\Response;

use InvalidArgumentException;
use ITeam\Kashier\Model\Ui\ConfigProvider;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use RuntimeException;

abstract class AbstractCaptureHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;


    /**
     * @var Json
     */
    protected $serializer;

    /**
     * VaultDetailsHandler constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json|null $serializer
     * @throws RuntimeException
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $serializer = null
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    protected function getPaymentObject(array $handlingSubject)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        return $paymentDO->getPayment();
    }

    public function handleTransactionInfo($payment, array $response)
    {
        $payment->setTransactionId($response['response']['transactionId']);
        $payment->setMethod(ConfigProvider::CODE);
        $payment->setIsTransactionClosed(false);

        $ccData = $payment->getAdditionalInformation('cc_details');

        $transactionRawDetails = [
            'Credit Card Last 4 Digits' => $ccData[OrderPaymentInterface::CC_LAST_4],
            'Card Brand' => $ccData[OrderPaymentInterface::CC_TYPE],
            'Expiration Date' => $payment->getCcExpMonth() . ' / ' . $payment->getCcExpYear()
        ];

        /** @noinspection PhpParamsInspection */
        $payment->setTransactionAdditionalInfo(
            Payment\Transaction::RAW_DETAILS,
            $transactionRawDetails
        );
    }
}
