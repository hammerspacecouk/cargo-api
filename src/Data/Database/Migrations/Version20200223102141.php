<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200223102141 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE users ADD nickname TINYTEXT DEFAULT NULL, ADD game_start_date_time DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', ADD game_completion_time INT DEFAULT NULL');
        $this->addSql('CREATE INDEX user_reddit ON users (reddit_id)');
        $this->addSql('CREATE INDEX user_completion_time ON users (game_completion_time)');

        $this->addSql('UPDATE users SET game_start_date_time = created_at');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX user_reddit ON users');
        $this->addSql('DROP INDEX user_completion_time ON users');
        $this->addSql('ALTER TABLE users DROP nickname, DROP game_start_date_time, DROP game_completion_time');
    }
}
