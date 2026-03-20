<?php

declare(strict_types=1);

namespace WpPluginInsights\RunnerDummy;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class Runner
{
    private readonly ReportPublisher $reportPublisher;

    public function __construct(
        private readonly AMQPChannel $channel,
        private readonly Config $config
    ) {
        $this->reportPublisher = new ReportPublisher($channel, $config);
    }

    public function consume(): void
    {
        $this->channel->basic_qos(null, 1, null);

        $this->channel->basic_consume(
            $this->config->inputQueue,
            '',
            false,
            false,
            false,
            false,
            fn (AMQPMessage $message) => $this->handleMessage($message)
        );
    }

    private function handleMessage(AMQPMessage $message): void
    {
        $receivedAt = gmdate(DATE_ATOM);

        try {
            $job = $this->parseJob($message->getBody());
            $report = $this->buildDummyReport($job);

            $this->reportPublisher->publish(
                $job,
                $report,
                $receivedAt,
                gmdate(DATE_ATOM)
            );

            $message->ack();
        } catch (InvalidArgumentException $exception) {
            fwrite(STDERR, sprintf("[runner] rejecting invalid job: %s\n", $exception->getMessage()));
            $message->reject(false);
        } catch (Throwable $exception) {
            fwrite(STDERR, sprintf("[runner] runtime failure: %s\n", $exception->getMessage()));
            $message->nack(false, true);
        }
    }

    private function parseJob(string $body): Job
    {
        try {
            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Message body is not valid JSON.', previous: $exception);
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('Message body must decode to a JSON object.');
        }

        return Job::fromArray($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDummyReport(Job $job): array
    {
        return [
            'message' => 'Dummy runner executed successfully',
            'plugin' => $job->plugin,
            'src_exists' => is_dir($job->src),
        ];
    }
}
