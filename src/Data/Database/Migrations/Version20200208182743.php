<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200208182743 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE achievements (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', name LONGTEXT NOT NULL, display_order INT DEFAULT NULL, svg LONGTEXT NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_achievements (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', achievement_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', collected_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', uuid VARCHAR(255) NOT NULL, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_51EE02FCB3EC99FE (achievement_id), INDEX IDX_51EE02FCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_achievements ADD CONSTRAINT FK_51EE02FCB3EC99FE FOREIGN KEY (achievement_id) REFERENCES achievements (id)');
        $this->addSql('ALTER TABLE user_achievements ADD CONSTRAINT FK_51EE02FCA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE player_ranks DROP emblem_svg');
        $this->addSql('ALTER TABLE users DROP colour');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_achievements DROP FOREIGN KEY FK_51EE02FCB3EC99FE');
        $this->addSql('DROP TABLE achievements');
        $this->addSql('DROP TABLE user_achievements');
        $this->addSql('ALTER TABLE player_ranks ADD emblem_svg LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE users ADD colour VARCHAR(6) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
