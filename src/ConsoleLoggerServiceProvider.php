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
            $logfile = $app->offsetExists('monolog.console_logfile')
                ? $app['monolog.console_logfile']
                : $app['monolog.logfile'];
            return new StreamHandler($logfile, $app['monolog.level']);
        };

        $app['logger.console_format'] = "%datetime% %start_tag%%level_name%%end_tag% <comment>[%channel%]</> %message%%context%%extra%\n";
        $app['logger.console_date_format'] = "H:i:s";

        $app->extend('monolog', function (Logger $monolog, Container $app) {
            $consoleHandler = new ConsoleHandler($app['console.output']);
            $consoleHandler->setFormatter(new ConsoleFormatter(array(
                'format' => $app['logger.console_format'],
                'date_format' => $app['logger.console_date_format'],
            )));
            $monolog->pushHandler($consoleHandler);

            return $monolog;
        });
    }
}
