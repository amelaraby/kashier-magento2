<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace ITeam\Kashier\Gateway\Validator;

use InvalidArgumentException;
use ITeam\Kashier\Api\Checkout;
use ITeam\Kashier\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Model\SessionFactory;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class CaptureValidator extends AbstractValidator
{

    /**
     * @var Data
     */
    private $dataHelper;
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        Data $dataHelper,
        SessionFactory $checkoutSessionFactory
    ) {
        parent::__construct($resultFactory);
        $this->dataHelper = $dataHelper;
        $this->checkoutSession = $checkoutSessionFactory->create();
    }

    /**
     * Performs validation of response
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response'])
            || !is_array($validationSubject['response'])) {
            throw new InvalidArgumentException('Response does not exist');
        }

        /** @var Checkout $response */
        $response = $this->dataHelper->dataToKashierModel($validationSubject['response'], Checkout::class);
        $responseData = $response->getResponse();
        if ($response->isSuccess()) {
            return $this->createResult(true);
        }

        if ($response->is3DsRequired()) {
            $this->checkoutSession->setThreeDSecureData($responseData['card']['3DSecure']);
            return $this->createResult(false, [__('3DSecure verification required')]);
        }

        return $this->createResult(false, [__($response->getErrorMessage())]);
    }
}
