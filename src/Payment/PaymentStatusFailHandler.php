<?php

declare(strict_types=1);

namespace Vinium\SyliusPayumUp2PayPlugin\Payment;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Payum\Core\Model\Identity;
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
        $newPayment = $order->getLastPayment(PaymentInterface::STATE_NEW);
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
