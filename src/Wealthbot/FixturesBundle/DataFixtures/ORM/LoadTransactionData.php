<?php
/**
 * Created by JetBrains PhpStorm.
 * User: amalyuhin
 * Date: 21.08.13
 * Time: 15:10
 * To change this template use File | Settings | File Templates.
 */

namespace Wealthbot\FixturesBundle\DataFixtures\ORM;


use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Wealthbot\AdminBundle\Entity\Transaction;
use Wealthbot\ClientBundle\Entity\Lot;
use Wealthbot\FixturesBundle\Model\AbstractCsvFixture;

class LoadTransactionData extends AbstractCsvFixture implements OrderedFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        $accountRepo = $manager->getRepository('WealthbotClientBundle:SystemAccount');
        $securityRepo = $manager->getRepository('WealthbotAdminBundle:Security');
        $closingMethodRepo = $manager->getRepository('WealthbotAdminBundle:ClosingMethod');
        $transactionTypeRepo = $manager->getRepository('WealthbotAdminBundle:TransactionType');

        $transactions = $this->getCsvData('transactions.csv');

        $i = $flush = 0;
        foreach ($transactions as $item) {
            if (count($item) < 18) {
                continue;
            }
            //$advisorCode = trim($item[0]);
            //$fileDate = trim($item[1]);
            $accountNumber = trim($item[2]);
            $transactionCode = trim($item[3]);
            $cancelStatusFlag = trim($item[4]);
            $symbol = trim($item[5]);
            //$securityCode = trim($item[6]);
            $txDate = trim($item[7]);
            $qty = trim($item[8]);
            $netAmount = trim($item[9]);
            $grossAmount = trim($item[10]);
            //$brokerFee = trim($item[11]);
            //$otherFee = trim($item[12]);
            $settleDate = trim($item[13]);
            //$transferAccount = trim($item[14]);
            //$accountType = trim($item[15]);
            $accruedInterest = trim($item[16]);
            $closingMethodCode = trim($item[17]);
            $notes = trim($item[18]);

            //create transactions table and leave data for lots.

            $account = $accountRepo->findOneBy(array('account_number' => $accountNumber));
            $security = $securityRepo->findOneBySymbol($symbol);
            $closingMethod = $closingMethodRepo->findOneBy(array('name' => $closingMethodCode));
            $transactionType = $transactionTypeRepo->findOneBy(array('name' => $transactionCode));

            if ($account && $security) {
                $transaction = new Transaction();
                $transaction->setCancelStatus((bool)($cancelStatusFlag == 'Y'));
                $transaction->setTxDate(new \DateTime($txDate));
                $transaction->setQty($qty);
                $transaction->setNetAmount($netAmount);
                $transaction->setGrossAmount($netAmount * 1.03);
                $transaction->setSettleDate(new \DateTime($settleDate));
                $transaction->setAccruedInterest(!empty($accruedInterest) ?: null);
                $transaction->setNotes($notes);
                $transaction->setStatus(Transaction::STATUS_PLACED);

                $transaction->setAccount($account);
                $transaction->setTransactionType($transactionType);
                $transaction->setClosingMethod($closingMethod);
                $transaction->setLot(null);
                $transaction->setSecurity($security);

                $manager->persist($transaction);
                $flush = true;

                if ((++$i % 100) == 0) {
                    $flush = false;
                    $manager->flush();
                }
            }
        }

        if ($flush) {
            $manager->flush();
        }
    }


    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    function getOrder()
    {
        return 9;
    }

}