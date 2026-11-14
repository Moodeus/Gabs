-- Gab's Bakeshop - production database updates, 5 Oct 2026 to 12 Oct 2026
-- Written for MySQL (the production server): no "ADD COLUMN IF NOT EXISTS", which
-- only MariaDB has. Run on `GabsDatabase`, top to bottom, and take a backup first.
-- Steps 1, 3 and 5 can only be run once: check first with the query below.
--
--   SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
--   WHERE TABLE_SCHEMA = DATABASE()
--     AND ((TABLE_NAME = 'accounts' AND COLUMN_NAME = 'Account_Phone')
--       OR (TABLE_NAME = 'order_line_item' AND COLUMN_NAME = 'Cost'));
--
-- Any row it returns means that column is already there: skip that step.
-- 1. accounts: optional phone number ("Number" on the Settings page)
ALTER TABLE `accounts` ADD COLUMN `Account_Phone` varchar(30) DEFAULT NULL AFTER `Email`;

-- 2. Permissions (Super Admin switches per role and per account)
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `Role` varchar(50) NOT NULL,
  `Permission` varchar(60) NOT NULL,
  `Enabled` tinyint(1) NOT NULL DEFAULT 1,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Role`,`Permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `account_permissions` (
  `Account_ID` int(11) NOT NULL,
  `Permission` varchar(60) NOT NULL,
  `Enabled` tinyint(1) NOT NULL DEFAULT 1,
  `Updated_At` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`Account_ID`,`Permission`),
  CONSTRAINT `account_permissions_ibfk_1` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. accounts: Username/Email become plain indexes instead of unique keys, so a
--    deactivated account's username/email can be reused (the app enforces
--    uniqueness among active accounts). Check first:
--      SHOW INDEX FROM `accounts` WHERE Key_name IN ('Username', 'Email');
--    If Non_unique is already 1 for both, skip this step.
ALTER TABLE `accounts`
  DROP INDEX `Username`, ADD INDEX `Username` (`Username`),
  DROP INDEX `Email`, ADD INDEX `Email` (`Email`);

-- 4. Account history (feeds Account Reports for Super Admin)
CREATE TABLE IF NOT EXISTS `account_history` (
  `Account_History_ID` int(11) NOT NULL AUTO_INCREMENT,
  `Target_Account_ID` int(11) NOT NULL,
  `Account_ID` int(11) NOT NULL,
  `Action_Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Remarks` varchar(255) DEFAULT NULL,
  `Deleted_At` datetime DEFAULT NULL,
  PRIMARY KEY (`Account_History_ID`),
  KEY `Target_Account_ID` (`Target_Account_ID`),
  KEY `Account_ID` (`Account_ID`),
  CONSTRAINT `account_history_ibfk_1` FOREIGN KEY (`Target_Account_ID`) REFERENCES `accounts` (`Account_ID`),
  CONSTRAINT `account_history_ibfk_2` FOREIGN KEY (`Account_ID`) REFERENCES `accounts` (`Account_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Cost saved with every order item (so Sales Report can deduct product cost).
--    The UPDATE fills existing items with each product's CURRENT cost, the best
--    available for past orders; new orders save the real cost at the time.
ALTER TABLE `order_line_item` ADD COLUMN `Cost` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `Price`;
UPDATE `order_line_item` li JOIN `product` p ON p.Product_ID = li.Product_ID SET li.Cost = p.Cost WHERE li.Cost = 0;

-- Optional clean-up, only if you ran the 6 Oct "backup before dropping" step and no longer need it:
-- DROP TABLE IF EXISTS `account_history_backup`;
