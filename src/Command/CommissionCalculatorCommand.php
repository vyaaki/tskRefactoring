<?php

namespace App\Command;

use App\Model\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class CommissionCalculatorCommand extends Command
{
    const EUR_CURRENCY_CODE = 'EUR';
    protected static $defaultName = 'task:commission:calculate';
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ParameterBagInterface
     */
    private $parameters;

    /**
     * CommissionCalculatorCommand constructor.
     * @param ParameterBagInterface $parameterBag
     * @param string|null $name
     */
    public function __construct(ParameterBagInterface $parameterBag ,string $name = null)
    {

        $this->parameters = $parameterBag;
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $serializer = new Serializer($normalizers, $encoders);
        parent::__construct($name);
        $this->serializer = $serializer;
    }

    protected function configure()
    {
        $this
            ->setDescription('Calculate commissions for already made transactions')
            ->addArgument('arg1', InputArgument::REQUIRED, 'Input file with transactions');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');
        if (file_exists($arg1)) {
            $io->note(sprintf('File found: %s', $arg1));
            $transactions = file($arg1);

            $ratesData = $this->serializer->decode(file_get_contents($this->parameters->get('app.rates_data_url')), 'json');
            if(is_array($ratesData) && array_key_exists('rates' , $ratesData)){
                $ratesList = $ratesData['rates'];
                $io->note("List of rates is ready");
            }
            else{
                $io->error("Error: Can't get list of rates. Invalid URL or incoming data");
                return Command::FAILURE;
            }
            foreach ($transactions as $transactionRow) {
                /** @var Transaction $transaction */
                $transaction = $this->serializer->deserialize($transactionRow, Transaction::class, 'json');

                $bin = $this->serializer->decode(file_get_contents($this->parameters->get('app.bin_data_url') . $transaction->getBin()), 'json');
                $isEu = $this->isEu($bin['country']['alpha2']);
                $transactionCurrency = $transaction->getCurrency();
                if(array_key_exists($transactionCurrency,$ratesList)){
                    $rate= $ratesList[$transactionCurrency];
                }
                else{
                    $rate = 0;
                    $io->note(sprintf("Can't find rate for BIN: %s", $transaction->getBin()));
                }
                if ($transactionCurrency == $this::EUR_CURRENCY_CODE or $rate == 0.00) {
                    $amountFixed = $transaction->getAmount();
                }
                if ($transactionCurrency != $this::EUR_CURRENCY_CODE or $rate > 0.00) {
                    $amountFixed = $transaction->getAmount() / $rate;
                }
                $io->comment(sprintf("Commission for BIN %s: %s", $transaction->getBin() , $this->ceiling(($amountFixed * ($isEu ? 0.01 : 0.02)))));
            }
            $io->success('Successfully calculated');
            return Command::SUCCESS;
        } else {
            $io->error("File not found");
            return Command::FAILURE;
        }

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
