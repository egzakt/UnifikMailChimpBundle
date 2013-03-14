<?php

namespace Egzakt\MailChimpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('egzakt_mail_chimp');

        $rootNode
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->defaultValue('Egzakt\MailChimpBundle\Lib\MailChimpApi')
                        ->end()
                    ->end()
                ->end()

                ->scalarNode('api_key')
                    ->isRequired()
                ->end()

                ->booleanNode('secure')
                    ->defaultFalse()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
