<?php

namespace glen\ConsoleLoggerServiceProvider;

use Monolog\Handler\StreamHandler;
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
     * @inheritdoc
     */
    public function register(Container $app)
    {
        $app['console.output'] = function () {
            return new ConsoleOutput();
        };

        $app['console.input'] = function () {
            return new ArgvInput();
        };

        $app['monolog.handler'] = function () use ($app) {
            $logfile = isset($app['monolog.console_logfile'])
                ? $app['monolog.console_logfile']
                : $app['monolog.logfile'];
            return new StreamHandler($logfile, $app['monolog.level']);
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

        $app['logger.console_logger.formatter.options'] = array();
        $app['logger.console_logger.formatter'] = function ($app) {
            return new ConsoleFormatter($app['logger.console_logger.formatter.options']);
        };

        $app->extend('monolog', function (Logger $monolog, Container $app) {
            $monolog->pushHandler($app['logger.console_logger.handler']);

            return $monolog;
        });
    }
}