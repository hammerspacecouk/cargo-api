<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200515201449 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rank_achievements DROP FOREIGN KEY FK_6EB0098A76ED395');
        $this->addSql('DROP INDEX IDX_6EB0098A76ED395 ON rank_achievements');
        $this->addSql('ALTER TABLE rank_achievements DROP user_id');
        $this->addSql('ALTER TABLE used_action_tokens CHANGE expiry expiry DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E937D48290 ON users (anonymous_ip_hash)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE rank_achievements ADD user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\'');
        $this->addSql('ALTER TABLE rank_achievements ADD CONSTRAINT FK_6EB0098A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6EB0098A76ED395 ON rank_achievements (user_id)');
        $this->addSql('ALTER TABLE used_action_tokens CHANGE expiry expiry DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('DROP INDEX UNIQ_1483A5E937D48290 ON users');
    }
}
