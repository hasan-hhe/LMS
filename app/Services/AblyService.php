<?php

namespace App\Services;

use Ably\AblyRest;
use Ably\Models\TokenRequest;

class AblyService
{
    public const EVENT_NOTIFICATION = 'notification';

    private ?AblyRest $client = null;

    public function isEnabled(): bool
    {
        return filled(config('services.ably.key'));
    }

    public function userChannel(int $userId): string
    {
        return 'user:'.$userId;
    }

    public function createUserTokenRequest(int $userId): array
    {
        $channel = $this->userChannel($userId);
        $tokenRequest = $this->client()->auth->createTokenRequest([
            'clientId' => (string) $userId,
            'ttl' => 60 * 60 * 1000,
            'capability' => json_encode([
                $channel => ['subscribe'],
            ], JSON_UNESCAPED_SLASHES),
        ]);

        return [
            'token_request' => $this->serializeTokenRequest($tokenRequest),
            'channel' => $channel,
            'event' => self::EVENT_NOTIFICATION,
        ];
    }

    public function publishUserNotification(int $userId, array $payload): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $this->client()->channels->get($this->userChannel($userId))->publish(
            self::EVENT_NOTIFICATION,
            $payload
        );
    }

    private function client(): AblyRest
    {
        return $this->client ??= new AblyRest([
            'key' => config('services.ably.key'),
        ]);
    }

    private function serializeTokenRequest(mixed $tokenRequest): array
    {
        if ($tokenRequest instanceof TokenRequest) {
            return [
                'keyName' => $tokenRequest->keyName,
                'ttl' => $tokenRequest->ttl !== null ? (int) $tokenRequest->ttl : null,
                'capability' => $tokenRequest->capability,
                'clientId' => $tokenRequest->clientId,
                'timestamp' => $tokenRequest->timestamp !== null ? (int) $tokenRequest->timestamp : null,
                'nonce' => $tokenRequest->nonce,
                'mac' => $tokenRequest->mac,
            ];
        }

        if (is_array($tokenRequest)) {
            return $tokenRequest;
        }

        return [
            'keyName' => $tokenRequest->keyName ?? null,
            'ttl' => isset($tokenRequest->ttl) ? (int) $tokenRequest->ttl : null,
            'capability' => $tokenRequest->capability ?? null,
            'clientId' => $tokenRequest->clientId ?? null,
            'timestamp' => isset($tokenRequest->timestamp) ? (int) $tokenRequest->timestamp : null,
            'nonce' => $tokenRequest->nonce ?? null,
            'mac' => $tokenRequest->mac ?? null,
        ];
    }
}
