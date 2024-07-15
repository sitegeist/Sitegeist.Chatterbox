<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Thread;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Doctrine\DBAL\Types\Types;
use Neos\Flow\Annotations as Flow;

class ThreadRecordRegistry
{
    private const TABLE_NAME = 'sitegeist_chatterbox_domain_thread_record';

    public function __construct(
        private readonly DatabaseConnection $databaseConnection,
    ) {
    }

    public function findAll(): ThreadRecords
    {
        $databaseRows = $this->databaseConnection->executeQuery(
            'SELECT * FROM '. self::TABLE_NAME . ' ORDER BY date_created DESC',
        )->fetchAllAssociative();

        return new ThreadRecords(...array_map(
            fn (array $databaseRow): ThreadRecord => $this->mapDatabaseRowToTheadRecord($databaseRow),
            $databaseRows,
        ));
    }

    public function findByAssistant(string $assistantId): ThreadRecords
    {
        $databaseRows = $this->databaseConnection->executeQuery(
            'SELECT * FROM '. self::TABLE_NAME . ' WHERE assistant_id = :assistantId ORDER BY date_created DESC',
            [
                'assistantId' => $assistantId
            ],
        )->fetchAllAssociative();

        return new ThreadRecords(...array_map(
            fn (array $databaseRow): ThreadRecord => $this->mapDatabaseRowToTheadRecord($databaseRow),
            $databaseRows,
        ));
    }

    public function findById(ThreadId $threadId): ?ThreadRecord
    {
        $databaseRow = $this->databaseConnection->executeQuery(
            'SELECT * FROM '. self::TABLE_NAME . ' WHERE thread_id = :threadId',
            [
                'threadId' => $threadId->value
            ]
        )->fetchAssociative();

        return $databaseRow
            ? $this->mapDatabaseRowToTheadRecord($databaseRow)
            : null;
    }

    public function registerThread(ThreadId $threadId, string $assistantId, \DateTimeImmutable $dateCreated): void
    {
        $this->databaseConnection->insert(
            self::TABLE_NAME,
            [
                'thread_id' => $threadId->value,
                'assistant_id' => $assistantId,
                'date_created' => $dateCreated->getTimestamp(),
            ],
            [
                'date_created' => Types::BIGINT
            ]
        );
    }

    public function unregisterThread(ThreadId $threadId): void
    {
        $this->databaseConnection->delete(
            self::TABLE_NAME,
            [
                'thread_id' => $threadId->value,
            ]
        );
    }

    /**
     * @param array<string,string> $databaseRow
     */
    private function mapDatabaseRowToTheadRecord(array $databaseRow): ThreadRecord
    {
        return new ThreadRecord(
            new ThreadId($databaseRow['threadId']),
            new \DateTimeImmutable('@' . $databaseRow['dateCreated']),
        );
    }
}
