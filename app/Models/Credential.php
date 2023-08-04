<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Models\Traits\HasProjectResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model implements ModelQuery
{
    use HasFactory, HasProjectResource;

    public $guarded = [];

    public const DIGITAL_OCEAN = 'digital-ocean';

    public const CLOUDFLARE = 'cloudflare';

    public const NAMECHEAP = 'namecheap';

    public const OVH_CLOUD = 'ovhcloud';

    public const VULTR = 'vultr';

    public const LINODE = 'linode';

    public const GO_DADDY = 'godaddy';

    public const GOOGLE_DOMAINS = 'google-domains';

    public const AWS_ROUTE_53 = 'aws-route53';

    public const GITHUB_SOURCE = 'github';

    public const FORGE_DEVELOPMENT = 'forge';

    public const TYPE_SERVER = 'server';

    public const TYPE_DOMAIN = 'domain';

    public const TYPE_REGISTRAR = 'registrar';

    public const TYPE_DEVELOPMENT = 'development';

    public const TYPE_SOURCE = 'source';

    public const TYPE_SSH = 'ssh';

    public const ALL_DOMAIN_PROVIDERS = [
        self::DIGITAL_OCEAN,
        self::CLOUDFLARE,
        self::OVH_CLOUD,
        self::VULTR,
        self::LINODE,
        self::GO_DADDY,
        self::GOOGLE_DOMAINS,
        self::AWS_ROUTE_53,
    ];

    public const ALL_SERVER_PROVIDERS = [
        self::DIGITAL_OCEAN,
        self::OVH_CLOUD,
        self::VULTR,
        self::LINODE,
    ];

    public const ALL_REGISTRAR_PROVIDERS = [
        self::OVH_CLOUD,
        self::CLOUDFLARE,
        self::GOOGLE_DOMAINS,
        self::NAMECHEAP,
        self::AWS_ROUTE_53,
        self::GO_DADDY,
    ];

    public const ALL_DEVELOPMENT_PROVIDERS = [
        self::FORGE_DEVELOPMENT,
    ];

    public const ALL_SOURCE_PROVIDERS = [
        self::GITHUB_SOURCE,
    ];

    public $hidden = [
        'api_key',
        'access_token',
        'refresh_token',
        'user_id'
    ];

    public $casts = [
        'settings' => 'json',
    ];

    public $fillable = [
        'name',
        'user_id',
        'type',
        'service',
        'api_key',
        'secret_key',
        'access_token',
        'refresh_token',
        'settings',
        'enabled_on',
    ];

    public static function booted(){
      parent::booted();
        static::creating(function ($credential) {
            $credential->user_id = auth()->id() ?? 1;
        });
    }

    public function getPublicKey(): string
    {
        $publicKeyFile = $this->settings['pub_key_file'];

        if (! file_exists($publicKeyFile)) {
            file_put_contents($publicKeyFile, $this->settings['private_key'] ?? '');
            chmod($publicKeyFile, 0600);
        }
        return $publicKeyFile;
    }

    public function getPrivateKey(): string
    {
        $privateKeyFile = $this->settings['private_key_file'];

        if (! file_exists($privateKeyFile)) {
            file_put_contents($privateKeyFile, $this->settings['private_key'] ?? '');
            chmod($privateKeyFile, 0600);
        }

        return $privateKeyFile;
    }

    public function getPasskey(): string
    {
        return decrypt($this->settings['pass_key'] ?? '');
    }
}
