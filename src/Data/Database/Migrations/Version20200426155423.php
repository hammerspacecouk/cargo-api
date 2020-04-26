<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200426155423 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ships CHANGE original_purchase_cost original_purchase_cost INT NOT NULL');
        $this->addSql('DROP INDEX user_completion_time ON users');
        $this->addSql('ALTER TABLE users ADD best_completion_time INT DEFAULT NULL');
        $this->addSql('CREATE INDEX user_completion_time ON users (best_completion_time)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ships CHANGE original_purchase_cost original_purchase_cost INT DEFAULT 0 NOT NULL');
        $this->addSql('DROP INDEX user_completion_time ON users');
        $this->addSql('ALTER TABLE users DROP best_completion_time');
        $this->addSql('CREATE INDEX user_completion_time ON users (game_completion_time)');
    }
}
