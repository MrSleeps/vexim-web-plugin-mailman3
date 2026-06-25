<?php

namespace VEximweb\Plugin\VEximMailman3;

use Filament\Contracts\Plugin;
use Filament\Panel;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource;

class VEximMailman3Plugin implements Plugin
{
    public function getId(): string
    {
        return 'vexim-mailman3';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MailmanListResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
