<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\ModelQuery;
use App\Events\Models\User\UserCreated;
use App\Events\Models\User\UserCreating;
use App\Events\Models\User\UserDeleted;
use App\Events\Models\User\UserDeleting;
use App\Events\Models\User\UserUpdated;
use App\Events\Models\User\UserUpdating;
use App\Models\Finance\Account;
use App\Models\Traits\HasProjectResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Tags\HasTags;

class User extends Authenticatable implements ModelQuery, Taggable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasProjectResource;
    use HasRoles;
    use HasTags;
    use HasTeams;
    use LogsActivity;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public $dispatchesEvents = [
        'created' => UserCreated::class,
        'creating' => UserCreating::class,
        'deleting' => UserDeleting::class,
        'deleted' => UserDeleted::class,
        'updating' => UserUpdating::class,
        'updated' => UserUpdated::class,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->useLogName('user')
            ->logOnlyDirty();
    }

    public function codes()
    {
        return $this->hasMany(ShortCode::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class);
    }

    public function domains()
    {
        return $this->hasManyThrough(Domain::class, Credential::class);
    }

    public function accounts()
    {
        return $this->hasManyThrough(Account::class, Credential::class);
    }

    public function messages()
    {
        return $this->hasManyThrough(Message::class, Credential::class)->orderByDesc('originated_at');
    }

    public function externalRssFeeds()
    {
        return $this->morphMany(ExternalRssFeed::class, 'owner');
    }

    public function person()
    {
        return Person::whereJsonContains('emails', $this->email)
            // for now, this is fine, my email base does support this idea, but I know if someone/
            // wanted to be malicious they could take advantage of this.
            ->first();
    }
}
