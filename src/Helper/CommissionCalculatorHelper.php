<?php


namespace App\Helper;


use App\Model\Transaction;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class CommissionCalculatorHelper
{

    const EUR_CURRENCY_CODE = 'EUR';
    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct()
    {

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $this->serializer = $serializer;
    }

    /**
     * @param string $transactionRow
     * @param $ratesList
     * @return float|int
     */
    public function calculateCommission(string $transactionRow, $ratesList)
    {

        $transaction = $this->serializer->deserialize($transactionRow, Transaction::class, 'json');
        $bin = $this->getDecodedBIN($transaction);
        $isEu = $this->isEu($bin['country']['alpha2']);
        $transactionCurrency = $transaction->getCurrency();
        if (array_key_exists($transactionCurrency, $ratesList)) {
            $rate = $ratesList[$transactionCurrency];
        } else {
            $rate = 0;
        }
        if ($transactionCurrency == $this::EUR_CURRENCY_CODE or $rate == 0.00) {
            $amountFixed = $transaction->getAmount();
        }
        if ($transactionCurrency != $this::EUR_CURRENCY_CODE or $rate > 0.00) {
            $amountFixed = $transaction->getAmount() / $rate;
        }
        $calculatedValue = $this->ceiling(($amountFixed * ($isEu ? 0.01 : 0.02)));
        return $calculatedValue;
    }


    /**
     * @param Transaction $transaction
     * @return array
     */
    private function getDecodedBIN(Transaction $transaction): array
    {
        return $this->serializer->decode(file_get_contents('https://lookup.binlist.net/' . $transaction->getBin()), 'json');
    }

    /**
     * @return array
     */
    public function getDecodedRates(): array
    {
        return $this->serializer->decode(file_get_contents('https://api.exchangeratesapi.io/latest'), 'json');
    }


    private function isEu($countryCode) {
        $result = false;
        switch($countryCode) {
            case 'AT':
            case 'BE':
            case 'BG':
            case 'CY':
            case 'CZ':
            case 'DE':
            case 'DK':
            case 'EE':
            case 'ES':
            case 'FI':
            case 'FR':
            case 'GR':
            case 'HR':
            case 'HU':
            case 'IE':
            case 'IT':
            case 'LT':
            case 'LU':
            case 'LV':
            case 'MT':
            case 'NL':
            case 'PO':
            case 'PT':
            case 'RO':
            case 'SE':
            case 'SI':
            case 'SK':
                $result = true;
        }
        return $result;
    }

    /**
     * @param $value
     * @return float|int
     */
    private function ceiling($value)
    {
        return ceil($value * 100) / 100;
    }
}