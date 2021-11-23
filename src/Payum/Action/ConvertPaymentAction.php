<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum\Action;

use Payum\Core\ApiAwareTrait;
use Vinium\SyliusPayumUp2PayPlugin\Payum\PayboxParams;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Convert;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Sylius\Component\Core\Model\PaymentInterface;

class ConvertPaymentAction implements ActionInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

    private $payboxParams;

    public function __construct(PayboxParams $payboxParams)
    {
        $this->payboxParams = $payboxParams;
    }

    /**
     * {@inheritdoc}
     *
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        /** @var PaymentInterface $payment */
        $payment = $request->getSource();
        $order = $payment->getOrder();
        $details = ArrayObject::ensureArrayObject($payment->getDetails());
        $details[PayboxParams::PBX_TOTAL] = $payment->getAmount();
        $details[PayboxParams::PBX_DEVISE] = $this->payboxParams->convertCurrencyToCurrencyCode($payment->getCurrencyCode());
        $details[PayboxParams::PBX_CMD] = $order->getNumber();
        $details[PayboxParams::PBX_PORTEUR] = $order->getCustomer()->getEmail();
        $details[PayboxParams::PBX_BILLING] = $this->payboxParams->setBilling($order);
        $details[PayboxParams::PBX_SHOPPINGCART] = $this->payboxParams->setShoppingCart($order->countItems());
        $token = $request->getToken();
        $details[PayboxParams::PBX_EFFECTUE] = $token->getTargetUrl();
        $details[PayboxParams::PBX_ANNULE] = $token->getTargetUrl();
        $details[PayboxParams::PBX_REFUSE] = $token->getTargetUrl();
        $details[PayboxParams::PBX_ATTENTE] = $token->getTargetUrl();
        /*$details[PayboxParams::PBX_TYPEPAIEMENT] = 'CARTE';
        $details[PayboxParams::PBX_TYPECARTE] = 'CB';*/

        // Prevent duplicated payment error
        if (strpos($token->getGatewayName(), 'sandbox') !== false) {
            $details[PayboxParams::PBX_CMD] = sprintf('%s-%d', $details[PayboxParams::PBX_CMD], time());
        }

        if (false == isset($details[PayboxParams::PBX_REPONDRE_A]) && $this->tokenFactory) {
            $notifyToken = $this->tokenFactory->createNotifyToken($token->getGatewayName(), $payment);
            $details[PayboxParams::PBX_REPONDRE_A] = $notifyToken->getTargetUrl();
        }

        $request->setResult((array) $details);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() == 'array'
            ;
    }
}
