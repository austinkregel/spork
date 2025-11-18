<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\CredentialRepositoryContract;
use App\Contracts\Repositories\MatrixClientSyncRepositoryContract;
use App\Contracts\Repositories\ProjectRepositoryContract;
use App\Contracts\Services\CloudflareDomainServiceContract;
use App\Contracts\Services\CloudflareRegistrarServiceContract;
use App\Contracts\Services\ConditionServiceContract;
use App\Contracts\Services\Development\DescribeTableServiceContract;
use App\Contracts\Services\Development\ForgeDevelopmentServiceContract;
use App\Contracts\Services\DigitalOceanServiceContract;
use App\Contracts\Services\Documents\HtmlJsonDataLinkingServiceContract;
use App\Contracts\Services\Documents\PdfParserServiceContract;
use App\Contracts\Services\Documents\PdfReaderServiceContract;
use App\Contracts\Services\GeocodingServiceContract;
use App\Contracts\Services\HttpServiceContract;
use App\Contracts\Services\ImapServiceContract;
use App\Contracts\Services\JiraServiceContract;
use App\Contracts\Services\Messaging\ImapFactoryServiceContract;
use App\Contracts\Services\MustacheTemplateService;
use App\Contracts\Services\NamecheapServiceContract;
use App\Contracts\Services\News\NewsServiceContract;
use App\Contracts\Services\News\RssServiceContract;
use App\Contracts\Services\PlaidServiceContract;
use App\Contracts\Services\ServerServiceContract;
use App\Contracts\Services\SshKeyGeneratorServiceContract;
use App\Contracts\Services\SshServiceContract;
use App\Contracts\Services\WeatherServiceContract;
use App\Operations\Operator;
use App\Repositories\CredentialRepository;
use App\Repositories\MatrixClientSyncRepository;
use App\Repositories\ProjectRepository;
use App\Services\Code;
use App\Services\ConditionService;
use App\Services\Development\DescribeTableService;
use App\Services\Development\ForgeDevelopmentService;
use App\Services\Documents\HtmlJsonDataLinkingService;
use App\Services\Documents\PdfParserService;
use App\Services\Documents\PdfReaderService;
use App\Services\Domain\CloudflareDomainService;
use App\Services\Finance\PlaidService;
use App\Services\Geocoding\GoogleMapsGeocodingService;
use App\Services\HttpService;
use App\Services\JiraService;
use App\Services\Messaging\ImapCredentialService;
use App\Services\Messaging\ImapFactoryService;
use App\Services\MustacheService;
use App\Services\News\NewsService;
use App\Services\News\RssFeedService;
use App\Services\Registrar\CloudflareRegistrarService;
use App\Services\Registrar\NamecheapService;
use App\Services\Server\DigitalOceanService;
use App\Services\Server\LibvirtService;
use App\Services\Server\OVHCloudService;
use App\Services\SshKeyGeneratorService;
use App\Services\SshService;
use App\Services\Weather\OpenWeatherService;
use App\Services\Weather\WeatherApiService;
use App\Services\Weather\WeatherGovApiService;
use App\Spork;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/-/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repositories
        $this->app->bind(CredentialRepositoryContract::class, CredentialRepository::class);
        $this->app->bind(ProjectRepositoryContract::class, ProjectRepository::class);
        $this->app->bind(MatrixClientSyncRepositoryContract::class, MatrixClientSyncRepository::class);

        // Services - News
        $this->app->bind(NewsServiceContract::class, NewsService::class);
        $this->app->bind(RssServiceContract::class, RssFeedService::class);

        // Services - Registrar
        $this->app->bind(NamecheapServiceContract::class, NamecheapService::class);
        $this->app->bind(CloudflareRegistrarServiceContract::class, CloudflareRegistrarService::class);

        // Services - Finance
        $this->app->bind(PlaidServiceContract::class, PlaidService::class);

        // Services - Messaging
        $this->app->bind(ImapServiceContract::class, ImapCredentialService::class);
        $this->app->bind(ImapFactoryServiceContract::class, ImapFactoryService::class);

        // Services - Weather
        $this->app->bind(WeatherServiceContract::class, OpenWeatherService::class);
        // Note: WeatherApiService and WeatherGovApiService also implement WeatherServiceContract
        // but OpenWeatherService is the default implementation

        // Services - Jira
        $this->app->bind(JiraServiceContract::class, JiraService::class);

        // Services - Documents
        $this->app->bind(PdfParserServiceContract::class, PdfParserService::class);
        $this->app->bind(PdfReaderServiceContract::class, PdfReaderService::class);
        $this->app->bind(HtmlJsonDataLinkingServiceContract::class, HtmlJsonDataLinkingService::class);

        // Services - Development
        $this->app->bind(DescribeTableServiceContract::class, DescribeTableService::class);
        // Note: ForgeDevelopmentService is instantiated with Credential, so it's not bound as singleton

        // Services - Domain
        $this->app->bind(CloudflareDomainServiceContract::class, CloudflareDomainService::class);

        // Services - Server
        $this->app->bind(DigitalOceanServiceContract::class, DigitalOceanService::class);
        $this->app->bind(ServerServiceContract::class, LibvirtService::class);
        // Note: OVHCloudService also implements ServerServiceContract

        // Services - Geocoding
        $this->app->bind(GeocodingServiceContract::class, GoogleMapsGeocodingService::class);

        // Services - HTTP
        $this->app->bind(HttpServiceContract::class, HttpService::class);

        // Services - SSH
        $this->app->bind(SshServiceContract::class, SshService::class);
        $this->app->bind(SshKeyGeneratorServiceContract::class, SshKeyGeneratorService::class);

        // Services - Condition
        $this->app->bind(ConditionServiceContract::class, ConditionService::class);

        // Services - Template
        $this->app->bind(MustacheTemplateService::class, MustacheService::class);

        // Other
        $this->app->alias(Operator::class, 'operator');
        $this->app->singleton(Spork::class, fn () => new Spork);
    }

    public function boot(): void
    {
        $this->bootRoute();
    }

    public function bootRoute(): void
    {
        Route::macro('domains', function (array $domains, $callback) {
            foreach ($domains as $domain) {
                Route::domain($domain)
                    ->name($domain)
                    ->group($callback);
            }

            return $this;
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        Route::bind('abstract_model', function ($tableName) {
            return collect(app(Filesystem::class)->allFiles(app_path('Models')))->filter(fn (SplFileInfo $file) => ! str_contains(strtolower($file->getRealPath()), 'trait'))->map(function (SplFileInfo $file) {
                $modelClass = ucfirst(str_replace('/', '\\', str_replace('.php', '', substr(str_replace(base_path(), '', $file->getRealPath()), 1))));

                return new $modelClass;
            })->filter(function ($model) use ($tableName) {
                return $model->getTable() === $tableName;
            })->first();
        });

        Route::bind('abstract_model_id', function ($value) {
            $class = request()->route('abstract_model');

            $model = new $class;

            return $model::find($value);
        });

        Route::bind('link', function ($value) {
            $model = Arr::first(array_values(array_filter(
                Code::instancesOf(Model::class)
                    ->getClasses(),
                function ($class) use ($value) {
                    try {
                        return (new $class)->getTable() === Str::slug($value, '_');
                    } catch (\Throwable $e) {
                        return false;
                    }
                })));

            abort_unless(isset($model), 404);

            return $model;
        });

    }
}
