<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MailmanConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Settings')
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

                Section::make('Posting Settings')
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

                Section::make('Subscription Settings')
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

                Section::make('Archive & Bounce Settings')
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

                Section::make('Content Filtering')
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

                Section::make('DMARC Settings')
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

                Section::make('Moderation Settings')
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

                Section::make('Advanced Settings')
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
}
