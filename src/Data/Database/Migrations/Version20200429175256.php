<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200429175256 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ports ADD blockaded_by_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', ADD blockaded_until DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE ports ADD CONSTRAINT FK_899FD0CDD7BC5D8A FOREIGN KEY (blockaded_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_899FD0CDD7BC5D8A ON ports (blockaded_by_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ports DROP FOREIGN KEY FK_899FD0CDD7BC5D8A');
        $this->addSql('DROP INDEX IDX_899FD0CDD7BC5D8A ON ports');
        $this->addSql('ALTER TABLE ports DROP blockaded_by_id, DROP blockaded_until');
    }
}
