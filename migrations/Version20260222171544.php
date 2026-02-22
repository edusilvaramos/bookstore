<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222171544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, cart_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_F0FE25271AD5CDBF (cart_id), INDEX IDX_F0FE252716A2B381 (book_id), UNIQUE INDEX uniq_cart_item_cart_book (cart_id, book_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252716A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO cart_item (cart_id, book_id, quantity) SELECT cb.cart_id, cb.book_id, CASE WHEN c.quantity IS NULL OR c.quantity < 1 THEN 1 ELSE c.quantity END FROM cart_book cb INNER JOIN cart c ON c.id = cb.cart_id');
        $this->addSql('ALTER TABLE cart_book DROP FOREIGN KEY `FK_2400A30816A2B381`');
        $this->addSql('ALTER TABLE cart_book DROP FOREIGN KEY `FK_2400A3081AD5CDBF`');
        $this->addSql('DROP TABLE cart_book');
        $this->addSql('ALTER TABLE cart DROP quantity');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart ADD quantity INT NOT NULL DEFAULT 1');
        $this->addSql('CREATE TABLE cart_book (cart_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_2400A30816A2B381 (book_id), INDEX IDX_2400A3081AD5CDBF (cart_id), PRIMARY KEY (cart_id, book_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cart_book ADD CONSTRAINT `FK_2400A30816A2B381` FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_book ADD CONSTRAINT `FK_2400A3081AD5CDBF` FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO cart_book (cart_id, book_id) SELECT ci.cart_id, ci.book_id FROM cart_item ci');
        $this->addSql('UPDATE cart c INNER JOIN (SELECT cart_id, MAX(quantity) AS quantity FROM cart_item GROUP BY cart_id) data ON data.cart_id = c.id SET c.quantity = data.quantity');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE252716A2B381');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('ALTER TABLE cart ALTER quantity DROP DEFAULT');
    }
}
