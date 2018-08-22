<?php

namespace Super\SuperEmailBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;

class SuperEmailExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $config = array();
        foreach ($configs as $subConfig) {
            $config = array_merge($config, $subConfig);
        }
        foreach ($config['sender'] as $alias => $options) {
            $this->registerSender($container, $alias, $options);
        }
        foreach ($config['message'] as $alias => $options) {
            $this->registerConfig($container, $alias, $options);
            $this->registerMessage($container, $alias, array_keys($config['sender']));
        }
        if (!empty($config['locale'])) {
            $this->registerLocaleListener($container, $config['locale']);
        }
    }

    protected function registerSender(ContainerBuilder $container, $alias, $options)
    {
        $optionId = sprintf('super_email.%s.sender', $alias);
        $optionDef = new Definition($container->getParameter('super_email.sender.class'));
        $optionDef->setPublic(true);
        $optionDef->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
        $optionDef->addMethodCall(
            'setPrimaryMailer',
            [new Reference(!empty($options['mailer']) ? $options['mailer'] : 'mailer')]
        );
        if (!empty($options['mailer_fallback'])) {
            $optionDef->addMethodCall('setFallbackMailer', [new Reference($options['mailer_fallback'])]);
        }
        $container->setDefinition($optionId, $optionDef);
    }

    protected function registerConfig(ContainerBuilder $container, $alias, $options)
    {
        $optionId = sprintf('super_email.%s.config', $alias);
        $optionDef = new Definition($container->getParameter('super_email.config.class'));
        // Dependency references
        $optionDef->setPublic(true);
        $optionDef->addMethodCall('setTemplateEngine', [new Reference('templating')]);
        $optionDef->addMethodCall('setTemplateLocator', [new Reference('templating.locator')]);
        $optionDef->addMethodCall('setTemplateNameParser', [new Reference('templating.name_parser')]);
        $optionDef->addMethodCall('setCachePath', [$container->getParameter('kernel.cache_dir')]);
        // Options
        $optionDef->addMethodCall('setTemplatePath', [$options['template_path']]);
        if (!empty($options['cache_inlined_css'])) {
            $optionDef->addMethodCall('setCacheInlinedCSS', [$options['cache_inlined_css']]);
        }
        if (!empty($options['css_file'])) {
            $optionDef->addMethodCall('setCssFile', [$options['css_file']]);
        }
        if (!empty($options['from'])) {
            $optionDef->addMethodCall('setFrom', [$options['from']]);
        }
        if (!empty($options['reply_to'])) {
            $optionDef->addMethodCall('setReplyTo', [$options['reply_to']]);
        }
        if (!empty($options['domain'])) {
            $optionDef->addMethodCall('setDomain', [$options['domain']]);
        }
        if (!empty($options['embed_images'])) {
            $optionDef->addMethodCall(
                'setEmbedImages',
                [$options['embed_images']['url'], $options['embed_images']['path']]
            );
        }
        $container->setDefinition($optionId, $optionDef);
    }

    protected function registerMessage(ContainerBuilder $container, $alias, $senders)
    {
        $optionId = sprintf('super_email.%s.message', $alias);
        $optionDef = new Definition($container->getParameter('super_email.message.class'));
        $optionDef->setShared(false);
        $optionDef->setPublic(true);
        $optionDef->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
        $optionDef->addMethodCall('setConfig', [new Reference(sprintf('super_email.%s.config', $alias))]);
        foreach ($senders as $sender) {
            $optionDef->addMethodCall('addSender', [$sender, new Reference(sprintf('super_email.%s.sender', $sender))]);
        }
        $container->setDefinition($optionId, $optionDef);
        if ($alias === 'default') {
            $container->setAlias('super_email.message', $optionId);
        }
    }

    protected function registerLocaleListener(ContainerBuilder $container, $config)
    {
        $container->setParameter('super_email.locale_config', $config);
        $optionDef = new Definition($container->getParameter('super_email.locale_listener.class'));
        $optionDef->setPublic(true);
        $optionDef->addArgument(new Reference('router.request_context'));
        $optionDef->addArgument(new Reference('translator'));
        $optionDef->addArgument(
            new Reference(
                'stof_doctrine_extensions.listener.translatable',
                ContainerInterface::NULL_ON_INVALID_REFERENCE
            )
        );
        $optionDef->addMethodCall('setContainer', [new Reference('service_container')]);
        $optionDef->addMethodCall('setLocaleConfig', [$container->getParameter('super_email.locale_config')]);
        $optionDef->addTag(
            'kernel.event_listener',
            ['event' => 'super_email.before_render_html', 'method' => 'onBeforeRenderHTML', 'priority' => 10]
        );
        $optionDef->addTag(
            'kernel.event_listener',
            ['event' => 'super_email.after_render_html', 'method' => 'onAfterRenderHTML', 'priority' => -10]
        );
        $container->setDefinition('super_email.locale.listener', $optionDef);
    }
}
