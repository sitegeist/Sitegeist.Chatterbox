<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105120757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('DROP TABLE sitegeist_chatterbox_domain_file_entry');
        $this->addSql('ALTER TABLE sitegeist_chatterbox_domain_knowledge_vectorstorereference DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE sitegeist_chatterbox_domain_knowledge_vectorstorereference DROP persistence_object_identifier');
        $this->addSql('ALTER TABLE sitegeist_chatterbox_domain_knowledge_vectorstorereference ADD PRIMARY KEY (account, context, knowledgesourceidentifier)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('CREATE TABLE sitegeist_chatterbox_domain_file_entry (id VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, knowledge_source_discriminator VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id, knowledge_source_discriminator)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sitegeist_chatterbox_domain_knowledge_vectorstorereference DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE sitegeist_chatterbox_domain_knowledge_vectorstorereference ADD persistence_object_identifier VARCHAR(40) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE sitegeist_chatterbox_domain_knowledge_vectorstorereference ADD PRIMARY KEY (persistence_object_identifier)');
    }
}
