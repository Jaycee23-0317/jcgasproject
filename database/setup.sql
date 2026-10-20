

-- DROP DATABASE IF EXISTS jcgasprojectdb;
-- CREATE DATABASE jcgasprojectdb;
-- USE jcgasprojectdb;

DROP DATABASE IF EXISTS jcgasprojectdbtest;
CREATE DATABASE jcgasprojectdbtest;
USE jcgasprojectdbtest;

-- 1. BRANCHES TABLE
CREATE TABLE IF NOT EXISTS branches (
    BranchID INT AUTO_INCREMENT PRIMARY KEY,
    BranchName VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NULL,
    Address VARCHAR(255),
    ContactNumber VARCHAR(20),
    Latitude DECIMAL(10, 8) NULL,   -- Set via geocoding admin branch creation
    Longitude DECIMAL(11, 8) NULL,  -- Set via geocoding admin branch creation
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. USERS TABLE
CREATE TABLE IF NOT EXISTS `user` (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    FullName VARCHAR(100) NOT NULL,
    Username VARCHAR(50) NOT NULL UNIQUE,
    Address VARCHAR(255),
    Password VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Cashier', 'Customer') DEFAULT 'Customer',
    BranchID INT NULL,               -- Ties cashiers directly to their base branch
    Email VARCHAR(100) NULL UNIQUE,  -- Gmail account used for OTP verification
    ContactNumber VARCHAR(20) NULL,  -- Customer contact number
    EmailVerified TINYINT(1) DEFAULT 0, -- 1 once Gmail OTP is verified
    Latitude DECIMAL(10, 8) NULL,   -- Default user home location coordinates
    Longitude DECIMAL(11, 8) NULL,  -- Default user home location coordinates
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (BranchID) REFERENCES branches(BranchID) ON DELETE SET NULL
);

-- 2b. OTP VERIFICATION TABLE (temporary codes for Gmail verification during registration)
CREATE TABLE IF NOT EXISTS otp_verifications (
    OtpID INT AUTO_INCREMENT PRIMARY KEY,    -- NULL until Step 2 (Gmail verified first)
    Email VARCHAR(100) NOT NULL,
    OtpCode VARCHAR(10) NOT NULL,
    ExpiresAt DATETIME NOT NULL,
    Used TINYINT(1) DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_email (Email)
);

-- 3. PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS products (
    ProductID INT AUTO_INCREMENT PRIMARY KEY,
    Brand VARCHAR(100) NOT NULL,
    ProductName VARCHAR(100) NOT NULL,
    Size VARCHAR(100) NOT NULL,
    ImageName VARCHAR(255) NULL,
    ImageFile LONGBLOB NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. BRANCH INVENTORY MANAGEMENT
CREATE TABLE IF NOT EXISTS branch_inventory (
    InventoryID INT AUTO_INCREMENT PRIMARY KEY,
    BranchID INT NOT NULL,
    ProductID INT NOT NULL,
    Stock INT NOT NULL DEFAULT 0,
    Price DECIMAL(10,2) NOT NULL,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- PRIMARY KEY (BranchID, ProductID),
    FOREIGN KEY (BranchID) REFERENCES branches(BranchID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES products(ProductID) ON DELETE CASCADE
);

-- 5. ORDER HEADER TABLE (Optimized with Geolocation and Stock Management Statuses)
CREATE TABLE IF NOT EXISTS `order` (
    OrderGroupID INT AUTO_INCREMENT PRIMARY KEY,
    OrderID VARCHAR(20) NOT NULL,
    TransactionID VARCHAR(20) NOT NULL UNIQUE,
    UserID INT NOT NULL,
    BranchID INT NULL COMMENT 'Assigned to the nearest branch handling local dispatch',
    OrderType ENUM('Online', 'Walk-in') DEFAULT 'Online',
    -- Custom tracking workflow mapping customer notification updates
    Status ENUM('Pending Review', 'Order Placed', 'Waiting On Stock', 'Out for Delivery', 'Completed', 'Cancelled') DEFAULT 'Pending Review',
    CancelReason VARCHAR(255) NULL,
    CancelledBy INT NULL,
    CancelDate TIMESTAMP NULL DEFAULT NULL,
    PaymentMethod ENUM('CASH', 'CASH ON DELIVERY', 'GCASH', 'PAYMAYA') NULL DEFAULT NULL,
    CustomerName VARCHAR(100) NULL,
    DeliveryAddress VARCHAR(255) NULL COMMENT 'Complete delivery address text chosen at checkout',
    DeliveryLatitude DECIMAL(10, 8) NULL COMMENT 'Snapshot of customer exact location when placing order',
    DeliveryLongitude DECIMAL(11, 8) NULL COMMENT 'Snapshot of customer exact location when placing order',
    TotalAmount DECIMAL(10,2) NOT NULL DEFAULT 0,
    OrderDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Optimized Performance Indexes for Fast Sorting and Fetch Loops
    INDEX idx_status (Status),
    INDEX idx_user (UserID),
    INDEX idx_branch (BranchID),
    FOREIGN KEY (UserID) REFERENCES `user`(UserID),
    FOREIGN KEY (BranchID) REFERENCES branches(BranchID) ON DELETE SET NULL,
    FOREIGN KEY (CancelledBy) REFERENCES `user`(UserID) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS activity_logs (
    LogID BIGINT AUTO_INCREMENT PRIMARY KEY,
    Action VARCHAR(50) NOT NULL,
    EntityType VARCHAR(50) NOT NULL,
    EntityID INT NULL,
    ActorUserID INT NULL,
    BranchID INT NULL,
    Description VARCHAR(500) NULL,
    OldData JSON NULL,
    NewData JSON NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (Action),
    INDEX idx_entity (EntityType, EntityID),
    INDEX idx_actor (ActorUserID),
    INDEX idx_branch (BranchID),
    INDEX idx_created_at (CreatedAt),
    FOREIGN KEY (ActorUserID)
        REFERENCES `user`(UserID)
        ON DELETE SET NULL,
    FOREIGN KEY (BranchID)
        REFERENCES branches(BranchID)
        ON DELETE SET NULL
);

-- 6. ORDER LINE ITEMS
CREATE TABLE IF NOT EXISTS order_items (
    ItemID INT AUTO_INCREMENT PRIMARY KEY,
    OrderGroupID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    UnitPrice DECIMAL(10,2) NOT NULL,
    Subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (OrderGroupID) REFERENCES `order`(OrderGroupID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES products(ProductID) ON DELETE RESTRICT,
    INDEX idx_order (OrderGroupID),
    INDEX idx_product (ProductID)
);

-- =========================================================
-- SEED DATA UPDATE WITH GEOLOCATION COORDINATES (Angeles City, Pampanga Examples)
-- =========================================================

INSERT INTO branches (BranchName, Address, ContactNumber, Latitude, Longitude) VALUES
('JC Gas - Main Branch', '34-13, Missael Street, Diamond Subdivision, Balibago, Angeles, Central Luzon, 2009, Philippines', '09123456789', 15.159400, 120.595600),
('JC Gas - Porac Branch', 'San Vicente Street, Mining, Angeles, Central Luzon, 1992, Philippines', '09198765432', 15.143200, 120.609100);

INSERT INTO `user` (FullName, Username, Password, Role, BranchID, Email, ContactNumber) VALUES
('Admin User', 'admin', 'pass', 'Admin', NULL, 'jcgasproject@gmail.com', '09123456789'),
('Cashier Main', 'marlon', 'pass', 'Cashier', 1, NULL, '09123456789'),
('Cashier Porac', 'marlon2', 'pass', 'Cashier', 2, NULL, '09123456789');

INSERT INTO products (Brand, ProductName, Size) VALUES
('Super Kalan', 'Super Kalan 2.7KG', '2.7 KG'),
('SHINE GAZ', 'SHINE GAZ 2.7KG', '2.7 KG'),
('GASULLETTE', 'GASULLETTE 2.7KG', '2.7 KG'),

('REGASCO', 'REGASCO 5kg', '5 KG'),
('SHINE GAZ', 'SHINE GAZ 5kg', '5 KG'),
('ZENITH', 'ZENITH 5kg', '5 KG'),
('PRYCE GAS', 'PRYCE GAS 5kg', '5 KG'),

('PRYCE GAS', 'PRYCE GAS 11kg', '11 KG'),
('SHINE GAZ', 'SHINE GAZ 11kg', '11 KG'),
('ZENITH GAS', 'ZENITH GAS 11kg', '11 KG'),
('REGASCO', 'REGASCO 11kg', '11 KG'),
('GASUL', 'GASUL 11kg', '11 KG'),
('SOLANE', 'SOLANE 11kg', '11 KG'),

('REGASCO', 'REGASCO 22KG', '22 KG'),
('ZENITH', 'ZENITH 22KG', '22 KG'),

('PRYCE GAS', 'PRYCE GAS 50kg', '50 KG'),
('ZENITH GAS', 'ZENITH GAS 50kg', '50 KG'),
('REGASCO', 'REGASCO 50kg', '50 KG'),
('SOLANE', 'SOLANE 50kg', '50 KG'),
('GASUL', 'GASUL 50kg', '50 KG');

INSERT INTO branch_inventory (BranchID, ProductID, Stock, Price) VALUES
(1, 1, 50, 350.00), (1, 2, 50, 350.00), (1, 3, 50, 350.00), (1, 4, 50, 550.00), (1, 5, 50, 550.00), (1, 6, 50, 550.00), (1, 7, 50, 550.00), (1, 8, 50, 1100.00), (1, 9, 50, 1100.00), (1, 10, 50, 1100.00), (1, 11, 50, 1170.00), (1, 12, 50, 1450.00), (1, 13, 50, 1450.00), (1, 14, 50, 2200.00), (1, 15, 50, 2200.00), (1, 16, 50, 4400.00), (1, 17, 50, 4400.00), (1, 18, 50, 4800.00), (1, 19, 50, 4800.00), (1, 20, 50, 5000.00),
(2, 1, 50, 350.00), (2, 2, 50, 350.00), (2, 3, 50, 350.00), (2, 4, 50, 550.00), (2, 5, 50, 550.00), (2, 6, 50, 550.00), (2, 7, 50, 550.00), (2, 8, 50, 1100.00), (2, 9, 50, 1100.00), (2, 10, 50, 1100.00), (2, 11, 50, 1170.00), (2, 12, 50, 1450.00), (2, 13, 50, 1450.00), (2, 14, 50, 2200.00), (2, 15, 50, 2200.00), (2, 16, 50, 4400.00), (2, 17, 50, 4400.00), (2, 18, 50, 4800.00), (2, 19, 50, 4800.00), (2, 20, 50, 5000.00);


