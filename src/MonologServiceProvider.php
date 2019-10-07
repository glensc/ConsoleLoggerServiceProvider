<?php

namespace glen\ConsoleLoggerServiceProvider;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Bridge\Monolog\Handler\FingersCrossed\NotFoundActivationStrategy;
use Symfony\Bridge\Monolog\Processor\DebugProcessor;

/**
 * Monolog Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['logger'] = function () use ($app) {
            return $app['monolog'];
        };

        if ($bridge = class_exists('Symfony\Bridge\Monolog\Logger')) {
            if (isset($app['request_stack'])) {
                $app['monolog.not_found_activation_strategy'] = function () use ($app) {
                    $level = MonologServiceProvider::translateLevel($app['monolog.level']);

                    return new NotFoundActivationStrategy($app['request_stack'], array('^/'), $level);
                };
            }
        }

        $app['monolog.logger.class'] = $bridge ? 'Symfony\Bridge\Monolog\Logger' : 'Monolog\Logger';

        $app['monolog'] = function ($app) use ($bridge) {
            /** @var \Monolog\Logger $log */
            $log = new $app['monolog.logger.class']($app['monolog.name']);

            $handler = new Handler\GroupHandler($app['monolog.handlers']);
            if (isset($app['monolog.not_found_activation_strategy'])) {
                $handler = new Handler\FingersCrossedHandler($handler, $app['monolog.not_found_activation_strategy']);
            }

            $log->pushHandler($handler);

            // DebugProcessor appears in Symfony 3.2
            // https://github.com/symfony/monolog-bridge/blob/3.2/Processor/DebugProcessor.php
            if ($bridge && isset($app['debug']) && $app['debug'] && class_exists('\Symfony\Bridge\Monolog\Processor\DebugProcessor')) {
                $log->pushProcessor(new DebugProcessor());
            }

            return $log;
        };

        $app['monolog.formatter'] = function () {
            return new LineFormatter();
        };

        $app['monolog.handler'] = $defaultHandler = function () use ($app) {
            $level = MonologServiceProvider::translateLevel($app['monolog.level']);

            if (isset($app['monolog.logfile'])) {
                $handler = new Handler\StreamHandler($app['monolog.logfile'], $level, $app['monolog.bubble'], $app['monolog.permission']);
            } else {
                $handler = new Handler\ErrorLogHandler(Handler\ErrorLogHandler::OPERATING_SYSTEM, $level, $app['monolog.bubble']);
            }

            $handler->setFormatter($app['monolog.formatter']);

            return $handler;
        };

        $app['monolog.handlers'] = function () use ($app, $defaultHandler) {
            $handlers = array();

            // enables the default handler if a logfile was set or the monolog.handler service was redefined
            if ($app['monolog.logfile'] || $defaultHandler !== $app->raw('monolog.handler')) {
                $handlers[] = $app['monolog.handler'];
            }

            return $handlers;
        };

        $app['monolog.level'] = function () {
            return Logger::DEBUG;
        };

        $app['monolog.name'] = 'app';
        $app['monolog.bubble'] = true;
        $app['monolog.permission'] = null;
        $app['monolog.logfile'] = null;
    }

    public static function translateLevel($name)
    {
        // level is already translated to logger constant, return as-is
        if (is_int($name)) {
            return $name;
        }

        $psrLevel = Logger::toMonologLevel($name);

        if (is_int($psrLevel)) {
            return $psrLevel;
        }

        $levels = Logger::getLevels();
        $upper = strtoupper($name);

        if (!isset($levels[$upper])) {
            throw new \InvalidArgumentException("Provided logging level '$name' does not exist. Must be a valid monolog logging level.");
        }

        return $levels[$upper];
    }
}
