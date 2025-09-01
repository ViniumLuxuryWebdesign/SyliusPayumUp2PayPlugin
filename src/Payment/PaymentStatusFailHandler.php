<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Payum\Core\Model\Identity;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Webmozart\Assert\Assert;

class PaymentStatusFailHandler
{
    private EntityManagerInterface $entityManager;
    private EntityRepository $paymentSecurityTokenRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $paymentSecurityTokenRepository
    ) {
        $this->entityManager = $entityManager;
        $this->paymentSecurityTokenRepository = $paymentSecurityTokenRepository;
    }

    public function fail(PaymentInterface $paymentFailed): void
    {
        $order = $paymentFailed->getOrder();

        // Check if there's already a successful payment for this order
        foreach ($order->getPayments() as $payment) {
            if ($payment->getState() === PaymentInterface::STATE_COMPLETED) {
                // There's already a successful payment, do nothing
                return;
            }
        }
        
        // Check if the "failed" payment actually contains a success response from Up2Pay
        $details = $paymentFailed->getDetails();
        if (isset($details['Reponse']) && $details['Reponse'] === '00000') {
            // This payment has a success response from Up2Pay, don't treat it as a failure
            // This can happen when a failure notification arrives before a success notification
            // and Sylius marks the payment as failed before the success notification is processed
            return;
        }
        
        $newPayment = $order->getLastPayment(PaymentInterface::STATE_NEW);
        
        // If no new payment exists, don't create a duplicate
        if (!$newPayment || $newPayment === $paymentFailed) {
            return;
        }
        

        $newPayment->setDetails($paymentFailed->getDetails());
        $this->entityManager->flush();
        $this->updatePaymentSecurityToken($newPayment);

    }

    /**
     * @author https://github.com/FLUX-SE/SyliusPayumMoneticoPlugin/commit/1941290f566613ef722ab9c1d8403773c4f09678
     */
    private function updatePaymentSecurityToken(PaymentInterface $newPayment): void
    {
        $order = $newPayment->getOrder();
        Assert::notNull($order);

        foreach ($order->getPayments() as $payment) {
            $identify = new Identity($payment->getId(), get_class($payment));
            /** @var TokenInterface[] $tokens */
            $tokens = $this->paymentSecurityTokenRepository->findBy(
                [
                    'details' => $identify,
                ]
            );
            if (count($tokens) === 0) {
                continue;
            }

            $newIdentify = new Identity($newPayment->getId(), get_class($newPayment));
            foreach ($tokens as $token) {
                $token->setDetails($newIdentify);
            }
        }
    }
}
