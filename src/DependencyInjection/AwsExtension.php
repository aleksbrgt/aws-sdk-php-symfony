<?php

namespace Aws\Symfony\DependencyInjection;

use Aws;
use Aws\AwsClient;
use Aws\Symfony\AwsBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

class AwsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yml');

        $configuration = $this->processConfiguration(
            new Configuration($this->shouldMergeConfiguration($configs)),
            $configs
        );

        // The mergeConfiguration key is only used to pick the appropriate configuration and should not be used in
        // the aws sdk configuration
        unset($configuration['mergeConfiguration']);

        $this->inflateServicesInConfig($configuration);

        $container
            ->getDefinition('aws_sdk')
            ->replaceArgument(0, $configuration + ['ua_append' => [
                'Symfony/' . Kernel::VERSION,
                'SYMOD/' . AwsBundle::VERSION,
            ]]);

        foreach (array_column(Aws\manifest(), 'namespace') as $awsService) {
            $serviceName = 'aws.' . strtolower($awsService);
            $serviceDefinition = $this->createServiceDefinition($awsService);
            $container->setDefinition($serviceName, $serviceDefinition);

            $container->setAlias($serviceDefinition->getClass(), $serviceName);
        }
    }

    private function shouldMergeConfiguration(array $configs)
    {
        // Old way of maintaining backwards compatibility, merge when the AWS_MERGE_CONFIG env var is set
        if (getenv('AWS_MERGE_CONFIG') ?: false) {
            return true;
        }

        // Merge if the mergeConfiguration key in the configuration exists and is set to true
        foreach ($configs as $config) {
            if (\array_key_exists('mergeConfiguration', $config)
                && true === $config['mergeConfiguration']
            ) {
                return true;
            }
        }

        return false;
    }

    private function createServiceDefinition($name)
    {
        $clientClass = "Aws\\{$name}\\{$name}Client";
        $serviceDefinition = new Definition(
            class_exists($clientClass) ? $clientClass : AwsClient::class
        );

        // Handle Symfony >= 2.6
        if (method_exists($serviceDefinition, 'setFactory')) {
            return $serviceDefinition->setFactory([
                new Reference('aws_sdk'),
                'createClient',
            ])->setArguments([$name]);
        }

        return $serviceDefinition
                ->setLazy(true)
                ->setFactoryService('aws_sdk')
                ->setFactoryMethod('createClient')
                ->setArguments([$name]);
    }

    private function inflateServicesInConfig(array &$config)
    {
        array_walk($config, function (&$value) {
            if (is_array($value)) {
                $this->inflateServicesInConfig($value);
            }

            if (is_string($value) && 0 === strpos($value, '@')) {
                // this is either a service reference or a string meant to
                // start with an '@' symbol. In any case, lop off the first '@'
                $value = substr($value, 1);
                if (0 !== strpos($value, '@')) {
                    // this is a service reference, not a string literal
                    $value = new Reference($value);
                }
            }
        });
    }
}
