<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Model\Adapter;

use ITeam\Kashier\Api\Checkout;
use ITeam\Kashier\Api\Data\CheckoutRequest;
use ITeam\Kashier\Api\Data\TokenizationRequest;
use ITeam\Kashier\Auth\KashierKey;
use ITeam\Kashier\Core\KashierConstants;
use ITeam\Kashier\Exception\KashierConfigurationException;
use ITeam\Kashier\Exception\KashierConnectionException;
use ITeam\Kashier\Model\Ui\ConfigProvider;
use ITeam\Kashier\Rest\ApiContext;
use ITeam\Kashier\Security\CheckoutRequestCipher;
use ITeam\Kashier\Security\CheckoutWithTokenRequestCipher;
use ITeam\Kashier\Security\TokenizationRequestCipher;
use Magento\Framework\ObjectManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Symfony\Component\DependencyInjection\Tests\Compiler\K;

class KashierAdapter
{
    protected $objectManager;
    protected $config;
    protected $apiContext;

    public function __construct(ObjectManagerInterface $objectManager, ConfigInterface $config)
    {
        $this->objectManager = $objectManager;
        $this->config = $config;
        $this->config->setMethodCode(ConfigProvider::CODE);

        $this->apiContext = $this->_initApiContext();
    }

    protected function _initApiContext()
    {
        $apiKey = $this->objectManager->create(KashierKey::class, [
            'apiKey' => $this->_getCurrentEnvironmentApiKey()
        ]);

        return $this->objectManager->create(ApiContext::class, [
            'merchantId' => $this->config->getValue('merchant_identifier'),
            'credential' => $apiKey
        ]);
    }

    protected function _getCurrentEnvironmentApiKey()
    {
        return $this->config->getValue('test_mode')
            ? $this->config->getValue('test_api_key')
            : $this->config->getValue('api_key');
    }

    public function getKashierBaseUrl()
    {
        return $this->config->getValue('test_mode')
            ? KashierConstants::REST_SANDBOX_ENDPOINT
            : KashierConstants::REST_LIVE_ENDPOINT;
    }

    /**
     * @param array $requestData
     * @return array
     * @throws KashierConfigurationException
     * @throws KashierConnectionException
     */
    public function checkout(array $requestData)
    {
        unset($requestData['three_ds_response']);
        $checkoutRequest = $this->objectManager->create(CheckoutRequest::class, [
            'data' => $requestData
        ]);
        $checkoutRequest->setMid($this->config->getValue('merchant_identifier'));

        /** @var Checkout $checkout */
        $checkout = $this->objectManager->create(Checkout::class);
        $checkout->setCheckoutRequest($checkoutRequest);

        $cipher = $this->objectManager->create(CheckoutWithTokenRequestCipher::class, [
            'apiContext' => $this->apiContext,
            'checkoutRequest' => $checkoutRequest
        ]);

        return $checkout->create($this->apiContext, $cipher)->toArray();
    }

    public function getTokenizationHash(array $requestData)
    {
        $tokenizationRequest = $this->objectManager->create(TokenizationRequest::class, [
            'data' => $requestData
        ]);

        $cipher = $this->objectManager->create(TokenizationRequestCipher::class, [
            'apiContext' => $this->apiContext,
            'tokenizationRequest' => $tokenizationRequest
        ]);

        return $cipher->encrypt();
    }

    public function getMerchantId()
    {
        return $this->apiContext->getMerchantId();
    }
}
