<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Payum\Action;

use Vinium\SyliusUp2PayPlugin\Payum\Bridge\EtransactionsBridgeInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Sylius\Component\Core\Model\PaymentInterface;
use Payum\Core\Request\Notify;
use Sylius\Component\Payment\PaymentTransitions;
use Webmozart\Assert\Assert;
use SM\Factory\FactoryInterface;

final class NotifyAction implements ActionInterface, ApiAwareInterface
{
    private $api = [];

    /**
     * @var EtransactionsBridgeInterface
     */
    private $etransactionsBridge;

    /**
     * @var FactoryInterface
     */
    private $stateMachineFactory;

    /**
     * @param EtransactionsBridgeInterface $etransactionsBridge
     * @param FactoryInterface $stateMachineFactory
     */
    public function __construct(
        EtransactionsBridgeInterface $etransactionsBridge,
        FactoryInterface $stateMachineFactory
    )
    {
        $this->etransactionsBridge = $etransactionsBridge;
        $this->stateMachineFactory = $stateMachineFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request)
    {
        /** @var $request Notify */
        RequestNotSupportedException::assertSupports($this, $request);

        if ($this->etransactionsBridge->paymentVerification($this->api['hmac'])) {
            /** @var PaymentInterface $payment */
            $payment = $request->getFirstModel();
            Assert::isInstanceOf($payment, PaymentInterface::class);
            $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH)->apply(PaymentTransitions::TRANSITION_COMPLETE);;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (!is_array($api)) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayObject
        ;
    }
}
