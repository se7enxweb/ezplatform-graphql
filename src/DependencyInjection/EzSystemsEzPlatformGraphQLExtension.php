<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\EzPlatformGraphQL\DependencyInjection;

use EzSystems\EzPlatformGraphQL\DependencyInjection\GraphQL\YamlSchemaProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EzSystemsEzPlatformGraphQLExtension extends Extension implements PrependExtensionInterface
{
    private const APP_SCHEMA_DIR_RELATIVE_PATH = '/config/graphql';
    private const EZPLATFORM_SCHEMA_DIR_RELATIVE_PATH = '/ezplatform';
    private const PACKAGE_DIR_RELATIVE_PATH = '/../vendor/ezsystems/ezplatform-graphql';
    private const PACKAGE_SCHEMA_DIR_RELATIVE_PATH = '/src/Resources/config/graphql';
    private const FIELS_DEFINITION_FILE_NAME = 'Field.types.yml';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services/data_loaders.yml');
        $loader->load('services/mutations.yml');
        $loader->load('services/resolvers.yml');
        $loader->load('services/schema.yml');
        $loader->load('services/services.yml');
        $loader->load('default_settings.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $this->setContainerParameters($container);

        $configDir = $container->getParameter('ezplatform.graphql.schema.root_dir');

        $container->prependExtensionConfig('overblog_graphql', $this->getGraphQLConfig($configDir));
    }

    /**
     * Uses YamlConfigProvider to determinate what schema should be used.
     */
    private function getGraphQLConfig(string $configDir): array
    {
        return [
            'definitions' => [
                'config_validation' => '%kernel.debug%',
                'schema' => (new YamlSchemaProvider($configDir))->getSchemaConfiguration(),
            ],
        ];
    }

    private function setContainerParameters(ContainerBuilder $container): void
    {
        $rootDir = rtrim($container->getParameter('kernel.root_dir'), '/');
        $appSchemaDir = $rootDir . self::APP_SCHEMA_DIR_RELATIVE_PATH;
        $eZPlatformSchemaDir = $appSchemaDir . self::EZPLATFORM_SCHEMA_DIR_RELATIVE_PATH;
        $packageRootDir = $rootDir . self::PACKAGE_DIR_RELATIVE_PATH;
        $fieldsDefinitionFile = $packageRootDir . self::PACKAGE_SCHEMA_DIR_RELATIVE_PATH . \DIRECTORY_SEPARATOR . self::FIELS_DEFINITION_FILE_NAME;

        $container->setParameter('ezplatform.graphql.schema.root_dir', $appSchemaDir);
        $container->setParameter('ezplatform.graphql.schema.ezplatform_dir', $eZPlatformSchemaDir);
        $container->setParameter('ezplatform.graphql.schema.fields_definition_file', $fieldsDefinitionFile);
        $container->setParameter('ezplatform.graphql.package.root_dir', $packageRootDir);
    }
}
