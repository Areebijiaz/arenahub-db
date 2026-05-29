-- ArenaHub Database - MySQL Version
-- Run this entire file in phpMyAdmin

-- 1. DROP AND CREATE DATABASE
DROP DATABASE IF EXISTS arenahub;
CREATE DATABASE arenahub;
USE arenahub;

-- 2. CREATE TABLES
CREATE TABLE admin (
    adminid INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customer (
    customerid INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    idcard VARCHAR(50) UNIQUE,
    phone VARCHAR(20),
    email VARCHAR(100),
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    adminid INT,
    FOREIGN KEY (adminid) REFERENCES admin(adminid)
);

CREATE TABLE pc (
    pcid INT PRIMARY KEY AUTO_INCREMENT,
    pc_name VARCHAR(50),
    hourlyrate DECIMAL(8,2),
    status VARCHAR(20) DEFAULT 'available',
    specifications TEXT
);

CREATE TABLE game (
    gameid INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(150),
    genre VARCHAR(80),
    price DECIMAL(8,2),
    pcid INT,
    FOREIGN KEY (pcid) REFERENCES pc(pcid) ON DELETE SET NULL
);

CREATE TABLE session (
    sessionid INT PRIMARY KEY AUTO_INCREMENT,
    starttime DATETIME NOT NULL,
    endtime DATETIME NULL,
    total_hours DECIMAL(5,2),
    customerid INT,
    pcid INT,
    adminid INT,
    FOREIGN KEY (customerid) REFERENCES customer(customerid),
    FOREIGN KEY (pcid) REFERENCES pc(pcid),
    FOREIGN KEY (adminid) REFERENCES admin(adminid)
);

CREATE TABLE bill (
    billid INT PRIMARY KEY AUTO_INCREMENT,
    amount DECIMAL(10,2),
    discount DECIMAL(5,2) DEFAULT 0,
    final_amount DECIMAL(10,2),
    paystatus VARCHAR(20) DEFAULT 'pending',
    payment_method VARCHAR(30),
    payment_date DATETIME,
    sessionid INT UNIQUE,
    FOREIGN KEY (sessionid) REFERENCES session(sessionid)
);

CREATE TABLE session_game (
    sessionid INT,
    gameid INT,
    PRIMARY KEY (sessionid, gameid),
    FOREIGN KEY (sessionid) REFERENCES session(sessionid) ON DELETE CASCADE,
    FOREIGN KEY (gameid) REFERENCES game(gameid) ON DELETE CASCADE
);

-- 3. INSERT SAMPLE DATA
INSERT INTO admin (username, password, email) VALUES 
('admin1', MD5('admin123'), 'admin@arenahub.com'),
('manager', MD5('manager123'), 'manager@gaming.com');

INSERT INTO customer (name, idcard, phone, email, adminid) VALUES 
('Ali Khan', '12345-1234567-1', '03001234567', 'ali@gmail.com', 1),
('Ahmed Raza', '98765-7654321-2', '03111234567', 'ahmed@gmail.com', 1),
('Sara Ahmed', '55555-5555555-5', '03331234567', 'sara@gmail.com', 1),
('Usman Malik', '11111-2222222-3', '03211234567', 'usman@gmail.com', 2);

INSERT INTO pc (pc_name, hourlyrate, status, specifications) VALUES 
('Gaming Rig 1', 200, 'busy', 'RTX 3060, i5 12th Gen, 16GB RAM'),
('Gaming Rig 2', 250, 'available', 'RTX 3070, i7 12th Gen, 32GB RAM'),
('Gaming Rig 3', 300, 'available', 'RTX 4080, i9 13th Gen, 64GB RAM'),
('Gaming Rig 4', 200, 'maintenance', 'RTX 3060, i5 12th Gen, 16GB RAM');

INSERT INTO game (title, genre, price, pcid) VALUES 
('PUBG: Battlegrounds', 'Battle Royale', 1500, 1),
('Valorant', 'FPS', 0, 2),
('FIFA 23', 'Sports', 2500, 2),
('Call of Duty', 'FPS', 3500, 3),
('Grand Theft Auto V', 'Action', 2000, 1);

INSERT INTO session (starttime, endtime, total_hours, customerid, pcid, adminid) VALUES 
('2026-04-26 10:00:00', '2026-04-26 12:00:00', 2, 1, 1, 1),
('2026-04-26 11:00:00', NULL, NULL, 2, 2, 1),
('2026-04-26 09:00:00', '2026-04-26 11:30:00', 2.5, 3, 3, 1),
('2026-04-26 14:00:00', NULL, NULL, 4, 2, 2);

INSERT INTO bill (amount, discount, final_amount, paystatus, payment_method, payment_date, sessionid) VALUES 
(400, 0, 400, 'paid', 'Cash', '2026-04-26 12:00:00', 1),
(625, 50, 575, 'paid', 'Card', '2026-04-26 11:30:00', 3),
(500, 0, 500, 'pending', NULL, NULL, 2);

INSERT INTO session_game (sessionid, gameid) VALUES 
(1, 1), (1, 5), (2, 2), (2, 3), (3, 4);

-- 4. CREATE FUNCTION
DROP FUNCTION IF EXISTS fn_session_cost;
DELIMITER $$
CREATE FUNCTION fn_session_cost(
    start_time DATETIME,
    end_time DATETIME,
    rate DECIMAL(8,2)
)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    RETURN (TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60.0) * rate;
END$$
DELIMITER ;

-- 5. CREATE STORED PROCEDURES
DROP PROCEDURE IF EXISTS StartSession;
DELIMITER $$
CREATE PROCEDURE StartSession(
    IN p_customerid INT,
    IN p_pcid INT,
    IN p_adminid INT
)
BEGIN
    DECLARE pc_status VARCHAR(20);
    
    SELECT status INTO pc_status FROM pc WHERE pcid = p_pcid;
    
    IF pc_status != 'available' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'PC is not available';
    ELSE
        UPDATE pc SET status = 'busy' WHERE pcid = p_pcid;
        INSERT INTO session (starttime, customerid, pcid, adminid)
        VALUES (NOW(), p_customerid, p_pcid, p_adminid);
        SELECT LAST_INSERT_ID() AS sessionid;
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS EndSession;
DELIMITER $$
CREATE PROCEDURE EndSession(
    IN p_sessionid INT
)
BEGIN
    DECLARE v_pcid INT;
    DECLARE v_start DATETIME;
    DECLARE v_rate DECIMAL(8,2);
    DECLARE v_hours DECIMAL(10,2);
    DECLARE v_amount DECIMAL(10,2);
    
    SELECT pcid, starttime INTO v_pcid, v_start
    FROM session WHERE sessionid = p_sessionid;
    
    SET v_hours = TIMESTAMPDIFF(MINUTE, v_start, NOW()) / 60.0;
    SELECT hourlyrate INTO v_rate FROM pc WHERE pcid = v_pcid;
    SET v_amount = v_hours * v_rate;
    
    UPDATE session SET endtime = NOW(), total_hours = v_hours
    WHERE sessionid = p_sessionid;
    
    UPDATE pc SET status = 'available' WHERE pcid = v_pcid;
    INSERT INTO bill (amount, final_amount, sessionid)
    VALUES (v_amount, v_amount, p_sessionid);
    
    SELECT p_sessionid AS sessionid, v_hours AS total_hours, v_amount AS amount;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS AddCustomer;
DELIMITER $$
CREATE PROCEDURE AddCustomer(
    IN p_name VARCHAR(100),
    IN p_idcard VARCHAR(50),
    IN p_phone VARCHAR(20),
    IN p_email VARCHAR(100),
    IN p_adminid INT
)
BEGIN
    INSERT INTO customer (name, idcard, phone, email, adminid)
    VALUES (p_name, p_idcard, p_phone, p_email, p_adminid);
    SELECT LAST_INSERT_ID() AS customerid;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS ProcessPayment;
DELIMITER $$
CREATE PROCEDURE ProcessPayment(
    IN p_billid INT,
    IN p_payment_method VARCHAR(30)
)
BEGIN
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_discount DECIMAL(5,2);
    
    SELECT amount, discount INTO v_amount, v_discount
    FROM bill WHERE billid = p_billid;
    
    UPDATE bill 
    SET paystatus = 'paid',
        payment_method = p_payment_method,
        payment_date = NOW(),
        final_amount = v_amount - v_discount
    WHERE billid = p_billid;
END$$
DELIMITER ;

-- 6. CREATE TRIGGERS
DROP TRIGGER IF EXISTS trg_bill_before_insert;
DELIMITER $$
CREATE TRIGGER trg_bill_before_insert
BEFORE INSERT ON bill
FOR EACH ROW
BEGIN
    SET NEW.final_amount = NEW.amount - NEW.discount;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_bill_before_update;
DELIMITER $$
CREATE TRIGGER trg_bill_before_update
BEFORE UPDATE ON bill
FOR EACH ROW
BEGIN
    SET NEW.final_amount = NEW.amount - NEW.discount;
END$$
DELIMITER ;

-- 7. CREATE VIEWS
CREATE OR REPLACE VIEW active_sessions_view AS
SELECT 
    s.sessionid,
    c.name AS customer_name,
    p.pc_name,
    p.hourlyrate,
    s.starttime,
    TIMESTAMPDIFF(MINUTE, s.starttime, NOW()) AS minutes_elapsed
FROM session s
JOIN customer c ON s.customerid = c.customerid
JOIN pc p ON s.pcid = p.pcid
WHERE s.endtime IS NULL;

CREATE OR REPLACE VIEW customer_bill_view AS
SELECT 
    c.name,
    c.phone,
    b.amount,
    b.discount,
    b.final_amount,
    b.paystatus,
    b.payment_date,
    s.starttime,
    s.endtime
FROM customer c
JOIN session s ON c.customerid = s.customerid
JOIN bill b ON s.sessionid = b.sessionid;

CREATE OR REPLACE VIEW daily_revenue_view AS
SELECT 
    DATE(payment_date) AS sale_date,
    COUNT(*) AS transactions,
    SUM(final_amount) AS total_revenue,
    AVG(final_amount) AS average_bill
FROM bill
WHERE paystatus = 'paid'
GROUP BY DATE(payment_date)
ORDER BY sale_date DESC;

-- 8. BACKUP TABLES
CREATE TABLE IF NOT EXISTS customer_backup LIKE customer;
CREATE TABLE IF NOT EXISTS session_backup LIKE session;

-- 9. SAMPLE QUERIES FOR TESTING

-- Total Revenue
SELECT SUM(final_amount) AS total_revenue FROM bill WHERE paystatus = 'paid';

-- Average Bill
SELECT AVG(final_amount) AS average_bill FROM bill WHERE paystatus = 'paid';

-- Most Active Customers
SELECT 
    c.name,
    COUNT(s.sessionid) AS total_sessions,
    COALESCE(SUM(b.final_amount), 0) AS total_spent
FROM customer c
JOIN session s ON c.customerid = s.customerid
LEFT JOIN bill b ON s.sessionid = b.sessionid
GROUP BY c.customerid, c.name
ORDER BY total_sessions DESC
LIMIT 5;

-- Most Used PCs
SELECT 
    p.pc_name,
    COUNT(s.sessionid) AS usage_count,
    COALESCE(AVG(s.total_hours), 0) AS avg_session_hours
FROM pc p
LEFT JOIN session s ON p.pcid = s.pcid
GROUP BY p.pcid, p.pc_name
ORDER BY usage_count DESC;

-- Popular Games
SELECT 
    g.title,
    g.genre,
    COUNT(sg.sessionid) AS times_played
FROM game g
LEFT JOIN session_game sg ON g.gameid = sg.gameid
GROUP BY g.gameid, g.title, g.genre
ORDER BY times_played DESC;

-- Test function
SELECT fn_session_cost('2026-04-26 10:00:00', '2026-04-26 12:00:00', 200) AS test_cost;

-- Check all data
SELECT COUNT(*) AS total_admins FROM admin;
SELECT COUNT(*) AS total_customers FROM customer;
SELECT COUNT(*) AS total_pcs FROM pc;
SELECT COUNT(*) AS total_games FROM game;
SELECT COUNT(*) AS total_sessions FROM session;
SELECT COUNT(*) AS total_bills FROM bill;
SELECT COUNT(*) AS total_session_games FROM session_game;

-- Final message
SELECT 'Database setup completed successfully!' AS status;