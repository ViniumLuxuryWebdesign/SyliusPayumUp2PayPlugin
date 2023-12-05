<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\GatewayAwareInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Vinium\SyliusPayumUp2PayPlugin\Payum\Api;

class StatusAction implements ApiAwareInterface, GatewayAwareInterface, ActionInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    const RESPONSE_SUCCESS = '00000';
    const RESPONSE_CANCELED = '00001';
    const RESPONSE_FAILED_PLATFORM = '00003';
    const RESPONSE_FAILED_CVV = '00004';
    const RESPONSE_FAILED_VALIDITY = '00008';
    const RESPONSE_FAILED_CREATE_SUBSCRIPTION = '00009';
    const RESPONSE_FAILED_CARD_UNAUTHORIZED = '00021';
    const RESPONSE_FAILED_UNCOMPLIANT = '00029';
    const RESPONSE_FAILED_TOOLONG = '00030';
    const RESPONSE_FAILED_UNAUTHORIZED_COUNTRY = '00033';
    const RESPONSE_FAILED_3DS_BLOCKED = '00040';
    const RESPONSE_FAILED_MIN = '00100';
    const RESPONSE_FAILED_MAX = '00199';
    const RESPONSE_PENDING_VALIDATION = '99999';

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * {@inheritdoc}
     *
     * @param GetStatusInterface $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());
        if (empty($model['Reponse'])) {
            $request->markNew();

            return;
        }
        // Rely only on NotifyAction to update payment
        if (isset($model['notify'])) {
            $request->setModel($request->getFirstModel());
            if (self::RESPONSE_SUCCESS === $model['Reponse']) {
                $request->markCaptured();
            } elseif (self::isFailureErrorCode($model['Reponse'])) {
                $request->markFailed();
            } elseif (self::RESPONSE_PENDING_VALIDATION === $model['Reponse']) {
                $request->markPending();
            } else {
                $request->markCanceled();
            }
            unset($model['notify']);
        } else {
            // To make Sylius display a correct message (PayumController:afterCaptureAction)
            // And because request is in state unknown
            // Let's mark the request with the state of the payment
            // Because IPN notification will always be handled by the server before user action
            $paymentState = $request->getFirstModel()->getState();
            switch ($paymentState) {
                case PaymentInterface::STATE_NEW:
                    // Request is marked pending in case of success whereas the payment is still marked as new,
                    // meaning the IPN didn't reach the capture endpoint before the user return to the shop.
                    // (when testing locally the IPN typically won't reach the endpoint)
                    if (self::RESPONSE_SUCCESS === $model['Reponse']) {
                        if ($this->api->isLocal()) {
                            $request->markCaptured();
                        } else {
                            $request->markPending();
                        }
                    } elseif (self::RESPONSE_PENDING_VALIDATION === $model['Reponse']) {
                        $request->markPending();
                    } elseif (self::isFailureErrorCode($model['Reponse'])) {
                        $request->markFailed();
                    } elseif (self::RESPONSE_CANCELED === $model['Reponse']) {
                        $request->markCanceled();
                    } else {
                        $request->markNew();
                    }
                    break;

                case PaymentInterface::STATE_COMPLETED:
                    $request->markCaptured();
                    break;

                case PaymentInterface::STATE_FAILED:
                    $request->markFailed();
                    break;

                default:
                    $request->markCanceled();
                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
            ;
    }

    protected static function isFailureErrorCode($errorCode): bool
    {
        if (
            self::RESPONSE_FAILED_MIN <= $errorCode && self::RESPONSE_FAILED_MAX >= $errorCode ||
            $errorCode === self::RESPONSE_FAILED_CVV ||
            $errorCode === self::RESPONSE_FAILED_VALIDITY ||
            $errorCode === self::RESPONSE_FAILED_CARD_UNAUTHORIZED ||
            $errorCode === self::RESPONSE_FAILED_CREATE_SUBSCRIPTION ||
            $errorCode === self::RESPONSE_FAILED_UNCOMPLIANT ||
            $errorCode === self::RESPONSE_FAILED_3DS_BLOCKED ||
            $errorCode === self::RESPONSE_FAILED_UNAUTHORIZED_COUNTRY ||
            $errorCode === self::RESPONSE_FAILED_PLATFORM ||
            $errorCode === self::RESPONSE_FAILED_TOOLONG
        ) {
            return true;
        }

        return false;
    }
}
