<?php

namespace Gvf\DatabaseExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class TablePurgerListener implements EventSubscriberInterface
{
    /**
     * @var KernelInterface
     */
    private $appKernel;

    /**
     * @var string
     */
    private $entityManagerServiceId;

    /**
     * @param KernelInterface $appKernel
     * @param string          $entityManagerServiceId
     */
    public function __construct(KernelInterface $appKernel, $entityManagerServiceId)
    {
        $this->appKernel = $appKernel;
        $this->entityManagerServiceId = $entityManagerServiceId;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // Not sure why we need the 2 ExampleTested events are thrown on the first test, then ScenarioTested events
            // are thrown in following tests
            ExampleTested::BEFORE => 'purgeTables',
            ScenarioTested::BEFORE => 'purgeTables',
        ];
    }

    public function purgeTables()
    {
        $appContainer = $this->appKernel->getContainer();
        $entityManagerServiceId = $appContainer->get($this->entityManagerServiceId);
        $connection = $entityManagerServiceId->getConnection();
        $connection->executeUpdate('SET foreign_key_checks = 0;');
        $connection->getConfiguration()->setSQLLogger(null);
        $purger = new ORMPurger($entityManagerServiceId);
        $purger->purge();
        $entityManagerServiceId->clear();
        $connection->executeUpdate('SET foreign_key_checks = 1;');
        $connection->close();
    }
}
