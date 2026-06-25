<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages;

use VEximweb\Plugin\VEximMailman3\Models\MailmanList;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Tables\SubscribersTable;

class ListSubscribers extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'vexim-mailman3::filament.resources.mailman-list-resource.pages.list-subscribers';

    public MailmanList $record;

    public static function getResource(): string
    {
        return MailmanListResource::class;
    }

    public static function getRouteName(?Panel $panel = null): string
    {
        $panelId = $panel?->getId() ?? Filament::getCurrentPanel()?->getId() ?? 'vexim';

        return "filament.{$panelId}.resources.mailman-lists.subscribers";
    }

    public function table(Table $table): Table
    {
        return SubscribersTable::configure($table, $this->record->mailman_list_id);
    }

    public function getTitle(): string
    {
        return "Subscribers for {$this->record->list_email}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            MailmanListResource::getUrl('index') => 'Mailman Lists',
            $this->getTitle(),
        ];
    }

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
        ?string $configuration = null,
    ): string {
        return parent::getUrl(
            $parameters,
            $isAbsolute,
            $panel ?? Filament::getCurrentPanel()?->getId() ?? 'vexim',
            $tenant,
            $shouldGuessMissingParameters,
            $configuration,
        );
    }
}
