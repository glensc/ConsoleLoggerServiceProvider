# ConsoleLoggerServiceProvider

This service provider makes it easy to show log messages from services in the console,
without having to inject an instance of `OutputInterface` into the services. This
requires version >=2.4 of Symfony Components. More info about the change is at the
[Symfony Blog](http://symfony.com/blog/new-in-symfony-2-4-show-logs-in-console).

In your console application, you can now do something like this:

```php
use Symfony\Component\Console\Application;

$app = require 'app.php';
$console = new Application('My Console Application', '1.0');
// You should only register this service provider when running commands
$app->register(new \glen\ConsoleLoggerServiceProvider());

$console->addCommands(
    array(
    //...
    )
);

$console->run($app['console.input'], $app['console.output']);
```

You will still use the normal `OutputInterface` instance for command feedback
in your commands, but you will now also get output from anything your services
are logging.

The console logger overrides the default `monolog.handler` in order to allow setting
a custom log file. If defined, it will use `monolog.console_logfile`, and if not, it
will fall back to `monolog.logfile`.

The minimum logging level at which this handler will be triggered depends on the
verbosity setting of the console output. The default mapping is:
 - `OutputInterface::VERBOSITY_NORMAL` will show all `WARNING` and higher logs
 - `OutputInterface::VERBOSITY_VERBOSE` (`-v`) will show all `NOTICE` and higher logs
 - `OutputInterface::VERBOSITY_VERY_VERBOSE` (`-vv`) will show all `INFO` and higher logs
 - `OutputInterface::VERBOSITY_DEBUG` (`-vvv`) will show all DEBUG and higher logs, i.e. all logs

This mapping can be customized with the `logger.console_logger.handler.verbosity_level_map` constructor parameter:

```php
$app->register(new ConsoleLoggerServiceProvider(), [
    'logger.console_logger.handler.verbosity_level_map' => array(
        OutputInterface::VERBOSITY_QUIET => Logger::ERROR,
        OutputInterface::VERBOSITY_NORMAL => Logger::INFO,
        OutputInterface::VERBOSITY_VERBOSE => Logger::NOTICE,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
        OutputInterface::VERBOSITY_DEBUG => Logger::DEBUG,
    ),
]);
```
