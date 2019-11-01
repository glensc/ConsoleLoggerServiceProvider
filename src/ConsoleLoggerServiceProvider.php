<?php

namespace glen\ConsoleLoggerServiceProvider;

use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsoleLoggerServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['console.output'] = function () {
            return new ConsoleOutput();
        };

        $app['console.input'] = function () {
            return new ArgvInput();
        };

        $app['logger.console_logger.handler.bubble'] = true;
        $app['logger.console_logger.handler.verbosity_level_map'] = array();
        $app['logger.console_logger.handler'] = function ($app) {
            $consoleHandler = new ConsoleHandler(
                $app['console.output'],
                $app['logger.console_logger.handler.bubble'],
                $app['logger.console_logger.handler.verbosity_level_map']
            );
            $consoleHandler->setFormatter($app['logger.console_logger.formatter']);

            return $consoleHandler;
        };

        $app['logger.console_logger.formatter.options'] = function ($app) {
            $options = array();

            $keys = array(
                'format',
                'date_format',
                'colors',
                'multiline',
                'ignore_empty_context_and_extra',
            );
            foreach ($keys as $key) {
                if (isset($app["logger.console_logger.formatter.$key"])) {
                    $options[$key] = $app["logger.console_logger.formatter.$key"];
                }
            }

            return $options;
        };

        $app['logger.console_logger.formatter'] = function ($app) {
            return new ConsoleFormatter($app['logger.console_logger.formatter.options']);
        };

        $app->extend('monolog', function (Logger $monolog, Container $app) {
            $monolog->pushHandler($app['logger.console_logger.handler']);

            return $monolog;
        });
    }
}