<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251118222557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carts ADD final_amount NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER INDEX idx_fc5702b877153098 RENAME TO idx_discount_code');
        $this->addSql('ALTER INDEX idx_fc5702b81b5771dd RENAME TO idx_discount_active');
        $this->addSql('ALTER INDEX idx_fc5702b87bd7dfb6 RENAME TO idx_discount_valid_from');
        $this->addSql('ALTER INDEX idx_fc5702b8fdef8f78 RENAME TO idx_discount_valid_until');
        $this->addSql('CREATE INDEX IDX_3967A2165F37A13B ON password_reset_tokens (token)');
        $this->addSql('CREATE INDEX IDX_3967A216F9D83E2 ON password_reset_tokens (expires_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_3967A2165F37A13B');
        $this->addSql('DROP INDEX IDX_3967A216F9D83E2');
        $this->addSql('ALTER TABLE carts DROP final_amount');
        $this->addSql('ALTER INDEX idx_discount_active RENAME TO idx_fc5702b81b5771dd');
        $this->addSql('ALTER INDEX idx_discount_code RENAME TO idx_fc5702b877153098');
        $this->addSql('ALTER INDEX idx_discount_valid_from RENAME TO idx_fc5702b87bd7dfb6');
        $this->addSql('ALTER INDEX idx_discount_valid_until RENAME TO idx_fc5702b8fdef8f78');
    }
}
