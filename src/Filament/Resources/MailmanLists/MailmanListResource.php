<?php

// namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists;

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages\CreateMailmanList;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages\EditMailmanList;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages\ListMailmanLists;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages\ListSubscribers;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Schemas\MailmanListForm;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Tables\MailmanListsTable;
use VEximweb\Plugin\VEximMailman3\Models\MailmanList;

class MailmanListResource extends Resource
{
    protected static ?string $model = MailmanList::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'Mailing Lists';

    protected static ?string $navigationLabel = 'Mailman 3';

    protected static ?string $recordTitleAttribute = 'Mailman 3';

    public static function form(Schema $schema): Schema
    {
        return MailmanListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MailmanListsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailmanLists::route('/'),
            'create' => CreateMailmanList::route('/create'),
            'edit' => EditMailmanList::route('/{record}/edit'),
            'subscribers' => ListSubscribers::route('/{record}/subscribers'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
