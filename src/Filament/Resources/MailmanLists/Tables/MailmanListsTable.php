<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages\ListSubscribers;

class MailmanListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain.domain')
                    ->searchable(),
                TextColumn::make('list_name')
                    ->searchable(),
                TextColumn::make('list_email')
                    ->searchable(),
                TextColumn::make('mailman_list_id')
                    ->searchable(),
                IconColumn::make('enabled')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('view_subscribers')
                    ->label('View Subscribers')
                    ->icon('heroicon-o-users')
                    ->color('primary')
                    ->url(fn ($record) => ListSubscribers::getUrl(
                        ['record' => $record],
                        panel: Filament::getCurrentPanel()?->getId() ?? 'vexim'
                    )),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
