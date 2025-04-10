<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409001422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback ADD utilisateur_id INT NOT NULL, ADD shop_id INT NOT NULL, ADD rating INT NOT NULL, ADD date_feedback DATETIME NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback ADD CONSTRAINT FK_D2294458FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback ADD CONSTRAINT FK_D22944584D16C4DD FOREIGN KEY (shop_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D2294458FB88E14F ON feedback (utilisateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D22944584D16C4DD ON feedback (shop_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458FB88E14F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback DROP FOREIGN KEY FK_D22944584D16C4DD
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D2294458FB88E14F ON feedback
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D22944584D16C4DD ON feedback
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE feedback DROP utilisateur_id, DROP shop_id, DROP rating, DROP date_feedback
        SQL);
    }
}
