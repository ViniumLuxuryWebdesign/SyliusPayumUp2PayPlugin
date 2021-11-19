<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum\Bridge;

use Vinium\SyliusPayumUp2PayPlugin\Legacy\Etransactions;
use Symfony\Component\HttpFoundation\RequestStack;

final class EtransactionsBridge implements EtransactionsBridgeInterface
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritDoc}
     */
    public function createEtransactions($hmac)
    {
        return new Etransactions($hmac);
    }

    /**
     * {@inheritDoc}
     */
    public function paymentVerification($hmac)
    {
        if ($this->isGetMethod()) {
            $paymentResponse = new Etransactions($hmac);
            // Sale à remplacer par l'objet Request?
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
              $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
              $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
              $ip = $_SERVER['REMOTE_ADDR'];
            }

            return $paymentResponse->isValid($_GET, $ip);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isGetMethod()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return $currentRequest->isMethod('GET');
    }

    /**
     * {@inheritDoc}
     */
    public function isPostMethod()
    {
        $currentRequest = $this->requestStack->getCurrentRequest();

        return $currentRequest->isMethod('POST');
    }
}
