<?php

namespace Egzakt\MailChimpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class EgzaktMailChimpExtension extends Extension
{

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // API Class
        if (isset($config['api']['class'])) {
            $container->setParameter('egzakt_mail_chimp.api.class', $config['api']['class']);
        }

        // Other parameters
        foreach(array('api_key', 'secure') as $param) {
            if (isset($config[$param])) {
                $container->setParameter('egzakt_mail_chimp.' . $param, $config[$param]);
            }
        }
    }

}
