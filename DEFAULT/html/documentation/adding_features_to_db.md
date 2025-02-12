ALTER TABLE products ADD FULLTEXT(name);
ALTER TABLE products ADD FULLTEXT(tags);
ALTER TABLE product_translations ADD FULLTEXT(name);
ALTER TABLE product_stocks ADD FULLTEXT(sku);
