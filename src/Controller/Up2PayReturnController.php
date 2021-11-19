<?php

declare(strict_types=1);

namespace Vinium\SyliusUp2PayPlugin\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

final class Up2PayReturnController extends AbstractController
{
    /** @var FlashBagInterface */
    private $flashBag;
    /** @var OrderRepositoryInterface */
    private $orderRepository;
    /** @var FactoryInterface */
    private $stateMachineFactory;
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        FlashBagInterface $flashBag,
        OrderRepositoryInterface $orderRepository,
        FactoryInterface $stateMachineFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->flashBag = $flashBag;
        $this->orderRepository = $orderRepository;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->entityManager = $entityManager;
    }

    public function cancelAction($orderToken): RedirectResponse
    {
        /** @var OrderInterface $order */
        $order = $this->orderRepository->findOneBy(['tokenValue' => $orderToken]);
        if (!$order) {
            throw $this->createNotFoundException();
        }
        $payment = $order->getLastPayment();
        $paymentStateMachine = $this->stateMachineFactory->get($payment, PaymentTransitions::GRAPH);
        $paymentStateMachine->apply(PaymentTransitions::TRANSITION_CANCEL);
        $this->entityManager->flush();
        $this->flashBag->add('success', 'sylius.payment.cancelled');

        return new RedirectResponse($this->generateUrl('sylius_shop_order_show', ['tokenValue' => $orderToken, '_locale' => $order->getLocaleCode()]));
    }
}
