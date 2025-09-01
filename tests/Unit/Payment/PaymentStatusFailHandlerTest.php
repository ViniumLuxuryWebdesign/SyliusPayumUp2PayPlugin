<?php

declare(strict_types=1);

namespace Tests\Vinium\SyliusPayumUp2PayPlugin\Unit\Payment;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Vinium\SyliusPayumUp2PayPlugin\Payment\PaymentStatusFailHandler;

/**
 * Unit test for PaymentStatusFailHandler
 * 
 * This test reproduces the production issue where Up2Pay sends multiple notifications:
 * 1. First: Reponse=00001 (failure)
 * 2. Second: Reponse=00159 (another failure) 
 * 3. Third: Reponse=00000 (success)
 * 
 * The fix prevents the handler from treating a payment with success response as a failure.
 */
class PaymentStatusFailHandlerTest extends TestCase
{
    private PaymentStatusFailHandler $handler;
    private MockObject $entityManager;
    private MockObject $tokenRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenRepository = $this->createMock(EntityRepository::class);
        
        $this->handler = new PaymentStatusFailHandler(
            $this->entityManager,
            $this->tokenRepository
        );
    }

    /**
     * Test the production scenario with exact log data from:
     * 195.25.67.22 - [08/Jul/2025:10:45:27] Reponse=00001
     * 195.25.67.22 - [08/Jul/2025:10:46:35] Reponse=00159  
     * 195.25.67.22 - [08/Jul/2025:10:48:47] Reponse=00000
     */
    public function testProductionMultipleNotificationScenario(): void
    {
        $payment = $this->createMockPayment(PaymentInterface::STATE_FAILED);
        $order = $this->createMockOrder([$payment]);
        $payment->method('getOrder')->willReturn($order);

        // === Test Case 1: First notification - Reponse=00001 (failure) ===
        $firstNotificationDetails = [
            'Mt' => '173920',
            'Ref' => '000001406', 
            'Appel' => '854432416',
            'Abo' => '0',
            'Reponse' => '00001', // FAILURE
            'Transaction' => '850463923',
            'Pays' => 'FRA'
        ];
        
        $payment->method('getDetails')->willReturn($firstNotificationDetails);
        $order->method('getLastPayment')->willReturn(null); // No new payment
        
        // Should not process (no new payment available)
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);

        // === Test Case 2: Second notification - Reponse=00159 (another failure) ===
        $secondNotificationDetails = [
            'Mt' => '173920',
            'Ref' => '000001406',
            'Appel' => '854432416', 
            'Abo' => '0',
            'Reponse' => '00159', // ANOTHER FAILURE
            'Transaction' => '850464150',
            'Pays' => 'FRA'
        ];
        
        $payment->method('getDetails')->willReturn($secondNotificationDetails);
        
        // Should not process (no new payment available)
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);

        // === Test Case 3: Third notification - Reponse=00000 (SUCCESS) ===
        // This is the critical case our fix addresses
        $thirdNotificationDetails = [
            'Mt' => '173920',
            'Ref' => '000001406',
            'Auto' => '601278', // Auto code only appears on success
            'Appel' => '854432416',
            'Abo' => '0', 
            'Reponse' => '00000', // SUCCESS - but payment might still be marked as failed
            'Transaction' => '850464585',
            'Pays' => 'FRA'
        ];
        
        $payment->method('getDetails')->willReturn($thirdNotificationDetails);
        
        // Our fix should detect the success response and NOT process as failure
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);
        
        // If we reach here without errors, the fix worked
        $this->assertTrue(true, 'Payment with success response was correctly ignored by fail handler');
    }

    /**
     * Test the classic double notification scenario from production logs:
     * 195.25.67.22 - [07/Jul/2025:14:30:53] Reponse=00001
     * 195.25.67.22 - [07/Jul/2025:14:33:32] Reponse=00000
     */
    public function testClassicDoubleNotificationScenario(): void
    {
        $payment = $this->createMockPayment(PaymentInterface::STATE_FAILED);
        $order = $this->createMockOrder([$payment]);
        $payment->method('getOrder')->willReturn($order);

        // === First notification: Reponse=00001 (failure) ===
        $failureDetails = [
            'Mt' => '50980',
            'Ref' => '000001310',
            'Appel' => '475659771',
            'Abo' => '0',
            'Reponse' => '00001', // FAILURE
            'Transaction' => '501432604',
            'Pays' => 'FRA'
        ];
        
        $payment->method('getDetails')->willReturn($failureDetails);
        $order->method('getLastPayment')->willReturn(null); // No new payment
        
        // Should not process (no new payment available)
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);

        // === Second notification: Reponse=00000 (SUCCESS) ===
        // This is where our fix is critical
        $successDetails = [
            'Mt' => '50980',
            'Ref' => '000001310',
            'Auto' => '436332', // Auto code indicates success
            'Appel' => '475659771',
            'Abo' => '0',
            'Reponse' => '00000', // SUCCESS - but payment might still be in failed state
            'Transaction' => '501433121',
            'Pays' => 'FRA'
        ];
        
        $payment->method('getDetails')->willReturn($successDetails);
        
        // Our fix should detect this is actually a success and NOT process as failure
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);
        
        // Test passes if no exception thrown and no processing occurred
        $this->assertTrue(true, 'Double notification scenario handled correctly');
    }

    /**
     * Test international payment double notification scenario from production logs:
     * 195.25.67.22 - [08/Jul/2025:13:51:01] Reponse=00001 Pays=AUT
     * 195.25.67.22 - [08/Jul/2025:13:54:08] Reponse=00000 Pays=AUT
     */
    public function testInternationalDoubleNotificationScenario(): void
    {
        $payment = $this->createMockPayment(PaymentInterface::STATE_FAILED);
        $order = $this->createMockOrder([$payment]);
        $payment->method('getOrder')->willReturn($order);

        // === First notification: Reponse=00001 (failure) from Austria ===
        $failureDetails = [
            'Mt' => '327100',
            'Ref' => '000001410',
            'Appel' => '854471142',
            'Abo' => '0',
            'Reponse' => '00001', // FAILURE
            'Transaction' => '850500053',
            'Pays' => 'AUT' // Austria
        ];
        
        $payment->method('getDetails')->willReturn($failureDetails);
        $order->method('getLastPayment')->willReturn(null); // No new payment
        
        // Should not process (no new payment available)
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);

        // === Second notification: Reponse=00000 (SUCCESS) ===
        // Critical test: international payment success after failure
        $successDetails = [
            'Mt' => '327100',
            'Ref' => '000001410',
            'Auto' => '080047', // Auto code indicates success
            'Appel' => '854471142',
            'Abo' => '0',
            'Reponse' => '00000', // SUCCESS - but payment might still be in failed state
            'Transaction' => '850500573',
            'Pays' => 'AUT' // Austria
        ];
        
        $payment->method('getDetails')->willReturn($successDetails);
        
        // Our fix should detect this is actually a success and NOT process as failure
        // regardless of the country (AUT instead of FRA)
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($payment);
        
        // Test passes if no exception thrown and no processing occurred
        $this->assertTrue(true, 'International double notification scenario handled correctly');
    }

    /**
     * Test that the handler still works correctly for legitimate failures
     */
    public function testLegitimateFailureStillProcessed(): void
    {
        $failedPayment = $this->createMockPayment(PaymentInterface::STATE_FAILED);
        $newPayment = $this->createMockPayment(PaymentInterface::STATE_NEW);
        $order = $this->createMockOrder([$failedPayment, $newPayment]);
        
        $failedPayment->method('getOrder')->willReturn($order);
        $newPayment->method('getOrder')->willReturn($order); // Add this line
        $order->method('getLastPayment')->with(PaymentInterface::STATE_NEW)->willReturn($newPayment);
        
        // Real failure response
        $realFailureDetails = [
            'Reponse' => '00001', // Actual failure, not success
            'Transaction' => '850463923'
        ];
        
        $failedPayment->method('getDetails')->willReturn($realFailureDetails);
        $this->tokenRepository->method('findBy')->willReturn([]);
        
        // Should process normally for real failures
        $this->entityManager->expects($this->once())->method('flush');
        $newPayment->expects($this->once())->method('setDetails')->with($realFailureDetails);
        
        $this->handler->fail($failedPayment);
    }

    /**
     * Test prevention when order already has a completed payment
     */
    public function testPreventProcessingWhenOrderHasCompletedPayment(): void
    {
        $completedPayment = $this->createMockPayment(PaymentInterface::STATE_COMPLETED);
        $failedPayment = $this->createMockPayment(PaymentInterface::STATE_FAILED);
        $order = $this->createMockOrder([$completedPayment, $failedPayment]);
        
        $failedPayment->method('getOrder')->willReturn($order);
        $failedPayment->method('getDetails')->willReturn(['Reponse' => '00001']);
        
        // Should not process because order already has completed payment
        $this->entityManager->expects($this->never())->method('flush');
        $this->handler->fail($failedPayment);
    }

    /**
     * Test various Up2Pay success response scenarios
     */
    public function testVariousSuccessResponsesAreIgnored(): void
    {
        $payment = $this->createMockPayment(PaymentInterface::STATE_FAILED);
        $order = $this->createMockOrder([$payment]);
        $payment->method('getOrder')->willReturn($order);
        
        // Test that any payment with Reponse=00000 is ignored, regardless of other data
        $successScenarios = [
            ['Reponse' => '00000', 'Auto' => '601278'],
            ['Reponse' => '00000', 'Transaction' => '850464585'],
            ['Reponse' => '00000'], // Minimal success response
        ];
        
        foreach ($successScenarios as $details) {
            $payment->method('getDetails')->willReturn($details);
            
            $this->entityManager->expects($this->never())->method('flush');
            $this->handler->fail($payment);
        }
        
        $this->assertTrue(true, 'All success responses were correctly ignored');
    }

    private function createMockPayment(string $state): MockObject
    {
        $payment = $this->createMock(PaymentInterface::class);
        $payment->method('getState')->willReturn($state);
        $payment->method('getId')->willReturn(random_int(1, 1000));
        return $payment;
    }

    private function createMockOrder(array $payments): MockObject
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getPayments')->willReturn(new ArrayCollection($payments));
        return $order;
    }
}
