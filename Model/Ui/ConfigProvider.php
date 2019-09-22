<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Model\Ui;

use ITeam\Kashier\Core\KashierConstants;
use ITeam\Kashier\Model\Adapter\KashierAdapter;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\CcConfig;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'kashier';
    const CC_VAULT_CODE = 'kashier_cc_vault';

    /**
     * @var CcConfig
     */
    private $ccConfig;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var Session
     */
    private $customerSession;
    /**
     * @var KashierAdapter
     */
    private $kashierAdapter;

    public function __construct(
        CcConfig $ccConfig,
        UrlInterface $urlBuilder,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory,
        KashierAdapter $kashierAdapter
    ) {
        $this->ccConfig = $ccConfig;
        $this->urlBuilder = $urlBuilder;
        $this->customerSession = $customerSessionFactory->create();
        $this->kashierAdapter = $kashierAdapter;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'threeDsUrl' => $this->urlBuilder->getUrl('kashier/index/threeDs'),
                    'vaultCode' => self::CC_VAULT_CODE,
                    'tokenizationHash' => $this->_getTokenizationHash(),
                    'tokenizationUrl' => $this->kashierAdapter->getKashierBaseUrl()
                        . KashierConstants::URL_PATH_TOKENIZATION,
                    'shopperReference' => $this->customerSession->getId(),
                    'merchantId' => $this->kashierAdapter->getMerchantId()
                ],
                'ccform' => [
                    'icons' => [
                        'MEEZA' => $this->getMeezaIcon()
                    ]
                ]
            ]
        ];
    }

    private function _getTokenizationHash()
    {
        return $this->kashierAdapter->getTokenizationHash([
            'shopper_reference' => $this->customerSession->getId()
        ]);
    }

    public function getMeezaIcon()
    {
        $asset = $this->ccConfig->createAsset('ITeam_Kashier::images/meeza.png');
        list($width, $height) = getimagesize($asset->getSourceFile());
        return [
            'url' => $asset->getUrl(),
            'width' => $width,
            'height' => $height
        ];
    }
}
