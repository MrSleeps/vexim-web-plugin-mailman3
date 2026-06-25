<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use VEximweb\Plugin\VEximMailman3\MailmanInterface;

class SubscribersTable
{
    public static function configure(Table $table, string $listId): Table
    {
        return $table
            ->records(function () use ($listId) {
                try {
                    $mailman = app(MailmanInterface::class);

                    return collect($mailman->members($listId));
                } catch (\Throwable $e) {
                    $message = $e->getMessage();

                    if ($e instanceof ClientException) {
                        $response = $e->getResponse();
                        $body = json_decode($response->getBody(), true);
                        $status = $response->getStatusCode();
                        $message = match ($status) {
                            404 => 'Mailing list not found in Mailman API.',
                            401, 403 => 'Authentication failed — check Mailman API credentials.',
                            default => "Mailman API error ({$status}): " . ($body['title'] ?? $e->getMessage()),
                        };
                    } elseif ($e instanceof ConnectException) {
                        $message = 'Could not connect to Mailman API — check the host and port in config.';
                    }

                    Notification::make()
                        ->title('Unable to load subscribers')
                        ->body($message)
                        ->danger()
                        ->persistent()
                        ->send();

                    return collect();
                }
            })
            ->headerActions([
                Action::make('subscribe')
                    ->label('Subscribe User')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading('Subscribe User to List')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->placeholder('user@example.com'),
                        TextInput::make('display_name')
                            ->label('Display Name')
                            ->required()
                            ->placeholder('John Doe'),
                        TextInput::make('confirm_email')
                            ->label('Confirm Email')
                            ->email()
                            ->required()
                            ->same('email')
                            ->placeholder('Confirm email address'),
                    ])
                    ->action(function (array $data, $livewire) use ($listId) {
                        try {
                            $mailman = app(MailmanInterface::class);
                            $success = $mailman->subscribe($listId, $data['display_name'], $data['email']);

                            if ($success) {
                                Notification::make()
                                    ->title('User subscribed successfully')
                                    ->body("{$data['display_name']} ({$data['email']}) has been subscribed.")
                                    ->success()
                                    ->send();

                                $livewire->resetTable();
                            } else {
                                Notification::make()
                                    ->title('Subscription failed')
                                    ->body('Unable to subscribe the user. Please try again.')
                                    ->danger()
                                    ->send();
                            }

                        } catch (ClientException $e) {
                            $status = $e->getResponse()->getStatusCode();
                            $message = match ($status) {
                                409 => 'This email is already subscribed to the list.',
                                400 => 'Invalid email address or display name.',
                                default => 'Failed to subscribe user: ' . $e->getMessage(),
                            };

                            Notification::make()
                                ->title('Subscription failed')
                                ->body($message)
                                ->danger()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Subscription failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->columns([
                TextColumn::make('member_id')
                    ->label('Member ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->default(fn ($record) => $record['display_name'] ?? $record['email'] ?? ''),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'member' => 'gray',
                        'moderator' => 'warning',
                        'owner' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('delivery_mode')
                    ->label('Delivery Mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'regular' => 'gray',
                        'digest' => 'info',
                        'plain_digest' => 'info',
                        'mime_digest' => 'info',
                        'summary_digest' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('bounce_score')
                    ->label('Bounce Score')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state >= 5 => 'danger',
                        $state >= 3 => 'warning',
                        $state > 0 => 'info',
                        default => 'success',
                    }),
                TextColumn::make('delivery_status')
                    ->label('Delivery Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enabled' => 'success',
                        'disabled' => 'danger',
                        'by_moderator' => 'warning',
                        'by_user' => 'warning',
                        'suspended' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading('Edit User Details')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('display_name')
                            ->label('Display Name')
                            ->required()
                            ->default(fn ($record) => $record['display_name'] ?? $record['email'] ?? ''),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->default(fn ($record) => $record['email'] ?? ''),
                        TextInput::make('confirm_email')
                            ->label('Confirm Email')
                            ->email()
                            ->required()
                            ->same('email')
                            ->default(fn ($record) => $record['email'] ?? ''),
                    ])
                    ->action(function (array $data, $record, $livewire) use ($listId) {
                        try {
                            $mailman = app(MailmanInterface::class);

                            $emailChanged = $data['email'] !== $record['email'];

                            if ($emailChanged) {
                                // Email changed: unsubscribe the old address and subscribe the new
                                // one. subscribe() will set the display name on the (new) user record.
                                $mailman->unsubscribe($listId, $record['email']);
                                $mailman->subscribe($listId, $data['display_name'], $data['email']);
                                $message = "Email changed from {$record['email']} to {$data['email']}";
                            } else {
                                // Email unchanged: update the display name in place.
                                // updateUserDisplayName() returns false only if the user
                                // genuinely can't be found by email — anything else (a failed
                                // PATCH, an API error) is thrown and handled below, rather than
                                // silently falling back to an unsubscribe/resubscribe.
                                $found = $mailman->updateUserDisplayName($record['email'], $data['display_name']);

                                if (! $found) {
                                    // No existing user record for this email — subscribe fresh.
                                    $mailman->subscribe($listId, $data['display_name'], $data['email']);
                                    $message = 'User re-subscribed with updated name';
                                } else {
                                    $message = "Display name updated to {$data['display_name']}";
                                }
                            }

                            Notification::make()
                                ->title('User updated successfully')
                                ->body("{$data['display_name']} ({$data['email']}) has been updated.")
                                ->success()
                                ->send();

                            $livewire->resetTable();

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to update user')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('unsubscribe')
                    ->label('Unsubscribe')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Unsubscribe User')
                    ->modalDescription(fn ($record) => "Are you sure you want to unsubscribe {$record['email']}?")
                    ->action(function ($record, $livewire) use ($listId) {
                        try {
                            $mailman = app(MailmanInterface::class);
                            $success = $mailman->unsubscribe($listId, $record['email']);

                            if ($success) {
                                Notification::make()
                                    ->title('User unsubscribed successfully')
                                    ->body("{$record['email']} has been unsubscribed.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('User not found')
                                    ->body('The user was not found on the mailing list.')
                                    ->warning()
                                    ->send();
                            }

                            $livewire->resetTable();

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to unsubscribe user')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
            ])
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No subscribers found')
            ->emptyStateDescription('This mailing list has no subscribers yet.')
            ->emptyStateActions([
                Action::make('subscribe')
                    ->label('Subscribe First User')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading('Subscribe User to List')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->placeholder('user@example.com'),
                        TextInput::make('display_name')
                            ->label('Display Name')
                            ->required()
                            ->placeholder('John Doe'),
                        TextInput::make('confirm_email')
                            ->label('Confirm Email')
                            ->email()
                            ->required()
                            ->same('email')
                            ->placeholder('Confirm email address'),
                    ])
                    ->action(function (array $data, $livewire) use ($listId) {
                        try {
                            $mailman = app(MailmanInterface::class);
                            $success = $mailman->subscribe($listId, $data['display_name'], $data['email']);

                            if ($success) {
                                Notification::make()
                                    ->title('User subscribed successfully')
                                    ->body("{$data['display_name']} ({$data['email']}) has been subscribed.")
                                    ->success()
                                    ->send();

                                $livewire->resetTable();
                            }

                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Subscription failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordUrl(null)
            ->defaultSort('email');
    }
}
