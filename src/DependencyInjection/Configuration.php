<?php

namespace Aws\Symfony\DependencyInjection;

use Aws;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $shouldValidateConfiguration;

    public function __construct($shouldValidateConfiguration)
    {
        $this->shouldValidateConfiguration = $shouldValidateConfiguration;
    }

    public function getConfigTreeBuilder()
    {
        if ($this->shouldValidateConfiguration) {
            return $this->getTreeBuilderWithValidation();
        }

        // Most recent versions of TreeBuilder have a constructor
        if (\method_exists(TreeBuilder::class, '__construct')) {
            $treeBuilder = new TreeBuilder('aws', 'variable');
        } else { // which is not the case for older versions
            $treeBuilder = new TreeBuilder;
            $treeBuilder->root('aws', 'variable');
        }

        return $treeBuilder;
    }

    private function getTreeBuilderWithValidation()
    {
        // Most recent versions of TreeBuilder have a constructor
        if (\method_exists(TreeBuilder::class, '__construct')) {
            $treeBuilder = new TreeBuilder('aws', 'array');
            $rootNode = $treeBuilder->getRootNode();
        } else { // which is not the case for older versions
            $treeBuilder = new TreeBuilder;
            $treeBuilder->root('aws', 'array');
            $rootNode = $treeBuilder->root('aws');
        }

        // Define TreeBuilder to allow config validation and merging
        $rootNode
            ->ignoreExtraKeys(false)
            ->children()
                ->variableNode('credentials')->end()
                ->variableNode('debug')->end()
                ->variableNode('stats')->end()
                ->scalarNode('endpoint')->end()
                ->variableNode('endpoint_discovery')->end()
                ->arrayNode('http')
                    ->children()
                        ->floatNode('connect_timeout')->end()
                        ->booleanNode('debug')->end()
                        ->booleanNode('decode_content')->end()
                        ->integerNode('delay')->end()
                        ->variableNode('expect')->end()
                        ->variableNode('proxy')->end()
                        ->scalarNode('sink')->end()
                        ->booleanNode('synchronous')->end()
                        ->booleanNode('stream')->end()
                        ->floatNode('timeout')->end()
                        ->scalarNode('verify')->end()
                    ->end()
                ->end()
                ->scalarNode('profile')->end()
                ->scalarNode('region')->end()
                ->integerNode('retries')->end()
                ->scalarNode('scheme')->end()
                ->scalarNode('service')->end()
                ->scalarNode('signature_version')->end()
                ->variableNode('ua_append')->end()
                ->variableNode('validate')->end()
                ->scalarNode('version')->end()
            ->end()
        ;

        //Setup config trees for each of the services
        foreach (array_column(Aws\manifest(), 'namespace') as $awsService) {
            $rootNode
                ->children()
                    ->arrayNode($awsService)
                        ->ignoreExtraKeys(false)
                        ->children()
                            ->variableNode('credentials')->end()
                            ->variableNode('debug')->end()
                            ->variableNode('stats')->end()
                            ->scalarNode('endpoint')->end()
                            ->variableNode('endpoint_discovery')->end()
                            ->arrayNode('http')
                                ->children()
                                    ->floatNode('connect_timeout')->end()
                                    ->booleanNode('debug')->end()
                                    ->booleanNode('decode_content')->end()
                                    ->integerNode('delay')->end()
                                    ->variableNode('expect')->end()
                                    ->variableNode('proxy')->end()
                                    ->scalarNode('sink')->end()
                                    ->booleanNode('synchronous')->end()
                                    ->booleanNode('stream')->end()
                                    ->floatNode('timeout')->end()
                                    ->scalarNode('verify')->end()
                                ->end()
                            ->end()
                            ->scalarNode('profile')->end()
                            ->scalarNode('region')->end()
                            ->integerNode('retries')->end()
                            ->scalarNode('scheme')->end()
                            ->scalarNode('service')->end()
                            ->scalarNode('signature_version')->end()
                            ->variableNode('ua_append')->end()
                            ->variableNode('validate')->end()
                            ->scalarNode('version')->end()
                        ->end()
                    ->end()
                ->end()
            ;
        }

        return $treeBuilder;
    }
}
