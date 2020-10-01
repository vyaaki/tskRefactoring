<?php

namespace App\Command;

use App\Helper\CommissionCalculatorHelper;
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
    protected static $defaultName = 'task:commission:calculate';
    /**
     * @var Serializer
     */
    private $serializer;

//    /**
//     * @var ParameterBagInterface
//     */
//    private $parameters;
    /**
     * @var CommissionCalculatorHelper
     */
    private $commissionCalculatorHelper;

    /**
     * CommissionCalculatorCommand constructor.
     * @param CommissionCalculatorHelper $commissionCalculatorHelper
     * @param string|null $name
     */
    public function __construct(CommissionCalculatorHelper $commissionCalculatorHelper,string $name = null)
    {

        $this->commissionCalculatorHelper = $commissionCalculatorHelper;
//        $this->parameters = $parameterBag;

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
        parent::__construct($name);

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

            $ratesData = $this->commissionCalculatorHelper->getDecodedRates();
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

                $calculatedValue = $this->commissionCalculatorHelper->calculateCommission($transactionRow, $ratesList);
                $io->comment(sprintf("Commission: %s", $calculatedValue));
            }
            $io->success('Successfully calculated');
            return Command::SUCCESS;
        } else {
            $io->error("File not found");
            return Command::FAILURE;
        }

    }
}
