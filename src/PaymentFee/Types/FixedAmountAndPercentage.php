<?php


declare(strict_types=1);

namespace SyliusMolliePlugin\PaymentFee\Types;

use SyliusMolliePlugin\Entity\MollieGatewayConfig;
use SyliusMolliePlugin\Order\AdjustmentInterface;
use SyliusMolliePlugin\Payments\PaymentTerms\Options;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Webmozart\Assert\Assert;

final class FixedAmountAndPercentage implements SurchargeTypeInterface
{
    /** @var AdjustmentFactoryInterface */
    private $adjustmentFactory;

    /** @var Percentage */
    private $percentage;

    /** @var FixedAmount */
    private $fixedAmount;

    public function __construct(
        AdjustmentFactoryInterface $adjustmentFactory,
        Percentage $percentage,
        FixedAmount $fixedAmount
    ) {
        $this->adjustmentFactory = $adjustmentFactory;
        $this->percentage = $percentage;
        $this->fixedAmount = $fixedAmount;
    }

    public function calculate(OrderInterface $order, MollieGatewayConfig $paymentMethod): OrderInterface
    {
        $paymentSurchargeFee = $paymentMethod->getPaymentSurchargeFee();
        Assert::notNull($paymentSurchargeFee);
        Assert::notNull($paymentSurchargeFee->getSurchargeLimit());
        $limit = $paymentSurchargeFee->getSurchargeLimit() * 100;

        $percentage = $this->percentage->calculate($order, $paymentMethod);
        $fixed = $this->fixedAmount->calculate($order, $paymentMethod);

        $percentageAmount = $this->getSumOfCalculatedValue($percentage->getAdjustments(AdjustmentInterface::PERCENTAGE_ADJUSTMENT));
        $fixAmount = $this->getSumOfCalculatedValue($fixed->getAdjustments(AdjustmentInterface::FIXED_AMOUNT_ADJUSTMENT));

        $amount = $percentageAmount + $fixAmount;

        if ($amount > $limit) {
            $amount = $limit;
        }

        $order->removeAdjustments(AdjustmentInterface::FIXED_AMOUNT_ADJUSTMENT);
        $order->removeAdjustments(AdjustmentInterface::PERCENTAGE_ADJUSTMENT);

        if (false === $order->getAdjustments(AdjustmentInterface::PERCENTAGE_AND_AMOUNT_ADJUSTMENT)->isEmpty()) {
            $order->removeAdjustments(AdjustmentInterface::PERCENTAGE_AND_AMOUNT_ADJUSTMENT);
        }

        /** @var AdjustmentInterface $adjustment */
        $adjustment = $this->adjustmentFactory->createNew();
        $adjustment->setType(AdjustmentInterface::PERCENTAGE_AND_AMOUNT_ADJUSTMENT);
        $adjustment->setAmount((int) ceil($amount));
        $adjustment->setNeutral(false);
        $order->addAdjustment($adjustment);

        return $order;
    }

    public function canCalculate(string $type): bool
    {
        return Options::FIXED_FEE_AND_PERCENTAGE === array_search($type, Options::getAvailablePaymentSurchargeFeeType(), true);
    }

    private function getSumOfCalculatedValue(Collection $adjustments): float
    {
        $value = 0;

        /** @var AdjustmentInterface $adjustment */
        foreach ($adjustments as $adjustment) {
            $value += $adjustment->getAmount();
        }

        return $value;
    }
}
