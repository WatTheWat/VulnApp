-- Neil and Avi's Coffee Shop — seed data
-- Runs once on first start of an empty db volume (reset.sh wipes the volume).

USE coffeeshop;

CREATE TABLE menu_items (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80)  NOT NULL,
  description VARCHAR(255) NOT NULL,
  price       DECIMAL(5,2) NOT NULL,
  category    VARCHAR(30)  NOT NULL,
  available   TINYINT(1)   NOT NULL DEFAULT 1
);

-- INTENTIONALLY WEAK: unsalted MD5 so sqlmap's dictionary attack cracks it on stage.
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(40)  NOT NULL UNIQUE,
  email         VARCHAR(120) NOT NULL,
  password_hash CHAR(32)     NOT NULL,
  role          VARCHAR(20)  NOT NULL DEFAULT 'barista',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(60)  NOT NULL,
  item_id       INT          NOT NULL,
  size          VARCHAR(10)  NOT NULL DEFAULT 'Medium',
  notes         VARCHAR(140) NOT NULL DEFAULT '',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES menu_items(id)
);

-- Storefront content. The admin panel edits these; the defacement targets them.
CREATE TABLE settings (
  name  VARCHAR(40) PRIMARY KEY,
  value VARCHAR(255) NOT NULL
);

INSERT INTO menu_items (name, description, price, category) VALUES
('House Drip',              'Our everyday brew. Bottomless, because it is not real.',              2.50, 'Coffee'),
('Zero-Day Espresso',       'A double shot nobody has patched yet. Extremely strong.',              3.25, 'Espresso'),
('Null Byte Latte',         'Silky steamed milk that ends a little earlier than expected.',          4.50, 'Espresso'),
('Buffer Overflow Mocha',   'Extra chocolate, extra shot. Spills over the cup boundary.',            5.25, 'Espresso'),
('Port 443 Pour-Over',      'Encrypted in transit, hand-poured at rest.',                           4.75, 'Coffee'),
('Cold Boot Cold Brew',     'Steeped for 18 hours. Retains its memory after a reboot.',              4.25, 'Cold'),
('Cookie Session Frappe',   'Blended with cookies. HttpOnly, but you can still taste it.',           5.75, 'Cold'),
('Root Shell Cortado',      'Small, potent, and has access to everything.',                        3.75, 'Espresso'),
('Honeypot Oat Latte',      'Sweetened with honey. Suspiciously inviting.',                         5.00, 'Espresso'),
('Firewall Chai',           'Spiced black tea. Lets the warmth in, keeps the cold out.',            4.25, 'Tea'),
('Packet Loss Matcha',      'Ceremonial-grade matcha. Some sips may not arrive.',                   5.50, 'Tea'),
('Hot Cocoa (Dev Build)',   'Untested in production. Marshmallows not guaranteed.',                  3.50, 'Other');

-- neil / cappuccino  -> in sqlmap's default wordlist, cracks on stage
-- avi  / strong pass -> NOT in the wordlist, stays uncracked (good talking point)
INSERT INTO users (username, email, password_hash, role) VALUES
('neil',     'neil@neilandavis.coffee',     MD5('cappuccino'),            'admin'),
('avi',      'avi@neilandavis.coffee',      MD5('Av1!Roast-Mast3r#2026'), 'admin'),
('jordan',   'jordan@neilandavis.coffee',   MD5('latte'),                 'barista'),
('sam',      'sam@neilandavis.coffee',      MD5('sunshine'),              'barista'),
('riley',    'riley@neilandavis.coffee',    MD5('Tr1ple-Sh0t!'),          'barista');

INSERT INTO orders (customer_name, item_id, size, notes) VALUES
('Priya',   3, 'Large',  'oat milk please'),
('Marcus',  2, 'Small',  ''),
('Elena',   6, 'Medium', 'extra ice'),
('Dev',     8, 'Small',  'for the pentest report'),
('Taylor', 11, 'Large',  '');

INSERT INTO settings (name, value) VALUES
('headline',      'Fresh virtual coffee, brewed in the cloud.'),
('tagline',       'Zero calories. Zero caffeine. One hundred percent packets.'),
('announcement',  'New this week: the Honeypot Oat Latte. Irresistible, by design.'),
('theme',         'normal');
