<?php

namespace Gvf\DatabaseExtension\EventListener;

use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class AutoIncrResetterListener implements EventSubscriberInterface
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
     * @var string[]
     */
    private $tables;

    /**
     * @param KernelInterface $appKernel
     * @param string          $entityManagerServiceId
     * @param string[]        $tables
     */
    public function __construct(KernelInterface $appKernel, $entityManagerServiceId, $tables)
    {
        $this->appKernel = $appKernel;
        $this->entityManagerServiceId = $entityManagerServiceId;
        $this->tables = $tables;
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
        if (empty($this->tables)) {
            return;
        }

        $appContainer = $this->appKernel->getContainer();
        $entityManagerServiceId = $appContainer->get($this->entityManagerServiceId);
        $connection = $entityManagerServiceId->getConnection();
        foreach ($this->tables as $table) {
            $connection->executeUpdate('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1');
        }
        $connection->close();
    }
}
