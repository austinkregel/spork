<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InfrastructureProvisionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_credential_id',
        'dns_credential_id',
        'server_id',
        'domain_id',
        'status',
        'payload',
        'result',
        'error',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerCredential(): BelongsTo
    {
        return $this->belongsTo(Credential::class, 'provider_credential_id');
    }

    public function dnsCredential(): BelongsTo
    {
        return $this->belongsTo(Credential::class, 'dns_credential_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
