-- Gab's Bakeshop schema for database: gabsbakeshopoz
-- Import in phpMyAdmin or: mysql -u root < SQL_Templates/gabsbakeshopoz.sql
--
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12
--
-- Every table has a `Deleted_At` column (soft delete): NULL means the row
-- is live, a timestamp means it was "deleted" without actually removing it,
-- so audit trails referencing it (history tables, orders, etc.) never lose
-- their subject. CodeIgniter's Models filter these out automatically.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `gabsbakeshopoz` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gabsbakeshopoz`;

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--
-- Role is free text: 'Super Admin', 'Admin', 'Branch', or 'Delivery'. An
-- account with Role = 'Branch' is what used to be a separate branch record.
-- Admin and Super Admin can both create, edit, and deactivate accounts;
-- only Super Admin can see or change Super Admin accounts, and only Super
-- Admin sees Account Reports. Username/Email are plain indexes, not unique
-- keys: the app enforces uniqueness among active accounts only, so a
-- deactivated account's username/email can be reused.
-- Account_Phone (shown as "Number") is optional and filled in by the user
-- themselves on the Settings page, not when the account is created.

CREATE TABLE `accounts` (
  `Account_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Account_Phone` varchar(30) DEFAULT NULL,
  `Username` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(50) NOT NULL,
  `Outstanding_Balance` decimal(10,2) DEFAULT 0.00,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_history`
--
-- Who created/updated/deactivated which account, and when. `Account_ID`
-- is the acting account (matches the convention used by every other
-- history table); `Target_Account_ID` is the account that was acted on.

CREATE TABLE `account_history` (
  `Account_History_ID` int(11) NOT NULL,
  `Target_Account_ID` int(11) NOT NULL,
  `Account_ID` int(11) NOT NULL,
  `Action_Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Remarks` varchar(255) DEFAULT NULL,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--
-- Super Admin can switch functions off (or back on) for Admin, Branch and
-- Delivery. A missing row means "allowed" (the role's normal function), so
-- only switched-off functions need to be stored, though the Permissions
-- page saves every switch it shows. Config table: no Deleted_At.

CREATE TABLE `role_permissions` (
  `Role` varchar(50) NOT NULL,
  `Permission` varchar(60) NOT NULL,
  `Enabled` tinyint(1) NOT NULL DEFAULT 1,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Role`,`Permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_permissions`
--
-- Per-account switches, on top of the per-role ones in `role_permissions`:
-- Super Admin can switch a function off for one specific account. Only
-- restricts - a function must be on for the role AND for the account.

CREATE TABLE `account_permissions` (
  `Account_ID` int(11) NOT NULL,
  `Permission` varchar(60) NOT NULL,
  `Enabled` tinyint(1) NOT NULL DEFAULT 1,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Account_ID`,`Permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `Ingredient_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Stock_Unit` varchar(20) NOT NULL,
  `Unit_To_Grams` decimal(10,2) NOT NULL,
  `Stock_In_Grams` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Low_Stock_Threshold` decimal(10,2) NOT NULL DEFAULT 5.00,
  `Type` varchar(10) NOT NULL DEFAULT 'Dry',
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_history`
--

CREATE TABLE `ingredient_history` (
  `Ingredient_History_ID` int(11) NOT NULL,
  `Ingredient_ID` int(11) NOT NULL,
  `Account_ID` int(11) NOT NULL,
  `Old_Stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `New_Stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Action_Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Remarks` varchar(255) DEFAULT NULL,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--
-- One `orders` row is one branch's order batch (what the old schema
-- represented as several `orders` rows sharing branch_ID + order_date).
-- The individual products in the batch live in `order_line_item`.

CREATE TABLE `orders` (
  `Order_ID` int(11) NOT NULL,
  `Account_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Details` longtext DEFAULT NULL,
  `Status` varchar(50) NOT NULL,
  `Order_Total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Order_Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Proof_Image` varchar(255) DEFAULT NULL,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders_update_history`
--

CREATE TABLE `orders_update_history` (
  `Orders_Update_History_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Account_ID` int(11) NOT NULL,
  `Action_Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Remarks` varchar(255) DEFAULT NULL,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_line_item`
--
-- Name/Price/Cost are a snapshot of the product at order time (so later price
-- changes don't rewrite history); Product_ID links back to the real
-- product row for stock/recipe deduction.

CREATE TABLE `order_line_item` (
  `Order_Line_Item_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Qty` decimal(10,2) NOT NULL,
  `Details` varchar(255) DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `Product_ID` int(11) NOT NULL,
  `Name` varchar(150) NOT NULL,
  `Stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Cost` decimal(10,2) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Low_Stock_Threshold` decimal(10,2) NOT NULL DEFAULT 10.00,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_history`
--

-- Old_Recipe/New_Recipe hold a JSON snapshot ([{"name":..,"qty":..}, ...])
-- of the recipe right before/after a "Recipe: ..." remark, so Product
-- Audit can show the full before/after lists, not just the diff text.
-- NULL for every other kind of history row (stock changes, Created, Deleted).
CREATE TABLE `product_history` (
  `Product_History_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Account_ID` int(11) NOT NULL,
  `Old_Stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `New_Stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Action_Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Remarks` varchar(255) DEFAULT NULL,
  `Old_Recipe` text DEFAULT NULL,
  `New_Recipe` text DEFAULT NULL,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recipe`
--

CREATE TABLE `recipe` (
  `Recipe_ID` int(11) NOT NULL,
  `Ingredient_ID` int(11) NOT NULL,
  `Product_ID` int(11) NOT NULL,
  `Qty_Per_Unit` decimal(10,2) NOT NULL,
  `Deleted_At` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`Account_ID`),
  ADD KEY `Username` (`Username`),
  ADD KEY `Email` (`Email`);

--
-- Indexes for table `account_history`
--
ALTER TABLE `account_history`
  ADD PRIMARY KEY (`Account_History_ID`),
  ADD KEY `Target_Account_ID` (`Target_Account_ID`),
  ADD KEY `Account_ID` (`Account_ID`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`Ingredient_ID`);

--
-- Indexes for table `ingredient_history`
--
ALTER TABLE `ingredient_history`
  ADD PRIMARY KEY (`Ingredient_History_ID`),
  ADD KEY `Ingredient_ID` (`Ingredient_ID`),
  ADD KEY `Account_ID` (`Account_ID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Order_ID`),
  ADD KEY `Account_ID` (`Account_ID`);

--
-- Indexes for table `orders_update_history`
--
ALTER TABLE `orders_update_history`
  ADD PRIMARY KEY (`Orders_Update_History_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Account_ID` (`Account_ID`);

--
-- Indexes for table `order_line_item`
--
ALTER TABLE `order_line_item`
  ADD PRIMARY KEY (`Order_Line_Item_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`Product_ID`);

--
-- Indexes for table `product_history`
--
ALTER TABLE `product_history`
  ADD PRIMARY KEY (`Product_History_ID`),
  ADD KEY `Product_ID` (`Product_ID`),
  ADD KEY `Account_ID` (`Account_ID`);

--
-- Indexes for table `recipe`
--
ALTER TABLE `recipe`
  ADD PRIMARY KEY (`Recipe_ID`),
  ADD KEY `Ingredient_ID` (`Ingredient_ID`),
  ADD KEY `Product_ID` (`Product_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

ALTER TABLE `accounts`
  MODIFY `Account_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `account_history`
  MODIFY `Account_History_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ingredients`
  MODIFY `Ingredient_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `ingredient_history`
  MODIFY `Ingredient_History_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `orders`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `orders_update_history`
  MODIFY `Orders_Update_History_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `order_line_item`
  MODIFY `Order_Line_Item_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `product`
  MODIFY `Product_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `product_history`
  MODIFY `Product_History_ID` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `recipe`
  MODIFY `Recipe_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

ALTER TABLE `account_history`
  ADD CONSTRAINT `account_history_ibfk_1` FOREIGN KEY (`Target_Account_ID`) REFERENCES `accounts` (`Account_ID`),
  ADD CONSTRAINT `account_history_ibfk_2` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`);

ALTER TABLE `account_permissions`
  ADD CONSTRAINT `account_permissions_ibfk_1` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`) ON DELETE CASCADE;

ALTER TABLE `ingredient_history`
  ADD CONSTRAINT `ingredient_history_ibfk_1` FOREIGN KEY (`Ingredient_ID`) REFERENCES `ingredients` (`Ingredient_ID`),
  ADD CONSTRAINT `ingredient_history_ibfk_2` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`);

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`);

ALTER TABLE `orders_update_history`
  ADD CONSTRAINT `orders_update_history_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`),
  ADD CONSTRAINT `orders_update_history_ibfk_2` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`);

ALTER TABLE `order_line_item`
  ADD CONSTRAINT `order_line_item_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`Order_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_line_item_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`);

ALTER TABLE `product_history`
  ADD CONSTRAINT `product_history_ibfk_1` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`),
  ADD CONSTRAINT `product_history_ibfk_2` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`);

ALTER TABLE `recipe`
  ADD CONSTRAINT `recipe_ibfk_1` FOREIGN KEY (`Ingredient_ID`) REFERENCES `ingredients` (`Ingredient_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `recipe_ibfk_2` FOREIGN KEY (`Product_ID`) REFERENCES `product` (`Product_ID`) ON DELETE CASCADE;

--
-- Default admin account (login: admin / admin123 -- change after first login)
--
INSERT IGNORE INTO `accounts` (`Account_ID`, `Name`, `Email`, `Username`, `Password`, `Role`) VALUES
(1, 'Administrator', 'admin@gabs.local', 'admin', '$2y$10$XIkbXvRxOFHPCzn5iVWvveT4GD1TV2GEIc3S.NFYOSywOWhMS7CjG', 'Super Admin');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
