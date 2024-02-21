<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add / remove applied migration database structure
 */
class Version20230209161525 extends AbstractMigration
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

        $this->addSql('CREATE TABLE sitegeist_chatterbox_domain_file_entry (id varchar(64) NOT NULL, knowledge_source_discriminator varchar(255) NOT NULL, content MEDIUMTEXT NOT NULL, PRIMARY KEY(id, knowledge_source_discriminator)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('DROP TABLE sitegeist_chatterbox_domain_file_entry');
    }
}
