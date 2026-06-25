<?php

namespace VEximweb\Plugin\VEximMailman3;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Mailman implements MailmanInterface
{
    protected Client $client;

    protected string $host;

    protected string $port;

    protected string $username;

    protected string $password;

    public function __construct(string $host, string $port, string $username, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;

        $this->client = new Client([
            'base_uri' => "{$this->host}:{$this->port}/",
            'auth' => [$this->username, $this->password],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Extract user ID from self_link URL
     * Example: http://172.28.0.3:8001/3.0/users/303729405391604530024862374471981675453
     * Returns: 303729405391604530024862374471981675453
     */
    protected function extractUserIdFromSelfLink(string $selfLink): ?string
    {
        if (preg_match('/\/users\/([0-9]+)/', $selfLink, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get all domains
     */
    public function domains(): array
    {
        $response = $this->client->get('3.0/domains');
        $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

        return $data['entries'] ?? [];
    }

    /**
     * Check if a domain exists
     */
    public function domainExists(string $mailHost): bool
    {
        try {
            $response = $this->client->get("3.0/domains/{$mailHost}");

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a new domain
     */
    public function createDomain(string $mailHost, string $description = '', array $owners = []): bool
    {
        $payload = ['mail_host' => $mailHost];
        if (! empty($description)) {
            $payload['description'] = $description;
        }
        if (! empty($owners)) {
            $payload['owners'] = $owners;
        }

        $response = $this->client->post('3.0/domains', ['json' => $payload]);

        return $response->getStatusCode() === 201;
    }

    /**
     * Ensure a domain exists, create it if it doesn't
     */
    public function ensureDomain(string $mailHost, string $description = ''): bool
    {
        if ($this->domainExists($mailHost)) {
            return true;
        }

        return $this->createDomain($mailHost, $description);
    }

    /**
     * Get lists for a specific domain
     */
    public function domainLists(string $mailHost): array
    {
        $response = $this->client->get("3.0/domains/{$mailHost}/lists");
        $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

        return $data['entries'] ?? [];
    }

    /**
     * Get members of a list with full details
     */
    public function members($list_name): array
    {
        try {
            $response = $this->client->get("3.0/lists/{$list_name}/roster/member");
            $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

            $entries = $data['entries'] ?? [];

            // Enhance member data with user display names if available
            foreach ($entries as &$entry) {
                // Keep member_id as string (it can be a large number)
                if (isset($entry['member_id'])) {
                    $entry['member_id'] = (string) $entry['member_id'];
                }

                // Fetch user details to get proper display_name
                if (isset($entry['user_id'])) {
                    $userId = (string) $entry['user_id'];

                    try {
                        $userResponse = $this->client->get("3.0/users/{$userId}");
                        $userData = json_decode($userResponse->getBody(), true);
                        if (isset($userData['display_name']) && $userData['display_name']) {
                            $entry['display_name'] = $userData['display_name'];
                        }
                    } catch (\Exception $e) {
                        // User endpoint might fail, keep the existing display_name
                    }
                }
            }

            \Log::debug('Mailman Members: ' . json_encode($entries));

            return $entries;
        } catch (\Exception $e) {
            \Log::error('Failed to get members', [
                'list' => $list_name,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get all lists
     */
    public function lists(): array
    {
        $response = $this->client->get('3.0/lists');
        $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

        return $data['entries'] ?? [];
    }

    /**
     * Create a new list
     */
    public function create_list($fqdn_listname, array $options = []): bool
    {
        if (empty($fqdn_listname) || ! str_contains($fqdn_listname, '@')) {
            throw new \Exception('Invalid email address format: ' . $fqdn_listname);
        }

        $parts = explode('@', $fqdn_listname);
        $mail_host = $parts[1] ?? '';
        if (empty($mail_host)) {
            throw new \Exception('Invalid email address: could not extract domain from ' . $fqdn_listname);
        }

        $domainCreated = $this->ensureDomain($mail_host, $options['domain_description'] ?? '');
        if (! $domainCreated) {
            throw new \Exception('Failed to create or verify domain: ' . $mail_host);
        }

        $payload = ['fqdn_listname' => $fqdn_listname];
        if (! empty($options['owners'])) {
            $payload['owners'] = $options['owners'];
        }
        if (! empty($options['style_name'])) {
            $payload['style_name'] = $options['style_name'];
        }

        $response = $this->client->post('3.0/lists', ['json' => $payload]);

        return $response->getStatusCode() === 201;
    }

    /**
     * Update a list
     */
    public function update_list($list, $options): bool
    {
        $response = $this->client->patch("3.0/lists/{$list}", ['json' => $options]);

        return $response->getStatusCode() === 200;
    }

    /**
     * Remove a list
     */
    public function remove_list($name): bool
    {
        $response = $this->client->delete("3.0/lists/{$name}");

        return $response->getStatusCode() === 204;
    }

    /**
     * Subscribe a user to a list with proper display name handling
     */
    public function subscribe($list_name, $user_name, $user_email): bool
    {
        try {
            \Log::info('Starting subscription', [
                'list' => $list_name,
                'email' => $user_email,
                'display_name' => $user_name,
            ]);

            // First, check if user exists and get or create user with proper display name
            $userId = $this->getOrCreateUserWithDisplayName($user_email, $user_name);

            if (! $userId) {
                \Log::error('Failed to get or create user', [
                    'email' => $user_email,
                    'display_name' => $user_name,
                ]);

                return false;
            }

            \Log::info('User ID found/created', [
                'user_id' => $userId,
                'email' => $user_email,
            ]);

            // Now subscribe the user to the list using the user ID
            $payload = [
                'list_id' => $list_name,
                'subscriber' => $userId,
                'pre_verified' => true,
                'pre_confirmed' => true,
                'pre_approved' => true,
            ];

            $response = $this->client->post('3.0/members', ['json' => $payload]);

            $success = in_array($response->getStatusCode(), [201, 204]);

            if ($success) {
                \Log::info('User subscribed successfully', [
                    'list' => $list_name,
                    'email' => $user_email,
                    'display_name' => $user_name,
                    'user_id' => $userId,
                ]);
            }

            return $success;

        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();

            if ($status === 409) {
                // User already subscribed - just update display name
                \Log::info('User already subscribed, updating display name', [
                    'email' => $user_email,
                    'display_name' => $user_name,
                ]);

                return $this->updateUserDisplayName($user_email, $user_name);
            }

            \Log::error('Subscription failed', [
                'list' => $list_name,
                'email' => $user_email,
                'error' => $e->getMessage(),
                'status' => $status,
            ]);

            throw new \Exception('Failed to subscribe user: ' . $e->getMessage(), $status, $e);
        } catch (\Exception $e) {
            \Log::error('Subscription failed with exception', [
                'list' => $list_name,
                'email' => $user_email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get or create a user with proper display name
     *
     * Returns user_id as string to handle 128-bit integers
     */
    protected function getOrCreateUserWithDisplayName(string $email, string $displayName): ?string
    {
        try {
            // Search for existing user - IMPORTANT: Use JSON_BIGINT_AS_STRING to preserve full integer
            $response = $this->client->get('3.0/users', [
                'query' => ['email' => $email],
            ]);
            $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

            \Log::debug('User search response', [
                'email' => $email,
                'response' => $data,
            ]);

            if (! empty($data['entries'])) {
                $user = $data['entries'][0];

                // ALWAYS extract user_id from self_link to get the full number
                $userId = null;
                if (isset($user['self_link'])) {
                    $userId = $this->extractUserIdFromSelfLink($user['self_link']);
                }

                // Fallback to user_id if self_link extraction fails
                if (! $userId && isset($user['user_id'])) {
                    $userId = (string) $user['user_id'];
                }

                if ($userId) {
                    // Check if display name needs updating
                    $currentDisplayName = $user['display_name'] ?? '';
                    \Log::info('Existing user found', [
                        'user_id' => $userId,
                        'email' => $email,
                        'current_display_name' => $currentDisplayName,
                        'new_display_name' => $displayName,
                        'self_link' => $user['self_link'] ?? null,
                    ]);

                    if ($currentDisplayName !== $displayName) {
                        // Update display name using PATCH
                        \Log::info('Updating display name', [
                            'user_id' => $userId,
                            'old_name' => $currentDisplayName,
                            'new_name' => $displayName,
                        ]);

                        $patchResponse = $this->client->patch("3.0/users/{$userId}", [
                            'json' => ['display_name' => $displayName],
                        ]);

                        $patchSuccess = $patchResponse->getStatusCode() === 204;

                        if ($patchSuccess) {
                            \Log::info('Successfully updated user display name', [
                                'user_id' => $userId,
                                'email' => $email,
                                'old_name' => $currentDisplayName,
                                'new_name' => $displayName,
                            ]);
                        } else {
                            \Log::warning('Failed to update user display name', [
                                'user_id' => $userId,
                                'status' => $patchResponse->getStatusCode(),
                                'response' => (string) $patchResponse->getBody(),
                            ]);
                        }
                    } else {
                        \Log::info('Display name already matches', [
                            'user_id' => $userId,
                            'display_name' => $displayName,
                        ]);
                    }

                    return $userId;
                }
            }

            // Create new user
            \Log::info('Creating new user', [
                'email' => $email,
                'display_name' => $displayName,
            ]);

            $payload = [
                'email' => $email,
                'display_name' => $displayName,
            ];

            $response = $this->client->post('3.0/users', ['json' => $payload]);

            if ($response->getStatusCode() === 201) {
                $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

                // ALWAYS extract user_id from self_link
                $userId = null;
                if (isset($data['self_link'])) {
                    $userId = $this->extractUserIdFromSelfLink($data['self_link']);
                }

                // Fallback to user_id if self_link extraction fails
                if (! $userId && isset($data['user_id'])) {
                    $userId = (string) $data['user_id'];
                }

                if ($userId) {
                    \Log::info('Created new user successfully', [
                        'user_id' => $userId,
                        'email' => $email,
                        'display_name' => $displayName,
                        'self_link' => $data['self_link'] ?? null,
                    ]);

                    return $userId;
                }
            }

            \Log::warning('Failed to create user', [
                'email' => $email,
                'status' => $response->getStatusCode(),
                'response' => (string) $response->getBody(),
            ]);

            return null;

        } catch (\Exception $e) {
            \Log::error('Failed to get or create user', [
                'email' => $email,
                'display_name' => $displayName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Update a user's display name by email.
     * Uses: PATCH /3.0/users/{user_id} with {'display_name': 'New Name'}
     *
     * Returns false ONLY when the user could not be found by email
     * (a genuine "no such user" case). Any failure while talking to
     * the Mailman API — including a non-204 response from the PATCH
     * itself — is thrown rather than swallowed, so callers can tell
     * "user doesn't exist" apart from "the update attempt failed".
     *
     * @throws \Exception if the PATCH request fails or returns an unexpected status
     */
    public function updateUserDisplayName(string $email, string $displayName): bool
    {
        \Log::info('Attempting to update user display name', [
            'email' => $email,
            'display_name' => $displayName,
        ]);

        // Find the user by email
        $userId = $this->findUserIdByEmail($email);

        if (! $userId) {
            \Log::warning('User not found for display name update', ['email' => $email]);

            return false;
        }

        \Log::info('Found user for display name update', [
            'user_id' => $userId,
            'email' => $email,
        ]);

        try {
            // Update the display name using PATCH
            $response = $this->client->patch("3.0/users/{$userId}", [
                'json' => ['display_name' => $displayName],
            ]);

            $success = $response->getStatusCode() === 204;

            if ($success) {
                \Log::info('Successfully updated user display name', [
                    'user_id' => $userId,
                    'email' => $email,
                    'display_name' => $displayName,
                ]);

                return true;
            }

            // Got a response, but not the expected 204 — treat as a real failure,
            // not a "user not found" case, so the caller doesn't fall back to
            // unsubscribe/resubscribe for what is actually an API-level problem.
            \Log::warning('Unexpected status updating user display name', [
                'user_id' => $userId,
                'status' => $response->getStatusCode(),
                'response' => (string) $response->getBody(),
            ]);

            throw new \Exception(
                "Failed to update display name for {$email}: Mailman API returned status {$response->getStatusCode()}"
            );

        } catch (ClientException $e) {
            $status = $e->getResponse()->getStatusCode();

            \Log::error('Failed to update user display name', [
                'email' => $email,
                'user_id' => $userId,
                'display_name' => $displayName,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                "Failed to update display name for {$email}: " . $e->getMessage(),
                $status,
                $e
            );
        }
    }

    /**
     * Find a user ID by email address
     *
     * Returns user_id as string to handle 128-bit integers
     */
    protected function findUserIdByEmail(string $email): ?string
    {
        try {
            $response = $this->client->get('3.0/users', [
                'query' => ['email' => $email],
            ]);

            $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

            \Log::debug('findUserIdByEmail response', [
                'email' => $email,
                'response' => $data,
            ]);

            if (! empty($data['entries'])) {
                $user = $data['entries'][0];

                // ALWAYS extract user_id from self_link to get the full number
                $userId = null;
                if (isset($user['self_link'])) {
                    $userId = $this->extractUserIdFromSelfLink($user['self_link']);
                }

                // Fallback to user_id if self_link extraction fails
                if (! $userId && isset($user['user_id'])) {
                    $userId = (string) $user['user_id'];
                }

                \Log::debug('findUserIdByEmail result', [
                    'email' => $email,
                    'user_id' => $userId,
                    'self_link' => $user['self_link'] ?? null,
                ]);

                return $userId;
            }

            return null;

        } catch (\Exception $e) {
            \Log::error('Failed to find user by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Subscribe a user by their user ID
     */
    public function subscribeByUserId($list_name, $user_id, $display_name = null): bool
    {
        try {
            // If display name provided, update the user
            if ($display_name) {
                $this->updateUserDisplayNameById($user_id, $display_name);
            }

            $payload = [
                'list_id' => $list_name,
                'subscriber' => $user_id,
                'pre_verified' => true,
                'pre_confirmed' => true,
                'pre_approved' => true,
            ];

            $response = $this->client->post('3.0/members', ['json' => $payload]);

            return in_array($response->getStatusCode(), [201, 204]);

        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 409) {
                return true;
            }

            throw $e;
        }
    }

    /**
     * Update a user's display name by user ID
     * Uses: PATCH /3.0/users/{user_id} with {'display_name': 'New Name'}
     */
    protected function updateUserDisplayNameById($userId, string $displayName): bool
    {
        try {
            $response = $this->client->patch("3.0/users/{$userId}", [
                'json' => ['display_name' => $displayName],
            ]);

            return $response->getStatusCode() === 204;

        } catch (\Exception $e) {
            \Log::error('Failed to update user display name by ID', [
                'user_id' => $userId,
                'display_name' => $displayName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unsubscribe a user from a list
     */
    public function unsubscribe($list_name, $user_email): bool
    {
        try {
            // First, try to find the member ID
            $members = $this->members($list_name);
            foreach ($members as $member) {
                if (strcasecmp($member['email'] ?? '', $user_email) === 0) {
                    $memberId = $member['member_id'] ?? null;
                    if ($memberId) {
                        $response = $this->client->delete("3.0/members/{$memberId}");
                        if ($response->getStatusCode() === 204) {
                            \Log::info('User unsubscribed', [
                                'list' => $list_name,
                                'email' => $user_email,
                                'member_id' => $memberId,
                            ]);

                            return true;
                        }
                    }
                }
            }

            // If not found in members, try the direct endpoint
            $encodedEmail = rawurlencode($user_email);
            $response = $this->client->delete("3.0/lists/{$list_name}/members/{$encodedEmail}");

            return $response->getStatusCode() === 204;

        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 404) {
                \Log::warning('User not found for unsubscribe', [
                    'list' => $list_name,
                    'email' => $user_email,
                ]);

                return false;
            }

            throw $e;
        }
    }

    /**
     * Get memberships for a user
     */
    public function membership($user): array
    {
        $response = $this->client->get("3.0/members/{$user}/lists");
        $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

        return $data['entries'] ?? [];
    }

    /**
     * Get the configuration for a specific list
     */
    public function getListConfig($listName): array
    {
        try {
            $response = $this->client->get("3.0/lists/{$listName}/config");

            return json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch list config', [
                'list' => $listName,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Update the configuration for a specific list
     */
    public function updateListConfig($listName, array $config): bool
    {
        try {
            $response = $this->client->patch("3.0/lists/{$listName}/config", ['json' => $config]);

            return $response->getStatusCode() === 204;
        } catch (\Exception $e) {
            \Log::error('Failed to update list config', [
                'list' => $listName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get a specific config value for a list
     */
    public function getListConfigValue($listName, $key): mixed
    {
        try {
            $response = $this->client->get("3.0/lists/{$listName}/config/{$key}");
            $data = json_decode($response->getBody(), true, 512, JSON_BIGINT_AS_STRING);

            return $data[$key] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
