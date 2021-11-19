<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payum\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Symfony\Component\HttpFoundation\RequestStack;
use Vinium\SyliusPayumUp2PayPlugin\Legacy\Etransactions;

final class StatusAction implements ActionInterface
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
     *
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $details = ArrayObject::ensureArrayObject($request->getModel());
        $requestCurrent = $this->requestStack->getCurrentRequest();
        $transactionReference = isset($details['transactionReference']) ? $details['transactionReference'] : null;
        $error = !empty($requestCurrent->get('Erreur')) ? $requestCurrent->get('Erreur') : null;
        $ref = !empty($requestCurrent->get('Ref')) ? $requestCurrent->get('Ref') : null;
        if ((null === $transactionReference || $transactionReference !== $ref) && !$requestCurrent->isMethod('POST')) {
            $request->markNew();
            return;
        }
        if (Etransactions::RESPONSE_SUCCESS === $error) {
            $request->markCaptured();
            return;
        } elseif (Etransactions::RESPONSE_PENDING === $error) {
            $request->markPending();
            return;
        } elseif (Etransactions::RESPONSE_FAILED_MIN <= $error && Etransactions::RESPONSE_FAILED_MAX >= $error) {
            $request->markFailed();
            return;
        } else {
            $request->markCanceled();
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request)
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
