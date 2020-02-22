<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200212162445 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE rank_achievements (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', rank_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', achievement_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', uuid VARCHAR(255) NOT NULL, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_6EB00987616678F (rank_id), INDEX IDX_6EB0098B3EC99FE (achievement_id), INDEX IDX_6EB0098A76ED395 (user_id), UNIQUE INDEX rankach_unique (rank_id, achievement_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE rank_achievements ADD CONSTRAINT FK_6EB00987616678F FOREIGN KEY (rank_id) REFERENCES player_ranks (id)');
        $this->addSql('ALTER TABLE rank_achievements ADD CONSTRAINT FK_6EB0098B3EC99FE FOREIGN KEY (achievement_id) REFERENCES achievements (id)');
        $this->addSql('ALTER TABLE rank_achievements ADD CONSTRAINT FK_6EB0098A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX userach_unique ON user_achievements (user_id, achievement_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE rank_achievements');
        $this->addSql('DROP INDEX userach_unique ON user_achievements');
    }
}
