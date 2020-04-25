<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200425150650 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dictionary ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE achievements ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE active_effects ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE authentication_tokens ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE channels ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE config ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE crates ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE crate_locations ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE crate_types ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE effects ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE events ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE hints ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE player_ranks ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE ports ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE port_visits ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE rank_achievements ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE ships ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE ship_classes ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE ship_locations ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE used_action_tokens ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE users ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE user_achievements ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
        $this->addSql('ALTER TABLE user_effects ADD deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE achievements DROP deleted_at');
        $this->addSql('ALTER TABLE active_effects DROP deleted_at');
        $this->addSql('ALTER TABLE authentication_tokens DROP deleted_at');
        $this->addSql('ALTER TABLE channels DROP deleted_at');
        $this->addSql('ALTER TABLE config DROP deleted_at');
        $this->addSql('ALTER TABLE crate_locations DROP deleted_at');
        $this->addSql('ALTER TABLE crate_types DROP deleted_at');
        $this->addSql('ALTER TABLE crates DROP deleted_at');
        $this->addSql('ALTER TABLE dictionary DROP deleted_at');
        $this->addSql('ALTER TABLE effects DROP deleted_at');
        $this->addSql('ALTER TABLE events DROP deleted_at');
        $this->addSql('ALTER TABLE hints DROP deleted_at');
        $this->addSql('ALTER TABLE player_ranks DROP deleted_at');
        $this->addSql('ALTER TABLE port_visits DROP deleted_at');
        $this->addSql('ALTER TABLE ports DROP deleted_at');
        $this->addSql('ALTER TABLE rank_achievements DROP deleted_at');
        $this->addSql('ALTER TABLE ship_classes DROP deleted_at');
        $this->addSql('ALTER TABLE ship_locations DROP deleted_at');
        $this->addSql('ALTER TABLE ships DROP deleted_at');
        $this->addSql('ALTER TABLE used_action_tokens DROP deleted_at');
        $this->addSql('ALTER TABLE user_achievements DROP deleted_at');
        $this->addSql('ALTER TABLE user_effects DROP deleted_at');
        $this->addSql('ALTER TABLE users DROP deleted_at');
    }
}
