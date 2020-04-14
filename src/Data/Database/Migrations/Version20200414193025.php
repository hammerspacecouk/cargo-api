<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200414193025 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ports DROP FOREIGN KEY FK_899FD0CDC36A3328');
        $this->addSql('DROP TABLE clusters');
        $this->addSql('DROP INDEX IDX_899FD0CDC36A3328 ON ports');
        $this->addSql('ALTER TABLE ports DROP cluster_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE clusters (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', name VARCHAR(191) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, uuid VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', UNIQUE INDEX UNIQ_EC895D3F5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE ports ADD cluster_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE ports ADD CONSTRAINT FK_899FD0CDC36A3328 FOREIGN KEY (cluster_id) REFERENCES clusters (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_899FD0CDC36A3328 ON ports (cluster_id)');
    }
}
