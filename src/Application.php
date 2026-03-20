<?php

declare(strict_types=1);

namespace WpPluginInsights\RunnerDummy;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class Application
{
    public function run(): void
    {
        $config = Config::fromEnvironment();

        $connection = new AMQPStreamConnection(
            $config->rabbitMqHost,
            $config->rabbitMqPort,
            $config->rabbitMqUser,
            $config->rabbitMqPassword,
            $config->rabbitMqVhost
        );

        $channel = $connection->channel();

        $channel->queue_declare($config->inputQueue, false, true, false, false);
        $channel->exchange_declare($config->reportExchange, 'direct', false, true, false);

        $runner = new Runner($channel, $config);
        $runner->consume();

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
