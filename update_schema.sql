ALTER TABLE products 
ADD COLUMN sku VARCHAR(50) NULL,
ADD COLUMN stock INT DEFAULT 0 NOT NULL,
ADD COLUMN status VARCHAR(20) DEFAULT 'active' NOT NULL,
ADD COLUMN featured TINYINT(1) DEFAULT 0 NOT NULL;
