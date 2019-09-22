<?php

/**
 * @author Ahmed El-Araby <araby2305@gmail.com>
 */

namespace ITeam\Kashier\Controller\Index;

use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\SessionFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

class ThreeDS extends Action implements HttpPostActionInterface
{
    private $result;
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        Context $context,
        SessionFactory $checkoutSessionFactory
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSessionFactory->create();
        $this->result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
    }

    public function execute()
    {
        $threeDSecureData = $this->checkoutSession->getThreeDSecureData();
        $this->checkoutSession->setThreeDSecureData(null);

        return $this->result->setData($threeDSecureData);
    }
}
