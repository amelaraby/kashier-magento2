<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Observer;

use ITeam\Kashier\Model\Ui\ConfigProvider;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

class KashierAddCcData extends AbstractDataAssignObserver
{
    private $additionalKeys = [
        'tokenization_response',
        'three_ds_response'
    ];
    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(
        PaymentTokenManagementInterface $paymentTokenManagement,
        SessionFactory $customerSessionFactory,
        SerializerInterface $serializer
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSessionFactory->create();
        $this->serializer = $serializer;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_array($additionalData)) {
            return;
        }

        if ($dataObject->getData(PaymentInterface::KEY_METHOD) === ConfigProvider::CC_VAULT_CODE) {
            $paymentToken = $this->paymentTokenManagement->getByPublicHash(
                $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA)['public_hash'],
                $this->customerSession->getId()
            );
            $paymentTokenDetails = $this->serializer->unserialize($paymentToken->getTokenDetails());
            $tokenizationResponse = [
                'cardToken' => $paymentToken->getGatewayToken(),
                'maskedCard' => $paymentTokenDetails['maskedCC'],
                'expiry_month' => $paymentTokenDetails['expirationMonth'],
                'expiry_year' => $paymentTokenDetails['expirationYear'],
            ];
            $additionalData['cc_type'] = $paymentTokenDetails['type'];
        } else {
            $tokenizationResponse = $this->serializer->unserialize(
                stripslashes($additionalData['tokenization_response'])
            );
            $tokenizationResponse = $tokenizationResponse['body']['response'];
            $tokenizationResponse['maskedCard'] = substr($tokenizationResponse['maskedCard'], -4);
        }

        $ccData[OrderPaymentInterface::CC_TYPE] = $additionalData['cc_type'];
        $ccData[OrderPaymentInterface::CC_LAST_4] = $tokenizationResponse['maskedCard'];
        $ccData[OrderPaymentInterface::CC_EXP_MONTH] = $tokenizationResponse['expiry_month'];
        $ccData[OrderPaymentInterface::CC_EXP_YEAR] = $tokenizationResponse['expiry_year'];

        $paymentModel = $this->readPaymentModelArgument($observer);

        $paymentModel->setAdditionalInformation(
            'cc_details',
            $ccData
        );

        $paymentModel->setAdditionalInformation(
            'cc_token',
            $tokenizationResponse['cardToken']
        );

        if (isset($tokenizationResponse['ccvToken'])) {
            $paymentModel->setAdditionalInformation(
                'ccv_token',
                $tokenizationResponse['ccvToken']
            );
        }

        foreach ($this->additionalKeys as $additionalKey) {
            if (isset($additionalData[$additionalKey]) && !empty($additionalData[$additionalKey])) {
                $paymentModel->setAdditionalInformation(
                    $additionalKey,
                    $additionalData[$additionalKey]
                );
            } else {
                $paymentModel->unsAdditionalInformation($additionalKey);
            }
        }

        // CC data should be stored explicitly
        foreach ($ccData as $ccKey => $ccValue) {
            $paymentModel->setData($ccKey, $ccValue);
        }
    }
}
