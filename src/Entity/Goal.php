<?php

namespace App\Entity;

use Exception;

class Goal extends Entity implements LockableEntity
{
    use LockableTrait;
    
    private $name;
    private $holdings;
    private $userId;
    private $balance;
    private $plan;
    private $type;
    private $orders;

    public function __construct()
    {
        $this->id = uniqid('G:');
        $this->holdings = [];
        $this->orders = [];
        $this->balance = 0;

        $cash = new Security();
        $cash->setSymbol(Security::CASH)->setName(Security::CASH)->setType(Security::CASH)->setClass(Security::CASH);
        $holding = new Holding();
        $holding->setSecurity($cash);
        $this->addHolding($holding);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(Plan $plan): self
    {
        $this->plan = $plan;
        $this->setType($plan->getName());
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getHoldings(): array
    {
        return array_values($this->holdings);
    }

    public function addHolding(Holding $holding): self
    {
        $this->holdings[$holding->getSecurity()->getSymbol()] = $holding;
        $this->updateBalance();
        return $this;
    }

    public function removeHolding(Holding $holding): self
    {
        unset($this->holdings[$holding->getSecurity()->getSymbol()]);
        $this->updateBalance();
        return $this;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        /** @var Order $existingOrder */
        foreach($this->orders as $existingOrder) {
            if ($existingOrder->getSecurity()->getSymbol() !== $order->getSecurity()->getSymbol()) {
                continue;
            }

            $existingStatus = $order->getStatus();
            if(in_array($existingStatus, [Order::STATUS_NEW, Order::STATUS_PARTIALLY_FILLED, Order::STATUS_PENDING_CANCEL, Order::STATUS_PENDING_REPLACE, Order::STATUS_DONE_FOR_DAY]))  {
                throw new Exception('Goal contains active order for security: ' . $order->getSecurity()->getSymbol());
            }
        }

        $this->orders[] = $order;
        return $this;
    }

    public function removeOrder(Order $order): self
    {
        foreach ($this->orders as $key => $value) {
            if ($value->getId() === $order->getId()) {
                unset($this->orders[$key]);
                break;
            }
        }
        return $this;
    }

    public function orderById(string $id): ?Order
    {
        foreach($this->orders as $order) {
            if($order->getId() === $id) {
                return $order;
            }
        }
    }

    public function setBalance(int $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function updateBalance()
    {
        $this->balance = 0;

        /** @var Holding $holding */
        foreach ($this->holdings as $holding) {
            $this->balance += $holding->getValue();
        }
    }

    public function cashBalance(): int
    {
        $cash = $this->holdingBySymbol(Security::CASH);
        if ($cash) {
            return $cash->getValue();
        }

        return 0;
    }

    public function holdingBySymbol(string $symbol): ?Holding
    {
        return $this->holdings[$symbol] ?? null;
    }
}
