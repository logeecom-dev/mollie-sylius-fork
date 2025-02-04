<?php


declare(strict_types=1);

namespace SyliusMolliePlugin\Resolver\ApplePayDirect;

use SyliusMolliePlugin\Entity\MollieGatewayConfigInterface;
use SyliusMolliePlugin\Entity\OrderInterface;
use SyliusMolliePlugin\Payments\Methods\AbstractMethod;
use Sylius\Component\Core\Model\PaymentInterface;

final class ApplePayDirectPaymentTypeResolver implements ApplePayDirectPaymentTypeResolverInterface
{
    /** @var ApplePayDirectApiPaymentResolverInterface */
    private $apiPaymentResolver;

    /** @var ApplePayDirectApiOrderPaymentResolverInterface */
    private $apiOrderPaymentResolver;

    public function __construct(
        ApplePayDirectApiPaymentResolverInterface $apiPaymentResolver,
        ApplePayDirectApiOrderPaymentResolverInterface $apiOrderPaymentResolver
    ) {
        $this->apiPaymentResolver = $apiPaymentResolver;
        $this->apiOrderPaymentResolver = $apiOrderPaymentResolver;
    }

    public function resolve(
        MollieGatewayConfigInterface $mollieGatewayConfig,
        PaymentInterface $payment,
        array $applePayDirectToken
    ): void {
        $details = [];
        /** @var OrderInterface $order */
        $order = $payment->getOrder();

        if (null === $payment->getAmount()) {
            return;
        }
        $amount = number_format(abs($payment->getAmount() / 100), 2, '.', '');

        $details['amount'] = [
            'currency' => $payment->getCurrencyCode(),
            'value' => "$amount",
        ];

        $details['applePayDirectToken'] = json_encode($applePayDirectToken);
        $details['backurl'] = $payment->getDetails()['backurl'];
        if (AbstractMethod::ORDER_API === $mollieGatewayConfig->getPaymentType()) {
            $this->createPaymentOrder($order, $mollieGatewayConfig, $details);

            return;
        }

        $this->createPayment($order, $details);
    }

    private function createPayment(OrderInterface $order, array $details): void
    {
        $this->apiPaymentResolver->resolve($order, $details);
    }

    private function createPaymentOrder(
        OrderInterface $order,
        MollieGatewayConfigInterface $mollieGatewayConfig,
        array $details
    ): void {
        $this->apiOrderPaymentResolver->resolve($order, $mollieGatewayConfig, $details);
    }
}
