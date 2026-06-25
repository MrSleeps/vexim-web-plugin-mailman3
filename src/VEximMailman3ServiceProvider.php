<?php

namespace VEximweb\Plugin\VEximMailman3;

use VEximweb\Plugin\VEximMailman3\Repositories\Interfaces\MailmanListRepositoryInterface;
use VEximweb\Plugin\VEximMailman3\Repositories\MailmanListRepository;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use VEximweb\Plugin\VEximMailman3\Commands\TestMailmanConnection;
use VEximweb\Plugin\VEximMailman3\Commands\VEximMailman3Command;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource;
use VEximweb\Plugin\VEximMailman3\Testing\TestsVEximMailman3;

class VEximMailman3ServiceProvider extends PackageServiceProvider
{
    public static string $name = 'vexim-mailman3';

    public static string $viewNamespace = 'vexim-mailman3';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->askToStarRepoOnGitHub('mrsleeps/vexim-mailman3');
            });

        if (file_exists($package->basePath("/../config/{$package->shortName()}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        // Bind Mailman API client
        $this->app->bind(MailmanInterface::class, function ($app) {
            return new Mailman(
                config('vexim-mailman3.host'),
                config('vexim-mailman3.port'),
                config('vexim-mailman3.admin_user'),
                config('vexim-mailman3.admin_pass')
            );
        });

        // Bind the repository interface to its implementation
        // Note: This assumes the host app has bound this interface
        $this->app->bind(
            MailmanListRepositoryInterface::class,
            MailmanListRepository::class
        );

        // Register your main plugin class
        $this->app->singleton(VEximMailman3::class, function ($app) {
            return new VEximMailman3(
                $app->make(MailmanInterface::class),
                $app->make(MailmanListRepositoryInterface::class)
            );
        });
        
        Panel::configureUsing(function (Panel $panel) {
            $panel->plugin(VEximMailman3Plugin::make());
        });        
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/vexim-mailman3/{$file->getFilename()}"),
                ], 'vexim-mailman3-stubs');
            }
        }

        if (class_exists(Panel::class)) {
            Filament::registerResources([
                MailmanListResource::class,
            ]);
        }

        // Testing
        Testable::mixin(new TestsVEximMailman3);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'mrsleeps/vexim-mailman3';
    }

    protected function getAssets(): array
    {
        return [];
    }

    protected function getCommands(): array
    {
        return [
            TestMailmanConnection::class,
        ];
    }

    protected function getIcons(): array
    {
        return [];
    }

    protected function getRoutes(): array
    {
        return [];
    }

    protected function getScriptData(): array
    {
        return [];
    }

    protected function getMigrations(): array
    {
        return []; // No migrations
    }
}
