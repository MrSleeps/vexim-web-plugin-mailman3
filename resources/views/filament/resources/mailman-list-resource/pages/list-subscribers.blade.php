<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">
                    Subscribers for {{ $record->list_email }}
                </h2>
            </div>
            <x-filament::button href="{{ \VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource::getUrl('index') }}" tag="a">Back to lists</x-filament::button>            
        </div>
        
        {{ $this->table }}
    </div>
</x-filament-panels::page>