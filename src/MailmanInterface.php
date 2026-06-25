<?php

namespace VEximweb\Plugin\VEximMailman3;

interface MailmanInterface
{
    /**
     * Get all domains
     */
    public function domains(): array;

    /**
     * Check if a domain exists
     */
    public function domainExists(string $mailHost): bool;

    /**
     * Create a new domain
     */
    public function createDomain(string $mailHost, string $description = '', array $owners = []): bool;

    /**
     * Ensure a domain exists, create it if it doesn't
     */
    public function ensureDomain(string $mailHost, string $description = ''): bool;

    /**
     * Get lists for a specific domain
     */
    public function domainLists(string $mailHost): array;

    /**
     * List the members of a list.
     *
     * @param  string  $list_name  fqdn_listname (email) or list_id
     * @return array
     */
    public function members($list_name);

    /**
     *  List all the mailing lists.
     *
     * @return array
     */
    public function lists();

    /**
     * Creates a new mailing list.
     *
     * @param  string  $fqdn_listname  Full email address (e.g., list@domain.com)
     * @param  array  $options  Additional options: style_name, display_name, description, subject_prefix, domain_description
     * @return bool
     */
    public function create_list($fqdn_listname, array $options = []);

    /**
     * Updates the options of a list.
     *
     * @param  string  $list  fqdn_listname (email) or list_id
     * @param  array  $options  Array with the new values
     * @return bool
     */
    public function update_list($list, $options);

    /**
     * Removes a mailing list.
     *
     * @param  string  $name  fqdn_listname (email) or list_id
     * @return bool
     */
    public function remove_list($name);

    /**
     * Subscribes a user to a list.
     *
     * @param  string  $list_name  fqdn_listname (email) or list_id
     * @param  string  $user_name  Full name of the user
     * @param  string  $user_email  Email of the user
     * @return bool
     */
    public function subscribe($list_name, $user_name, $user_email);

    /**
     * Unsubscribes a user from a list.
     *
     * @param  string  $list_name  fqdn_listname (email) or list_id
     * @param  string  $user_email  Email of the user
     * @return bool
     */
    public function unsubscribe($list_name, $user_email);

    /**
     * Returns all the lists where the member is member.
     *
     * @param  string  $user  Email of the user
     * @return array
     */
    public function membership($user);

    /**
     * Get the configuration for a specific list
     */
    public function getListConfig($listName): array;

    /**
     * Update the configuration for a specific list
     */
    public function updateListConfig($listName, array $config): bool;

    /**
     * Get a specific config value for a list
     */
    public function getListConfigValue($listName, $key): mixed;

    /**
     * Update a user's display name by email.
     *
     * @param  string  $email  User's email address
     * @param  string  $displayName  New display name
     * @return bool False ONLY if no user could be found for the given email.
     *              Any other failure (e.g. the underlying API call failing)
     *              is thrown as an Exception rather than returned as false.
     *
     * @throws \Exception if the update request fails after the user is found
     */
    public function updateUserDisplayName(string $email, string $displayName): bool;
}
