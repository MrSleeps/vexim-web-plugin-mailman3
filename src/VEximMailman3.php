<?php

namespace VEximweb\Plugin\VEximMailman3;

use VEximweb\Plugin\VEximMailman3\Repositories\Interfaces\MailmanListRepositoryInterface;

class VEximMailman3
{
    protected MailmanInterface $mailman;

    protected MailmanListRepositoryInterface $repository;

    public function __construct(
        MailmanInterface $mailman,
        MailmanListRepositoryInterface $repository
    ) {
        $this->mailman = $mailman;
        $this->repository = $repository;
    }

    /**
     * Sync all lists from Mailman API to local database
     */
    public function syncLists(int $domainId): array
    {
        $mailmanLists = $this->mailman->lists();

        return $this->repository->syncWithMailman($domainId, $mailmanLists);
    }

    /**
     * Get a list by local ID with Mailman API data
     */
    public function getListWithApiData(int $listId): ?array
    {
        $list = $this->repository->findById($listId);
        if (! $list) {
            return null;
        }

        $apiData = $this->mailman->members($list->list_email);

        return [
            'local' => $list,
            'api' => $apiData,
        ];
    }

    /**
     * Create a new list both locally and in Mailman
     */
    public function createList(array $data): array
    {
        $mailmanCreated = $this->mailman->create_list($data['list_email']);

        if (! $mailmanCreated) {
            throw new \Exception('Failed to create list in Mailman');
        }

        $localList = $this->repository->create($data);

        return [
            'local' => $localList,
            'mailman_created' => $mailmanCreated,
        ];
    }

    /**
     * Subscribe a user to a list
     */
    public function subscribeUser(int $listId, string $userEmail, string $userName): bool
    {
        $list = $this->repository->findById($listId);
        if (! $list) {
            throw new \Exception('List not found');
        }

        return $this->mailman->subscribe(
            $list->list_email,
            $userName,
            $userEmail
        );
    }

    /**
     * Delegate method calls to the Mailman API client
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->mailman, $method)) {
            return $this->mailman->{$method}(...$parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
