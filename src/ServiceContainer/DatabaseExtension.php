<?php

namespace Gvf\DatabaseExtension\ServiceContainer;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Gvf\DatabaseExtension\EventListener\AutoIncrResetterListener;
use Gvf\DatabaseExtension\EventListener\DbLoaderListener;
use Gvf\DatabaseExtension\EventListener\TablePurgerListener;

class DatabaseExtension implements ExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigKey()
    {
        return 'gvf_database';
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        if ($extensionManager->getExtension('symfony2') === null) {
            throw new \Exception('Symfony2Extension is needed to run Migrations extension!');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
            ->scalarNode('entity_manager')
            ->isRequired()
            ->end()
            ->scalarNode('db_schema_path')
            ->end()
            ->arrayNode('reset_autoincr')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $definition = new Definition(TablePurgerListener::class, [
                new Reference('symfony2_extension.kernel'),
                $config['entity_manager'],
            ]
        );
        $definition->addTag('event_dispatcher.subscriber', ['priority' => 10]);
        $container->setDefinition('migrations_extension.table_purger', $definition);

        if (isset($config['db_schema_path'])) {

            $definition = new Definition(DbLoaderListener::class, [
                    new Reference('symfony2_extension.kernel'),
                    $config['db_schema_path'],
                ]
            );
            $definition->addTag('event_dispatcher.subscriber');
            $container->setDefinition('migrations_extension.db_loader', $definition);
        }

        $definition = new Definition(AutoIncrResetterListener::class, [
                new Reference('symfony2_extension.kernel'),
                $config['entity_manager'],
                $config['reset_autoincr'],
            ]
        );
        $definition->addTag('event_dispatcher.subscriber', ['priority' => 5]);
        $container->setDefinition('migrations_extension.auto_incr_resetter', $definition);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
    }
}
