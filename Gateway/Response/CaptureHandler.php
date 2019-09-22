<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Gateway\Response;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use ITeam\Kashier\Model\Ui\ConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

class CaptureHandler extends AbstractCaptureHandler
{
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        Json $serializer = null
    ) {
        parent::__construct($paymentTokenFactory, $paymentExtensionFactory, $serializer);
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->getPaymentObject($handlingSubject);

        $this->handleTransactionInfo($payment, $response);

        // add vault payment token entity to extension attributes
        $paymentToken = $this->getVaultPaymentToken($payment);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }


    /**
     * Get vault payment token entity
     *
     * @param Payment $payment
     * @return PaymentTokenInterface|null
     * @throws Exception
     */
    protected function getVaultPaymentToken($payment)
    {
        $token = $payment->getAdditionalInformation('cc_token');
        $paymentToken = $this->paymentTokenManagement->getByGatewayToken(
            $token,
            ConfigProvider::CODE,
            $payment->getOrder()->getCustomerId()
        );
        if ($paymentToken !== null) {
            return $paymentToken;
        }

        $ccData = $payment->getAdditionalInformation('cc_details');

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($token);
        $paymentToken->setExpiresAt($this->getExpirationDate($payment));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $ccData[OrderPaymentInterface::CC_TYPE],
            'maskedCC' => $ccData[OrderPaymentInterface::CC_LAST_4],
            'expirationMonth' => $ccData[OrderPaymentInterface::CC_EXP_MONTH],
            'expirationYear' => $ccData[OrderPaymentInterface::CC_EXP_YEAR]
        ]));

        return $paymentToken;
    }

    /**
     * @param $payment Payment
     * @return string
     * @throws Exception
     */
    private function getExpirationDate($payment)
    {
        $expDate = new DateTime(
            $payment->getCcExpYear()
            . '-'
            . $payment->getCcExpMonth()
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new DateTimeZone('UTC')
        );
        $expDate->add(new DateInterval('P1M'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = $this->serializer->serialize($details);
        return $json ? $json : '{}';
    }

    /**
     * Get payment extension attributes
     */
    protected function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
