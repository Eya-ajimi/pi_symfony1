<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250404234844 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires RENAME INDEX idx_67f068bc4b89032c TO IDX_D9BEC0C44B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires RENAME INDEX idx_67f068bcfb88e14f TO IDX_D9BEC0C4FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires RENAME INDEX idx_27c91b8fba9cd190 TO IDX_662E98A1BA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires RENAME INDEX idx_27c91b8ffb88e14f TO IDX_662E98A1FB88E14F
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires RENAME INDEX idx_d9bec0c44b89032c TO IDX_67F068BC4B89032C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE commentaires RENAME INDEX idx_d9bec0c4fb88e14f TO IDX_67F068BCFB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires RENAME INDEX idx_662e98a1ba9cd190 TO IDX_27C91B8FBA9CD190
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_commentaires RENAME INDEX idx_662e98a1fb88e14f TO IDX_27C91B8FFB88E14F
        SQL);
    }
}
