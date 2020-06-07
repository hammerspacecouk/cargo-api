<?php

declare(strict_types=1);

namespace App\Data\Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200607133132 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE purchases (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid_binary)\', user_id BINARY(16) DEFAULT NULL COMMENT \'(DC2Type:uuid_binary)\', checkout_session_id VARCHAR(191) NOT NULL, product_id VARCHAR(191) NOT NULL, cost INT NOT NULL, vat INT NOT NULL, uuid VARCHAR(255) NOT NULL, created_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', updated_at DATETIME(6) NOT NULL COMMENT \'(DC2Type:datetime_microsecond)\', deleted_at DATETIME(6) DEFAULT NULL COMMENT \'(DC2Type:datetime_microsecond)\', INDEX IDX_AA6431FEA76ED395 (user_id), INDEX purhcase_checkout_id (checkout_session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE purchases ADD CONSTRAINT FK_AA6431FEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE purchases');
    }
}
