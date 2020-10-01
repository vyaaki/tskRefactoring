<?php


namespace App\Model;


const DIVIDER = 100;
class Transaction
{
    /**
     * @var int
     */
    private $bin;
    /**
     * @var int
     */
    private $amount;
    /**
     * @var string
     */
    private $currency;

    /**
     * @return int
     */
    public function getBin() : int
    {
        return $this->bin;
    }

    /**
     * @param int $bin
     */
    public function setBin(int $bin): void
    {
        $this->bin = $bin;
    }

    /**
     * @return int
     */
    public function getAmount() : int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency() : string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

}