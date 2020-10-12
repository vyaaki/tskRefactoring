<?php

namespace App\Command;

use App\Helper\CommissionCalculatorHelper;
use App\Helper\DataReceiverHelper;
use App\Model\Transaction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CommissionCalculatorCommand extends Command
{
    protected static $defaultName = 'task:commission:calculate';

    /**
     * @var CommissionCalculatorHelper
     */
    private $commissionCalculatorHelper;
    /**
     * @var DataReceiverHelper
     */
    private $dataReceiver;

    /**
     * CommissionCalculatorCommand constructor.
     * @param CommissionCalculatorHelper $commissionCalculatorHelper
     * @param DataReceiverHelper $dataReceiverHelper
     * @param string|null $name
     */
    public function __construct(CommissionCalculatorHelper $commissionCalculatorHelper, DataReceiverHelper $dataReceiverHelper, string $name = null)
    {

        $this->commissionCalculatorHelper = $commissionCalculatorHelper;

        $this->dataReceiver = $dataReceiverHelper;
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

            $ratesList = $this->dataReceiver->getDecodedRates();
            $io->note("List of rates is ready");

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
