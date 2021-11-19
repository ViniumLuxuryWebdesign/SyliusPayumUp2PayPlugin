<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Payum\Action;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Vinium\SyliusUp2PayPlugin\Legacy\SimplePayment;
use Vinium\SyliusUp2PayPlugin\Payum\Bridge\EtransactionsBridgeInterface;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\Request\Capture;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webmozart\Assert\Assert;
use Payum\Core\Payum;
use Sylius\Component\Core\Model\OrderInterface;

final class CaptureAction implements ActionInterface, ApiAwareInterface
{
    private $api = [];
    /**
     * @var Payum
     */
    private $payum;
    /**
     * @var EtransactionsBridgeInterface
     */
    private $etransactionsBridge;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param Payum $payum
     * @param EtransactionsBridgeInterface $etransactionsBridge
     */
    public function __construct(
        Payum $payum,
        EtransactionsBridgeInterface $etransactionsBridge,
        RouterInterface $router,
        RequestStack $requestStack
    )
    {
        $this->etransactionsBridge = $etransactionsBridge;
        $this->payum = $payum;
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function setApi($api)
    {
        if (!\is_array($api)) {
            throw new UnsupportedApiException('Not supported.');
        }

        $this->api = $api;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());
        //if already exist return
        if (!empty($details['transactionReference'])) {
            return;
        }
        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        Assert::isInstanceOf($payment, PaymentInterface::class);
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var TokenInterface $token */
        $token = $request->getToken();
        $requestCurrent = $this->requestStack->getCurrentRequest();
        //generate token fort notifyAction
        $notifyToken = $this->createNotifyToken($token->getGatewayName(), $token->getDetails());
        $hmac = $this->api['hmac'];
        $etransactions = $this->etransactionsBridge->createEtransactions($hmac);
        $identifiant = $this->api['identifiant'];
        $rang = $this->api['rang'];
        $site = $this->api['site'];
        $sandbox = $this->api['sandbox'];
        $currencyCode = $payment->getCurrencyCode();
        $automaticResponseUrl = $notifyToken->getTargetUrl();
        $cancelUrl = $this->router->generate('vinium_sylius_up2pay_cancel', ['orderToken' => $order->getTokenValue(), '_locale' => $requestCurrent->getLocale()], UrlGeneratorInterface::ABSOLUTE_URL);
        $successUrl = $token->getAfterUrl();
        $customerEmail = $order->getCustomer()->getEmail();
        $amount = $payment->getAmount();
        $transactionReference = "etransactionsWS".uniqid($payment->getOrder()->getNumber());
        //set transaction reference
        $details['transactionReference'] = $transactionReference;
        $request->setModel($details);
        $simplePayment = new SimplePayment(
            $etransactions,
            $identifiant,
            $rang,
            $site,
            $sandbox,
            $amount,
            $currencyCode,
            $transactionReference,
            $customerEmail,
            $automaticResponseUrl,
            $successUrl,
            $cancelUrl
        );
        try {
            $simplePayment->execute();
        } catch (\Exception $e) {
            $this->payum->getHttpRequestVerifier()->invalidate($token);
            throw $e;
        }
    }

    /**
     * @param string $gatewayName
     * @param object $model
     *
     * @return TokenInterface
     */
    private function createNotifyToken($gatewayName, $model)
    {
        return $this->payum->getTokenFactory()->createNotifyToken(
            $gatewayName,
            $model
        );
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
