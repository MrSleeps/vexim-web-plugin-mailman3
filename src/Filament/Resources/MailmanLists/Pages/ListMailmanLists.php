<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use VEximweb\Plugin\VEximMailman3\Facades\VEximMailman3;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource;
use VEximweb\Plugin\VEximMailman3\Models\MailmanList;

class ListMailmanLists extends ListRecords
{
    protected static string $resource = MailmanListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('syncAllWithMailman')
                ->label('Sync All with Mailman')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->syncAllLists();
                })
                ->requiresConfirmation()
                ->modalDescription('This will check all local lists against Mailman 3 and create any missing ones.'),
        ];
    }

    protected function syncAllLists(): void
    {
        $lists = MailmanList::all();
        $created = 0;
        $existing = 0;
        $errors = 0;

        try {
            $mailmanLists = VEximMailman3::lists();
            $existingListIdentifiers = collect($mailmanLists)->map(function ($mailmanList) {
                return $mailmanList['fqdn_listname'] ??
                       $mailmanList['list_id'] ??
                       $mailmanList['name'] ??
                       null;
            })->filter()->values()->toArray();

            \Log::info('Existing Mailman lists', ['lists' => $existingListIdentifiers]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch existing lists from Mailman', ['error' => $e->getMessage()]);
            $existingListIdentifiers = [];
        }

        foreach ($lists as $list) {
            try {
                // Check if list exists using fqdn_listname format (listname@domain.com)
                $listExists = in_array($list->list_email, $existingListIdentifiers);

                // Also check if list_id format might be used (domain.com.listname)
                if (! $listExists) {
                    // Convert email format to potential list_id format
                    $parts = explode('@', $list->list_email);
                    if (count($parts) === 2) {
                        $potentialListId = $parts[1] . '.' . $parts[0];
                        $listExists = in_array($potentialListId, $existingListIdentifiers);
                    }
                }

                if (! $listExists) {
                    \Log::info('Creating list in Mailman', ['list' => $list->list_email]);
                    $created_success = VEximMailman3::create_list($list->list_email);
                    if ($created_success) {
                        $created++;
                        $list->update(['mailman_list_id' => $list->list_email]);
                        $mailmanLists = VEximMailman3::lists();
                        $existingListIdentifiers = collect($mailmanLists)->map(function ($mailmanList) {
                            return $mailmanList['fqdn_listname'] ??
                                   $mailmanList['list_id'] ??
                                   $mailmanList['name'] ??
                                   null;
                        })->filter()->values()->toArray();
                    } else {
                        $errors++;
                    }
                } else {
                    $existing++;
                    \Log::info('List already exists in Mailman', ['list' => $list->list_email]);
                }
            } catch (\Exception $e) {
                $errors++;
                \Log::error('Failed to sync list: ' . $list->list_email, ['error' => $e->getMessage()]);
            }
        }

        Notification::make()
            ->title('Sync Complete')
            ->body("{$created} created, {$existing} already existed, {$errors} errors.")
            ->success()
            ->send();
    }
}
