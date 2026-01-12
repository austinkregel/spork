<?php

declare(strict_types=1);

namespace App\Data\Matrix;

class MatrixSyncState
{
    protected array $devices = [];

    protected array $keys = [];

    protected ?string $default_key = null;

    protected ?array $master_key = null;

    protected ?array $self_sign_key = null;

    protected ?array $signing_user = null;

    protected ?array $megolm_backup = null;

    protected ?array $client = null;

    protected ?array $breadcrumbs = null;

    protected ?array $dms = null;

    protected ?array $notification_settings = null;

    public function mergeDevice(string $deviceId, array $context): void
    {
        if (! isset($this->devices[$deviceId])) {
            $this->devices[$deviceId] = [];
        }

        $this->devices[$deviceId] = array_merge($this->devices[$deviceId], $context);
    }

    public function storeRecentEmoji(array $emoji): void
    {
        $this->devices['recent_emoji'] = [
            'recent_emoji' => $emoji,
        ];
    }

    public function mergeKey(string $key, array $context): void
    {
        if (! isset($this->keys[$key])) {
            $this->keys[$key] = [];
        }

        $this->keys[$key] = array_merge($this->keys[$key], $context);
    }

    public function setDefaultKey(string $key): void
    {
        $this->default_key ??= $key;
    }

    public function setMasterKey(array $payload): void
    {
        $this->master_key ??= $payload;
    }

    public function mergeSelfSignKey(array $payload): void
    {
        $this->self_sign_key = array_merge($this->self_sign_key ?? [], $payload);
    }

    public function mergeSigningUser(array $payload): void
    {
        $this->signing_user = array_merge($this->signing_user ?? [], $payload);
    }

    public function mergeMegolmBackup(array $payload): void
    {
        $this->megolm_backup = array_merge($this->megolm_backup ?? [], $payload);
    }

    public function setClient(array $client): void
    {
        $this->client = $client;
    }

    public function setBreadcrumbs(array $breadcrumbs): void
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    public function setDms(array $dms): void
    {
        $this->dms = $dms;
    }

    public function devices(): array
    {
        return $this->devices;
    }

    public function keys(): array
    {
        return $this->keys;
    }

    public function defaultKey(): ?string
    {
        return $this->default_key;
    }

    public function masterKey(): ?array
    {
        return $this->master_key;
    }

    public function selfSignKey(): ?array
    {
        return $this->self_sign_key;
    }

    public function signingUser(): ?array
    {
        return $this->signing_user;
    }

    public function megolmBackup(): ?array
    {
        return $this->megolm_backup;
    }

    public function client(): ?array
    {
        return $this->client;
    }

    public function breadcrumbs(): ?array
    {
        return $this->breadcrumbs;
    }

    public function dms(): ?array
    {
        return $this->dms;
    }

    public function notificationSettings(): ?array
    {
        return $this->notification_settings;
    }
}
