<?php

namespace Gvf\DatabaseExtension\EventListener;

use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class DbLoaderListener implements EventSubscriberInterface
{
    /**
     * @var KernelInterface
     */
    private $appKernel;

    /**
     * @var string
     */
    private $dbSchemaPath;

    public function __construct(KernelInterface $appKernel, string $dbSchemaPath)
    {
        $this->appKernel = $appKernel;
        $this->dbSchemaPath = $dbSchemaPath;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExerciseCompleted::BEFORE => 'loadDb',
        ];
    }

    /**
     * @throws \Exception
     */
    public function loadDb(): void
    {
        $appContainer = $this->appKernel->getContainer();
        $connection = $appContainer->get('database_connection');

        exec(
            'mysql -u' . $connection->getUsername() .
            ' -p' . $connection->getPassword() .
            ' -h' . $connection->getHost() .
            ' < ' . $this->appKernel->getProjectDir() . $this->dbSchemaPath
        );
    }
}
