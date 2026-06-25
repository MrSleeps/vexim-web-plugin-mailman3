<?php

namespace VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use VEximweb\Core\Data\Models\Domain;
use VEximweb\Plugin\VEximMailman3\Facades\VEximMailman3;
use VEximweb\Plugin\VEximMailman3\Filament\Resources\MailmanLists\MailmanListResource;

class CreateMailmanList extends CreateRecord
{
    protected static string $resource = MailmanListResource::class;

    /**
     * Before creating, validate and create in Mailman first
     */
    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        if (! empty($data['email_input']) && str_contains($data['email_input'], '@')) {
            $parts = explode('@', $data['email_input']);
            $listName = $parts[0] ?? '';
            $domainName = $parts[1] ?? '';

            $domain = Domain::where('domain', $domainName)->first();

            if (! $domain) {
                Notification::make()
                    ->title('Domain Not Found')
                    ->body("The domain '{$domainName}' does not exist in the system. Please add the domain first before creating a list.")
                    ->danger()
                    ->send();

                $this->halt();

                return;
            }

            if (! $domain->enabled) {
                Notification::make()
                    ->title('Domain Disabled')
                    ->body("The domain '{$domainName}' is currently disabled. Please enable it first.")
                    ->danger()
                    ->send();

                $this->halt();

                return;
            }

            $this->form->fill([
                'list_name' => $listName,
                'domain_id' => $domain->domain_id,
                'list_email' => $data['email_input'],
            ]);
        } else {
            if (! empty($data['domain_id'])) {
                $domain = Domain::find($data['domain_id']);
                if (! $domain) {
                    Notification::make()
                        ->title('Invalid Domain')
                        ->body('The selected domain does not exist.')
                        ->danger()
                        ->send();

                    $this->halt();

                    return;
                }

                if (! $domain->enabled) {
                    Notification::make()
                        ->title('Domain Disabled')
                        ->body('The selected domain is currently disabled.')
                        ->danger()
                        ->send();

                    $this->halt();

                    return;
                }
            }
        }

        try {
            VEximMailman3::lists();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Connection Error')
                ->body('Cannot connect to Mailman API. Please check your Mailman configuration.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    /**
     * Create the list in Mailman before saving to database
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $domain = null;
        $domainName = '';

        if (! empty($data['email_input']) && str_contains($data['email_input'], '@')) {
            $parts = explode('@', $data['email_input']);
            $localPart = $parts[0] ?? '';
            $domainPart = $parts[1] ?? '';

            \Log::info('Extracted from email', [
                'email_input' => $data['email_input'],
                'localPart' => $localPart,
                'domainPart' => $domainPart,
            ]);

            if (empty($domainPart)) {
                Notification::make()
                    ->title('Invalid Email')
                    ->body('Could not extract domain from the email address.')
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            $data['list_email'] = $data['email_input'];
            $data['list_name'] = $localPart;

            $domain = Domain::where('domain', $domainPart)->first();

            if (! $domain) {
                Notification::make()
                    ->title('Domain Not Found')
                    ->body("The domain '{$domainPart}' does not exist in the system. Please add the domain first.")
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            if (! $domain->enabled) {
                Notification::make()
                    ->title('Domain Disabled')
                    ->body("The domain '{$domainPart}' is disabled. Please enable it first.")
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            $data['domain_id'] = $domain->domain_id;

            $domainName = $domain->domain;

            \Log::info('Domain found in database', [
                'domain_id' => $domain->domain_id,
                'domain_name' => $domainName,
                'domain_object_domain' => $domain->domain,
            ]);

        } else {
            if (empty($data['domain_id'])) {
                Notification::make()
                    ->title('Missing Domain')
                    ->body('No domain selected. Please select a domain.')
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            $domain = Domain::find($data['domain_id']);
            if (! $domain) {
                Notification::make()
                    ->title('Domain Not Found')
                    ->body('The selected domain does not exist.')
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            if (! $domain->enabled) {
                Notification::make()
                    ->title('Domain Disabled')
                    ->body('The selected domain is disabled. Please enable it first.')
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            $domainName = $domain->domain;

            if (empty($domainName)) {
                \Log::error('Domain name is empty', [
                    'domain_id' => $domain->domain_id,
                    'domain' => $domain->domain,
                ]);

                Notification::make()
                    ->title('Domain Error')
                    ->body("The domain name is empty for domain ID {$domain->domain_id}.")
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            $data['list_email'] = $data['list_name'] . '@' . $domainName;
        }

        if (! $domain) {
            Notification::make()
                ->title('Domain Error')
                ->body('Could not determine the domain for this list.')
                ->danger()
                ->send();

            $this->halt();

            return $data;
        }

        if (empty($domainName)) {
            Notification::make()
                ->title('Domain Name Error')
                ->body('The domain name is empty. Please check your domain configuration.')
                ->danger()
                ->send();

            $this->halt();

            return $data;
        }

        try {
            $domainExists = VEximMailman3::domainExists($domainName);
            if (! $domainExists) {
                \Log::info('Domain not found in Mailman, creating', ['domain' => $domainName]);
                $domainCreated = VEximMailman3::createDomain($domainName, $domainName);
                if (! $domainCreated) {
                    Notification::make()
                        ->title('Failed to create domain in Mailman')
                        ->body("Could not create domain '{$domainName}' in Mailman.")
                        ->danger()
                        ->send();

                    $this->halt();

                    return $data;
                }
            }

            $created = VEximMailman3::create_list($data['list_email'], [
                'display_name' => $data['list_name'],
                'description' => $data['description'] ?? '',
            ]);

            if (! $created) {
                Notification::make()
                    ->title('Failed to create list in Mailman 3')
                    ->body('The list could not be created in Mailman. Please check the logs for details.')
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }

            $parts = explode('@', $data['list_email']);
            $data['mailman_list_id'] = $parts[0] . '.' . ($parts[1] ?? '');

        } catch (\Exception $e) {
            \Log::error('Mailman create failed', [
                'email' => $data['list_email'] ?? 'unknown',
                'domain' => $domainName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Error creating list in Mailman')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    /**
     * After successful creation, notify the user
     */
    protected function afterCreate(): void
    {
        $record = $this->record;

        Notification::make()
            ->title('List created successfully!')
            ->body("List {$record->list_email} has been created in Mailman 3 and saved locally.")
            ->success()
            ->send();
    }

    /**
     * Redirect after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
