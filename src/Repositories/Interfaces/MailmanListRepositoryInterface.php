<?php

namespace VEximweb\Plugin\VEximMailman3\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use VEximweb\Plugin\VEximMailman3\Models\MailmanList;

interface MailmanListRepositoryInterface
{
    /**
     * Get all mailing lists.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, MailmanList>
     */
    public function all(array $filters = []): Collection;

    /**
     * Get paginated mailing lists.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;

    /**
     * Find a mailing list by its ID.
     */
    public function findById(int $listId): ?MailmanList;

    /**
     * Find a mailing list by its full email address.
     */
    public function findByEmail(string $email): ?MailmanList;

    /**
     * Find a mailing list by Mailman's internal list ID.
     */
    public function findByMailmanId(string $mailmanListId): ?MailmanList;

    /**
     * Get all mailing lists for a specific domain.
     *
     * @return Collection<int, MailmanList>
     */
    public function getByDomain(int $domainId, bool $onlyEnabled = false): Collection;

    /**
     * Create a new mailing list.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): MailmanList;

    /**
     * Update an existing mailing list.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $listId, array $data): MailmanList;

    /**
     * Delete a mailing list (soft delete).
     */
    public function delete(int $listId): bool;

    /**
     * Force delete a mailing list (permanent).
     */
    public function forceDelete(int $listId): bool;

    /**
     * Restore a soft-deleted mailing list.
     */
    public function restore(int $listId): bool;

    /**
     * Enable a mailing list.
     */
    public function enable(int $listId): bool;

    /**
     * Disable a mailing list.
     */
    public function disable(int $listId): bool;

    /**
     * Check if a mailing list exists for a given email.
     */
    public function exists(string $email): bool;

    /**
     * Count mailing lists for a domain.
     */
    public function countByDomain(int $domainId, bool $onlyEnabled = false): int;

    /**
     * Sync lists with Mailman 3 API.
     *
     * @param  array<string, mixed>  $mailmanLists
     * @return array<string, mixed>
     */
    public function syncWithMailman(int $domainId, array $mailmanLists): array;

    /**
     * Get lists for Exim router lookup.
     *
     * @return array<string, mixed>|null
     */
    public function getForEximRouter(string $email): ?array;
}
