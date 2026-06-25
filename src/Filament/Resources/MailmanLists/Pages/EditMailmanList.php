<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages;

use VEximweb\Core\Data\Models\Domain;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use VEximweb\Plugin\VEximMailman3\Facades\VEximMailman3;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Schemas\MailmanListForm;

class EditMailmanList extends EditRecord
{
    protected static string $resource = MailmanListResource::class;

    public function form(Schema $schema): Schema
    {
        $localFields = MailmanListForm::configure($schema)->getComponents();

        return $schema->components([
            Section::make('Local Database Configuration')
                ->description('These settings are stored in your local database.')
                ->schema($localFields),

            Section::make('Mailman 3 Configuration - Basic')
                ->description('Basic Mailman 3 list settings.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('mm_display_name')
                                ->label('Display Name')
                                ->helperText('The display name for the list in Mailman'),

                            TextInput::make('mm_subject_prefix')
                                ->label('Subject Prefix')
                                ->helperText('Prefix added to the subject of messages (e.g., [List])'),
                        ]),

                    Textarea::make('mm_description')
                        ->label('Mailman Description')
                        ->rows(2)
                        ->helperText('Description in Mailman'),

                    Textarea::make('mm_info')
                        ->label('Info')
                        ->rows(3)
                        ->helperText('Information about the list'),
                ]),

            Section::make('Mailman 3 Configuration - Posting')
                ->description('Posting and reply settings.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_allow_list_posts')
                                ->label('Allow List Posts')
                                ->helperText('Allow members to post to the list'),

                            Toggle::make('mm_anonymous_list')
                                ->label('Anonymous List')
                                ->helperText('Strip identifying information from posts'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Select::make('mm_reply_goes_to_list')
                                ->label('Reply Goes To List')
                                ->options([
                                    'no_munging' => 'No Munging',
                                    'point_to_list' => 'Point to List',
                                    'explicit_to' => 'Explicit To',
                                ])
                                ->helperText('Where replies should go'),

                            TextInput::make('mm_reply_to_address')
                                ->label('Reply To Address')
                                ->helperText('Custom Reply-To address'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_include_rfc2369_headers')
                                ->label('Include RFC 2369 Headers')
                                ->helperText('Include List-* headers in messages'),

                            Toggle::make('mm_respond_to_post_requests')
                                ->label('Respond to Post Requests')
                                ->helperText('Send response to post requests'),
                        ]),

                    Toggle::make('mm_require_explicit_destination')
                        ->label('Require Explicit Destination')
                        ->helperText('Require explicit destination in To/Cc headers'),
                ]),

            Section::make('Mailman 3 Configuration - Subscription')
                ->description('Subscription and member settings.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('mm_subscription_policy')
                                ->label('Subscription Policy')
                                ->options([
                                    'confirm' => 'Confirm',
                                    'moderate' => 'Moderate',
                                    'open' => 'Open',
                                ])
                                ->helperText('Who can subscribe to the list'),

                            Select::make('mm_unsubscription_policy')
                                ->label('Unsubscription Policy')
                                ->options([
                                    'confirm' => 'Confirm',
                                    'moderate' => 'Moderate',
                                    'open' => 'Open',
                                ])
                                ->helperText('Who can unsubscribe from the list'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_send_welcome_message')
                                ->label('Send Welcome Message')
                                ->helperText('Send a welcome message to new subscribers'),

                            Toggle::make('mm_send_goodbye_message')
                                ->label('Send Goodbye Message')
                                ->helperText('Send a goodbye message to unsubscribers'),
                        ]),

                    Select::make('mm_member_roster_visibility')
                        ->label('Member Roster Visibility')
                        ->options([
                            'moderators' => 'Moderators Only',
                            'members' => 'All Members',
                            'anyone' => 'Anyone',
                        ])
                        ->helperText('Who can see the member roster'),
                ]),

            Section::make('Mailman 3 Configuration - Archive & Bounce')
                ->description('Archive and bounce processing settings.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('mm_archive_policy')
                                ->label('Archive Policy')
                                ->options([
                                    'public' => 'Public',
                                    'private' => 'Private',
                                    'never' => 'Never',
                                ])
                                ->helperText('Who can access the archives'),

                            Toggle::make('mm_process_bounces')
                                ->label('Process Bounces')
                                ->helperText('Enable bounce processing'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('mm_bounce_info_stale_after')
                                ->label('Bounce Info Stale After')
                                ->helperText('e.g., 7d, 30d'),

                            TextInput::make('mm_bounce_score_threshold')
                                ->label('Bounce Score Threshold')
                                ->numeric()
                                ->helperText('Score threshold for bouncing'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('mm_bounce_you_are_disabled_warnings')
                                ->label('Bounce Warnings')
                                ->numeric()
                                ->helperText('Number of warnings before disabling'),

                            TextInput::make('mm_max_days_to_hold')
                                ->label('Max Days to Hold')
                                ->numeric()
                                ->helperText('Maximum days to hold messages'),
                        ]),
                ]),

            Section::make('Mailman 3 Configuration - Content Filtering')
                ->description('Content filtering and attachment settings.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_filter_content')
                                ->label('Filter Content')
                                ->helperText('Enable content filtering'),

                            Toggle::make('mm_convert_html_to_plaintext')
                                ->label('Convert HTML to Plaintext')
                                ->helperText('Convert HTML emails to plain text'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_collapse_alternatives')
                                ->label('Collapse Alternatives')
                                ->helperText('Collapse multipart/alternative parts'),

                            Select::make('mm_filter_action')
                                ->label('Filter Action')
                                ->options([
                                    'discard' => 'Discard',
                                    'hold' => 'Hold',
                                    'preserve' => 'Preserve',
                                ])
                                ->helperText('What to do with filtered messages'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('mm_max_message_size')
                                ->label('Max Message Size (KB)')
                                ->numeric()
                                ->helperText('Maximum size of a message in KB'),

                            TextInput::make('mm_max_num_recipients')
                                ->label('Max Number of Recipients')
                                ->numeric()
                                ->helperText('Maximum number of recipients per message'),
                        ]),

                    TagsInput::make('mm_filter_extensions')
                        ->label('Filter Extensions')
                        ->helperText('File extensions to filter (e.g., .exe, .zip)'),

                    TagsInput::make('mm_filter_types')
                        ->label('Filter MIME Types')
                        ->helperText('MIME types to filter (e.g., application/zip)'),

                    TagsInput::make('mm_pass_extensions')
                        ->label('Pass Extensions')
                        ->helperText('File extensions to always pass'),

                    TagsInput::make('mm_pass_types')
                        ->label('Pass MIME Types')
                        ->helperText('MIME types to always pass'),
                ]),

            Section::make('Mailman 3 Configuration - DMARC')
                ->description(new HtmlString('DMARC mitigation settings. Visit the <a href="https://docs.mailman3.org/projects/mailman/en/latest/src/mailman/handlers/docs/dmarc-mitigations.html" target="_blank" class="text-primary-600 hover:underline"><u>documentation</u></a> for more information.'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('mm_dmarc_mitigate_action')
                                ->label('DMARC Mitigation Action')
                                ->options([
                                    'no_mitigation' => 'No Mitigation',
                                    'munge_from' => 'Munge From',
                                    'wrap_message' => 'Wrap Message',
                                    'reject' => 'Reject',
                                ])
                                ->helperText('How to handle DMARC failures'),

                            Toggle::make('mm_dmarc_mitigate_unconditionally')
                                ->label('Mitigate Unconditionally')
                                ->helperText('Apply DMARC mitigation to all messages'),
                        ]),

                    TagsInput::make('mm_dmarc_addresses')
                        ->label('DMARC Addresses')
                        ->helperText('Email addresses or patterns to apply DMARC mitigation to'),
                ]),

            Section::make('Mailman 3 Configuration - Moderation')
                ->description(new HtmlString('Moderation and default actions. Visit the <a href="https://docs.mailman3.org/" target="_blank" class="text-primary-600 hover:underline"><u>documentation</u></a> for more information.'))
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('mm_default_member_action')
                                ->label('Default Member Action')
                                ->options([
                                    'defer' => 'Defer',
                                    'hold' => 'Hold',
                                    'reject' => 'Reject',
                                    'discard' => 'Discard',
                                    'accept' => 'Accept',
                                ])
                                ->helperText('Default action for member posts'),

                            Select::make('mm_default_nonmember_action')
                                ->label('Default Non-Member Action')
                                ->options([
                                    'defer' => 'Defer',
                                    'hold' => 'Hold',
                                    'reject' => 'Reject',
                                    'discard' => 'Discard',
                                    'accept' => 'Accept',
                                ])
                                ->helperText('Default action for non-member posts'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('mm_moderator_password')
                                ->label('Moderator Password')
                                ->helperText('Password for moderators (leave empty to keep current)'),

                            Toggle::make('mm_emergency')
                                ->label('Emergency')
                                ->helperText('Emergency moderation mode'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_admin_immed_notify')
                                ->label('Admin Immediate Notify')
                                ->helperText('Notify admin immediately of held messages'),

                            Toggle::make('mm_admin_notify_mchanges')
                                ->label('Admin Notify Member Changes')
                                ->helperText('Notify admin of member changes'),
                        ]),

                    Toggle::make('mm_administrivia')
                        ->label('Administrivia')
                        ->helperText('Filter administrative messages'),
                ]),

            Section::make('Mailman 3 Configuration - Advanced')
                ->description('Advanced settings.')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Toggle::make('mm_advertised')
                                ->label('Advertised')
                                ->helperText('Whether the list is advertised'),

                            TextInput::make('mm_preferred_language')
                                ->label('Preferred Language')
                                ->helperText('Default language for the list (e.g., en, ja)'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Select::make('mm_personalize')
                                ->label('Personalize')
                                ->options([
                                    'none' => 'None',
                                    'full' => 'Full',
                                    'delivered' => 'Delivered',
                                ])
                                ->helperText('Personalization setting'),

                            TextInput::make('mm_footer_uri')
                                ->label('Footer URI')
                                ->helperText('URI for email footer'),
                        ]),

                    TextInput::make('mm_header_uri')
                        ->label('Header URI')
                        ->helperText('URI for email header'),
                ]),
        ]);
    }

    /**
     * Convert email format (list@domain) to Mailman API format (list.domain)
     */
    protected function emailToMailmanFormat(string $email): string
    {
        $converted = str_replace('@', '.', $email);

        return $converted;
    }

    /**
     * Before filling, split list_email and fetch Mailman config
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {

        if (! empty($data['list_email'])) {
            $data['email_input'] = $data['list_email'];

            $parts = explode('@', $data['list_email']);
            $data['list_name'] = $parts[0] ?? '';

            $domainName = $parts[1] ?? '';
            if ($domainName) {
                $domain = Domain::where('domain', $domainName)->first();
                if ($domain) {
                    $data['domain_id'] = $domain->domain_id;
                    \Log::info('Found domain', [
                        'domain_name' => $domainName,
                        'domain_id' => $domain->domain_id,
                        'domain_field' => $domain->domain,
                    ]);
                } else {
                    \Log::warning('Domain not found in database', ['domain_name' => $domainName]);
                }
            }

            $mailmanListId = $data['mailman_list_id'] ?? $this->emailToMailmanFormat($data['list_email']);

            try {
                $mailmanConfig = VEximMailman3::getListConfig($mailmanListId);

                if (! empty($mailmanConfig) && is_array($mailmanConfig)) {
                    foreach ($mailmanConfig as $key => $value) {
                        $data['mm_' . $key] = $value;
                    }
                } else {
                    \Log::warning('Mailman config is empty or not an array', [
                        'mailmanConfig' => $mailmanConfig,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Could not fetch Mailman config', [
                    'list' => $data['list_email'],
                    'mailman_list_id' => $mailmanListId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        } else {
            \Log::warning('list_email is empty in mutateFormDataBeforeFill');
        }

        return $data;
    }

    /**
     * Before saving, validate domain and prepare data
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {

        if (! empty($data['email_input']) && str_contains($data['email_input'], '@')) {
            $parts = explode('@', $data['email_input']);
            $listName = $parts[0] ?? '';
            $domainName = $parts[1] ?? '';

            $domain = Domain::where('domain', $domainName)->first();
            if (! $domain) {
                \Log::error('MM3: Domain not found', ['domainName' => $domainName]);
                Notification::make()
                    ->title('Domain Not Found')
                    ->body("The domain '{$domainName}' does not exist in the system.")
                    ->danger()
                    ->send();
                $this->halt();

                return $data;
            }

            if (! $domain->enabled) {
                \Log::error('Domain disabled', ['domainName' => $domainName]);
                Notification::make()
                    ->title('Domain Disabled')
                    ->body("The domain '{$domainName}' is currently disabled.")
                    ->danger()
                    ->send();
                $this->halt();

                return $data;
            }

            $data['list_email'] = $data['email_input'];
            $data['list_name'] = $listName;
            $data['domain_id'] = $domain->domain_id;
        } else {
            \Log::info('MM3: Using domain_id fallback', [
                'domain_id' => $data['domain_id'] ?? 'not set',
            ]);

            $domain = Domain::find($data['domain_id']);
            if (! $domain) {
                \Log::error('MM3: Domain not found by ID', ['domain_id' => $data['domain_id'] ?? 'not set']);
                Notification::make()
                    ->title('Domain Not Found')
                    ->body('The selected domain does not exist.')
                    ->danger()
                    ->send();
                $this->halt();

                return $data;
            }

            if (! $domain->enabled) {
                \Log::error('MM3: Domain disabled', ['domain_id' => $data['domain_id']]);
                Notification::make()
                    ->title('Domain Disabled')
                    ->body('The selected domain is currently disabled.')
                    ->danger()
                    ->send();
                $this->halt();

                return $data;
            }

            $data['list_email'] = $data['list_name'] . '@' . $domain->domain;
            $data['domain_id'] = $domain->domain_id;

            \Log::info('Set data from domain_id', [
                'list_email' => $data['list_email'],
                'domain_id' => $domain->domain_id,
            ]);
        }

        // Generate mailman_list_id (listname.domain format)
        $parts = explode('@', $data['list_email']);
        $data['mailman_list_id'] = $parts[0] . '.' . ($parts[1] ?? '');

        return $data;
    }

    /**
     * After saving, sync with Mailman
     */
    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        try {
            $domain = Domain::find($record->domain_id);
            if (! $domain) {
                \Log::error('MM3: Domain not found for record', ['domain_id' => $record->domain_id]);
                Notification::make()
                    ->title('Domain Not Found')
                    ->body('The domain for this list no longer exists.')
                    ->danger()
                    ->send();

                return;
            }

            $domainName = $domain->domain;

            if (empty($domainName)) {
                \Log::error('MM3: Domain name is empty', [
                    'domain_id' => $domain->domain_id,
                    'domain_object' => $domain->toArray(),
                ]);
                Notification::make()
                    ->title('Domain Error')
                    ->body('The domain name is empty. Please check your domain configuration.')
                    ->danger()
                    ->send();

                return;
            }

            // Use the mailman_list_id from the record
            $mailmanListId = $record->mailman_list_id;

            if (empty($mailmanListId)) {
                $mailmanListId = $this->emailToMailmanFormat($record->list_email);
                $record->update(['mailman_list_id' => $mailmanListId]);
            }

            $domainExists = VEximMailman3::domainExists($domainName);

            if (! $domainExists) {
                $domainCreated = VEximMailman3::createDomain($domainName, $domainName);
                if (! $domainCreated) {
                    \Log::error('MM3: Failed to create domain in Mailman', [
                        'domain_name' => $domainName,
                    ]);
                    Notification::make()
                        ->title('Failed to create domain in Mailman')
                        ->body("Could not create domain '{$domainName}' in Mailman.")
                        ->danger()
                        ->send();

                    return;
                }
            }

            // Get all lists from Mailman
            $existingLists = VEximMailman3::lists();

            $listExists = collect($existingLists)->contains(function ($list) use ($mailmanListId) {
                return ($list['list_id'] ?? '') === $mailmanListId;
            });

            if (! $listExists) {

                $created = VEximMailman3::create_list($record->list_email, [
                    'display_name' => $data['mm_display_name'] ?? $record->list_name,
                    'description' => $data['mm_description'] ?? $record->description ?? '',
                ]);

                \Log::info('Create list result', [
                    'created' => $created,
                ]);

                if ($created) {
                    Notification::make()
                        ->title('List created in Mailman 3 successfully!')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to create list in Mailman 3.')
                        ->danger()
                        ->send();
                }
            } else {

                $config = $this->buildMailmanConfig($data);

                if (! empty($config)) {
                    $updated = VEximMailman3::updateListConfig($mailmanListId, $config);

                    if ($updated) {
                        Notification::make()
                            ->title('Mailman Configuration Updated')
                            ->body('The list configuration has been updated in Mailman 3 successfully!')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to Update Mailman')
                            ->body('There was an error updating the configuration in Mailman.')
                            ->warning()
                            ->send();
                    }
                } else {
                    \Log::info('No configuration changes to update');
                    Notification::make()
                        ->title('No Configuration Changes')
                        ->body('No Mailman configuration settings were provided to update.')
                        ->info()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            \Log::error('MM3: Mailman sync failed on update', [
                'list_id' => $record->list_id ?? 'unknown',
                'email' => $record->list_email ?? 'unknown',
                'mailman_list_id' => $record->mailman_list_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error syncing with Mailman')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Build the Mailman config array from form data
     */
    protected function buildMailmanConfig(array $data): array
    {

        $config = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'mm_')) {
                $configKey = substr($key, 3);
                if ($value !== null && $value !== '') {
                    $config[$configKey] = $value;
                    \Log::info('Added config key', [
                        'key' => $configKey,
                        'value' => $value,
                    ]);
                }
            }
        }

        return $config;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
