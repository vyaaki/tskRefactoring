<?php


namespace App\Helper;


use App\Model\Transaction;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DataReceiverHelper
{

    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    public function __construct(ParameterBagInterface $parameterBag)
    {

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        $this->serializer = $serializer;
        $this->parameters = $parameterBag;
    }

    /**
     * @param Transaction $transaction
     * @return string
     */
    public function getDecodedBINCountry(Transaction $transaction): string
    {
        $result = $this->serializer->decode(file_get_contents($this->parameters->get('app.bin_data_url') . $transaction->getBin()), 'json');
        return $result['country']['alpha2'];
    }

    /**
     * @return array
     */
    public function getDecodedRates(): array
    {
        return $this->serializer->decode(file_get_contents($this->parameters->get('app.rates_data_url')), 'json')['rates'];
    }
}