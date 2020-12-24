<?php

namespace App\Entity;

class Order extends Entity
{
    const SIDE_BUY = 'buy';
    const SIDE_SELL = 'sell';

    const TYPE_QUANTITY = 'quantity';
    const TYPE_CASH_BALANCE = 'balance';

    const STATUS_NEW = 'new';
    const STATUS_PARTIALLY_FILLED = 'partially_filled';
    const STATUS_FILLED = 'filled';
    const STATUS_DONE_FOR_DAY = 'done_for_day';
    const STATUS_CANCELED = 'canceled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REPLACED = 'replaced';
    const STATUS_PENDING_CANCEL = 'pending_cancel';
    const STATUS_PENDING_REPLACE = 'pending_replace';

    private string $externalId;
    private int $qty;
    private string $side;
    private Security $security;
    private string $type;
    private int $avgPrice;
    private int $limitPrice;
    private string $status;
    private bool $reconciled;

    public function __construct()
    {
        $this->id = uniqid('O:');
        $this->qty = 0;
        $this->side = '';
        $this->avgPrice = 0;
        $this->status = self::STATUS_NEW;
        $this->type = self::TYPE_QUANTITY;
        $this->reconciled = false;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getQty(): int
    {
        return $this->qty;
    }

    public function setQty(int $qty): self
    {
        $this->qty = $qty;
        return $this;
    }

    public function getSide(): string
    {
        return $this->side;
    }

    public function setSide(string $side): self
    {
        $this->side = $side;
        return $this;
    }

    public function getAvgPrice(): int
    {
        return $this->avgPrice;
    }

    public function setAvgPrice(int $avgPrice): self
    {
        $this->avgPrice = $avgPrice;
        return $this;
    }

    public function getSecurity(): Security
    {
        return $this->security;
    }

    public function setSecurity(Security $security): self
    {
        $this->security = $security;
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

    public function getStatus(): string 
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getLimitPrice(): ?int 
    {
        return $this->limitPrice;
    }

    public function setLimitPrice(int $limit): self
    {
        $this->limitPrice = $limit;
        return $this;
    }

    public function setReconciled(bool $reconciled): self
    {
        $this->reconciled = $reconciled;
        return $this;
    }

    public function isReconciled(): bool
    {
        return $this->reconciled;
    }
}