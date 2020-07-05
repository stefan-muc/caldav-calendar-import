<?php declare(strict_types=1);
    /*
    * This file adds TRACE level to the Monolog package.
    */

    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;
    use Monolog\Formatter\LineFormatter;

    class MyLogger extends Logger
    {
        /**
         * Very detailed debug information
         */
        public const TRACE = 50;

        /**
         * @param string             $name       The logging channel, a simple descriptive name that is attached to all log records
         * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
         * @param callable[]         $processors Optional array of processors
         * @param DateTimeZone|null  $timezone   Optional timezone, if not provided date_default_timezone_get() will be used
         */
        public function __construct(string $name, array $handlers = [], array $processors = [], ?DateTimeZone $timezone = null)
        {
            parent::__construct($name, $handlers, $processors, $timezone);

            self::$levels[static::TRACE] = 'TRACE';
        }

        /**
         * Adds a log record at the TRACE level.
         *
         * This method allows for compatibility with common interfaces.
         *
         * @param string $message The log message
         * @param array  $context The log context
         */
        public function trace($message, array $context = []): void
        {
            $this->addRecord(static::TRACE, (string) $message, $context);
        }
    }

?>