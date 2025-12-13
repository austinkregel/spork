<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Spork\SyncDataFromCredential;
use App\Contracts\ModelQuery;
use App\Events\Models\Credential\CredentialCreated;
use App\Events\Models\Credential\CredentialCreating;
use App\Events\Models\Credential\CredentialDeleted;
use App\Events\Models\Credential\CredentialDeleting;
use App\Events\Models\Credential\CredentialUpdated;
use App\Events\Models\Credential\CredentialUpdating;
use App\Models\Contracts\Owner;
use App\Models\Finance\Account;
use App\Models\Traits\HasProjectResource;
use App\Models\Traits\ScopeQSearch;
use App\Models\Traits\ScopeRelativeSearch;
use App\Observers\ApplyCredentialsObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([ApplyCredentialsObserver::class])]
class Credential extends Model implements Crud, ModelQuery, Owner
{
    use HasFactory;
    use HasProjectResource;
    use LogsActivity;
    use ScopeQSearch;
    use ScopeRelativeSearch;

    public const DIGITAL_OCEAN = 'digital-ocean';

    public const CLOUDFLARE = 'cloudflare';

    public const TYPE_MATRIX = 'matrix';

    public const NAMECHEAP = 'namecheap';

    public const ENOM = 'enom';

    public const OVH_CLOUD = 'ovhcloud';

    public const VULTR = 'vultr';

    public const LINODE = 'linode';

    public const GO_DADDY = 'godaddy';

    public const GOOGLE_DOMAINS = 'google-domains';

    public const AWS_ROUTE_53 = 'aws-route53';

    public const GITHUB_SOURCE = 'github';

    public const IMAP = 'imap';

    public const TYPE_SERVER = 'server';

    public const TYPE_CRM = 'crm';

    public const TYPE_DOMAIN = 'domain';

    public const TYPE_REGISTRAR = 'registrar';

    public const TYPE_DEVELOPMENT = 'development';

    public const TYPE_SOURCE = 'source';

    public const TYPE_FINANCE = 'finance';

    public const TYPE_SSH = 'ssh';

    public const TYPE_EMAIL = 'email';

    public const MONICA = 'monica';

    public const ALL_DOMAIN_PROVIDERS = [
        self::DIGITAL_OCEAN,
        self::CLOUDFLARE,
        self::OVH_CLOUD,
        self::VULTR,
        self::LINODE,
        self::GO_DADDY,
        self::GOOGLE_DOMAINS,
        self::AWS_ROUTE_53,
        self::TYPE_EMAIL,
    ];

    public const ALL_CRM_PROVIDERS = [
        self::MONICA,
    ];

    public const ALL_SERVER_PROVIDERS = [self::DIGITAL_OCEAN, self::OVH_CLOUD, self::VULTR, self::LINODE, self::IMAP];

    public const ALL_REGISTRAR_PROVIDERS = [
        self::OVH_CLOUD,
        self::CLOUDFLARE,
        self::GOOGLE_DOMAINS,
        self::NAMECHEAP,
        self::ENOM,
        self::AWS_ROUTE_53,
        self::GO_DADDY,
    ];

    public const ALL_SOURCE_PROVIDERS = [self::GITHUB_SOURCE];

    public $guarded = ['secret_fingerprint'];

    public $hidden = ['api_key', 'secret_key', 'access_token', 'refresh_token', 'secret_fingerprint'];

    public $fillable = [
        'name',
        'type',
        'user_id',
        'service',
        'api_key',
        'secret_key',
        'access_token',
        'refresh_token',
        'settings',
        'enabled_on',
    ];

    public $dispatchesEvents = [
        'created' => CredentialCreated::class,
        'creating' => CredentialCreating::class,
        'deleting' => CredentialDeleting::class,
        'deleted' => CredentialDeleted::class,
        'updating' => CredentialUpdating::class,
        'updated' => CredentialUpdated::class,
    ];

    public $actions = [SyncDataFromCredential::class];

    protected static function booted(): void
    {
        static::saving(function (self $credential): void {
            $credential->secret_fingerprint = self::makeSecretFingerprint(
                api_key: $credential->api_key,
                secret_key: $credential->secret_key,
                access_token: $credential->access_token,
                refresh_token: $credential->refresh_token,
                settings: $credential->settings ?? null,
            );
        });
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function makeSecretFingerprint(
        ?string $api_key,
        ?string $secret_key,
        ?string $access_token,
        ?string $refresh_token,
        ?array $settings,
    ): ?string {
        // Treat "no settings" and "empty settings" equivalently so that
        // credentials created with no settings and validated with an empty
        // settings array share the same fingerprint.
        $normalizedSettings = self::normalizeSettings($settings ?? []);

        if ($normalizedSettings === []) {
            $normalizedSettings = null;
        }

        $payload = [
            'api_key' => $api_key,
            'secret_key' => $secret_key,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
            'settings' => $normalizedSettings,
        ];

        $hasNonEmptySecret = collect($payload)
            ->map(function ($value) {
                if (is_array($value)) {
                    return collect($value)
                        ->flatten()
                        ->filter(fn ($v) => ! is_null($v) && $v !== '')
                        ->isNotEmpty();
                }

                return ! is_null($value) && $value !== '';
            })
            ->contains(true);

        if (! $hasNonEmptySecret) {
            return null;
        }

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Normalize settings for stable fingerprinting.
     */
    private static function normalizeSettings(?array $settings): ?array
    {
        if ($settings === null) {
            return null;
        }

        $normalize = function (array $value) use (&$normalize): array {
            ksort($value);

            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $value[$key] = $normalize($item);
                }
            }

            return $value;
        };

        return $normalize($settings);
    }

    public function getPublicKey(): string
    {
        $publicKeyFile = $this->settings['pub_key_file'];

        if (! file_exists($publicKeyFile)) {
            file_put_contents($publicKeyFile, $this->settings['pub_key'] ?? '');
            chmod($publicKeyFile, 0600);
        }

        return $this->settings['pub_key'] ?? '';
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
        return empty($this->settings['pass_key'] ?? '') ? '' : decrypt($this->settings['pass_key'] ?? '');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'service', 'enabled_on'])
            ->useLogName('credential')
            ->logOnlyDirty();
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
}
