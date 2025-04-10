<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409175035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27A5849B6F
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_29A5EC27A5849B6F ON produit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit CHANGE promotion_id promotionId INT DEFAULT NULL, CHANGE shop_owner_id shopId INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27C9E63C48 FOREIGN KEY (shopId) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_29A5EC27C9E63C48 ON produit (shopId)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27C9E63C48
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_29A5EC27C9E63C48 ON produit
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit CHANGE shopId shop_owner_id INT NOT NULL, CHANGE promotionId promotion_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27A5849B6F FOREIGN KEY (shop_owner_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_29A5EC27A5849B6F ON produit (shop_owner_id)
        SQL);
    }
}
