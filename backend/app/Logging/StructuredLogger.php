<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class StructuredLogger
{
    /**
     * Create a custom Monolog instance.
     */
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('umap');
        
        $handler = new StreamHandler(
            storage_path('logs/app.log'),
            $config['level'] ?? 'info'
        );
        
        // Use JSON formatter for structured logging
        $handler->setFormatter(new JsonFormatter());
        
        $logger->pushHandler($handler);
        
        return $logger;
    }
}
