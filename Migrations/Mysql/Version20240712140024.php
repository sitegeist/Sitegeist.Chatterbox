<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add / remove applied migration database structure
 */
class Version20240712140024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('CREATE TABLE sitegeist_chatterbox_domain_thread_record (thread_id varchar(64) NOT NULL, assistant_id varchar(64) NOT NULL, date_created BIGINT NOT NULL, PRIMARY KEY(thread_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX date_created ON sitegeist_chatterbox_domain_thread_record (date_created)');
        $this->addSql('CREATE INDEX date_created_by_assistant ON sitegeist_chatterbox_domain_thread_record (assistant_id, date_created)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('DROP TABLE sitegeist_chatterbox_domain_thread_record');
    }
}
