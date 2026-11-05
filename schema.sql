SET NAMES utf8mb4;
CREATE TABLE categories(id INT AUTO_INCREMENT PRIMARY KEY,name VARCHAR(80) NOT NULL,icon VARCHAR(8) DEFAULT '🌾')ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE products(id INT AUTO_INCREMENT PRIMARY KEY,category_id INT DEFAULT NULL,name VARCHAR(150) NOT NULL,size VARCHAR(50) DEFAULT '',price INT NOT NULL,stock INT NOT NULL DEFAULT 0,description TEXT,image VARCHAR(60) DEFAULT NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE orders(id INT AUTO_INCREMENT PRIMARY KEY,token CHAR(24) NOT NULL UNIQUE,user_id INT DEFAULT NULL,order_no VARCHAR(30) NOT NULL,status ENUM('pending','confirmed','paid','verified','shipped','rejected') NOT NULL DEFAULT 'pending',note VARCHAR(255) DEFAULT NULL,method VARCHAR(10) DEFAULT NULL,cust_name VARCHAR(120) DEFAULT NULL,phone VARCHAR(30) DEFAULT NULL,addr TEXT,slip VARCHAR(60) DEFAULT NULL,carrier VARCHAR(60) DEFAULT NULL,tracking VARCHAR(60) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,paid_at DATETIME DEFAULT NULL,shipped_at DATETIME DEFAULT NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE order_items(id INT AUTO_INCREMENT PRIMARY KEY,order_id INT NOT NULL,product_id INT NOT NULL,name VARCHAR(200) NOT NULL,price INT NOT NULL,qty INT NOT NULL,INDEX(order_id),FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE admins(id INT AUTO_INCREMENT PRIMARY KEY,username VARCHAR(50) NOT NULL UNIQUE,pw VARCHAR(255) NOT NULL)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE settings(k VARCHAR(40) PRIMARY KEY,v TEXT)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE users(id INT AUTO_INCREMENT PRIMARY KEY,phone VARCHAR(15) NOT NULL UNIQUE,name VARCHAR(120) DEFAULT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE otp_codes(id INT AUTO_INCREMENT PRIMARY KEY,phone VARCHAR(15) NOT NULL,code_hash CHAR(64) NOT NULL,expires_at DATETIME NOT NULL,attempts INT NOT NULL DEFAULT 0,used TINYINT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX(phone))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE banners(id INT AUTO_INCREMENT PRIMARY KEY,image VARCHAR(60) NOT NULL,title VARCHAR(150) DEFAULT '',subtitle VARCHAR(255) DEFAULT '',link VARCHAR(255) DEFAULT '',sort_order INT NOT NULL DEFAULT 0,active TINYINT NOT NULL DEFAULT 1)ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE notifications(
  id INT AUTO_INCREMENT PRIMARY KEY,
  target ENUM('admin','user') NOT NULL,
  user_id INT DEFAULT NULL,
  message VARCHAR(255) NOT NULL,
  link VARCHAR(255) DEFAULT '',
  is_read TINYINT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(target,user_id,is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO categories(name,icon) VALUES('ข้าวหอมมะลิ','🍚'),('ข้าวเหนียว','🌾'),('ข้าวขาว','🥣'),('ข้าวอินทรีย์','🌱');
INSERT INTO products(category_id,name,size,price,stock,description) VALUES
(1,'ข้าวหอมมะลิคุณภาพพรีเมียม','5 กก.',250,50,'ข้าวหอมมะลิ 100% จากเกษตรกรไทย หอม นุ่ม อร่อย'),
(2,'ข้าวเหนียว','5 กก.',220,30,'ข้าวเหนียวเม็ดสวย นึ่งแล้วนุ่มเหนียว'),
(3,'ข้าวขาว','5 กก.',200,40,'ข้าวขาวคัดพิเศษ เหมาะกับทุกครัวเรือน'),
(4,'ข้าวอินทรีย์','5 กก.',280,3,'ข้าวอินทรีย์ปลอดสารเคมี'),
(1,'ข้าวหอมมะลิคุณภาพพรีเมียม','10 กก.',480,20,'ข้าวหอมมะลิ 100% ถุงใหญ่คุ้มกว่า'),
(2,'ข้าวเหนียวเขี้ยวงู','1 กก.',60,25,'ข้าวเหนียวเขี้ยวงู เม็ดยาว');
INSERT INTO settings(k,v) VALUES('shop_name','RiceShop'),('phone','081-234-5678'),('line','@riceshop'),('bank','ธนาคารกสิกรไทย 123-4-56789-0 ชื่อบัญชี ร้านข้าวดี'),('qr','');
