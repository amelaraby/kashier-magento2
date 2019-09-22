<?php
/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Gateway\Http\Client;

use ITeam\Kashier\Model\Adapter\KashierAdapter;
use ITeam\Kashier\Model\Adapter\KashierAdapterFactory;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;

class Kashier implements ClientInterface
{
    /**
     * @var KashierAdapter $adapter
     */
    protected $adapter;

    public function __construct(KashierAdapterFactory $adapterFactory)
    {
        $this->adapter = $adapterFactory->create();
    }

    public function placeRequest(TransferInterface $transferObject)
    {
        $body = $transferObject->getBody();
        if (isset($body['three_ds_response']) && !empty($body['three_ds_response'])) {
            return json_decode($body['three_ds_response'], true);
        }

        return $this->adapter->checkout($transferObject->getBody());
    }
}
