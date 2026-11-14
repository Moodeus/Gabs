-- Gab's Bakeshop - optional sample/test data (ingredients, products, recipes)
--
-- NOT part of the production schema - import this only for local testing/demo.
-- Run gabsbakeshopoz.sql first, then:
--   mysql -u root gabsbakeshopoz < SQL_Templates/sample_data.sql
--
-- Safe to re-run: it clears its own rows first so you don't get duplicates.

USE `gabsbakeshopoz`;

DELETE FROM `recipe`;
DELETE FROM `product`;
DELETE FROM `ingredients`;

-- --------------------------------------------------------
-- Ingredients (Stock_In_Grams is the working unit; Stock_Unit/Unit_To_Grams
-- are just how it's displayed and purchased)
-- --------------------------------------------------------

INSERT INTO `ingredients` (`Ingredient_ID`, `Name`, `Stock_Unit`, `Unit_To_Grams`, `Stock_In_Grams`) VALUES
(1,  'All-Purpose Flour',  'kg', 1000, 25000),
(2,  'Granulated Sugar',   'kg', 1000, 15000),
(3,  'Brown Sugar',        'kg', 1000, 8000),
(4,  'Butter',             'kg', 1000, 10000),
(5,  'Eggs',                'pc',  50, 3000),
(6,  'Whole Milk',         'L',  1000, 12000),
(7,  'Baking Powder',      'kg', 1000, 1000),
(8,  'Baking Soda',        'kg', 1000, 500),
(9,  'Salt',                'kg', 1000, 2000),
(10, 'Vanilla Extract',     'ml',   1, 500),
(11, 'Active Dry Yeast',    'kg', 1000, 800),
(12, 'Chocolate Chips',     'kg', 1000, 6000),
(13, 'Cocoa Powder',        'kg', 1000, 3000),
(14, 'Cream Cheese',        'kg', 1000, 4000),
(15, 'Powdered Sugar',      'kg', 1000, 5000);

-- --------------------------------------------------------
-- Products
-- --------------------------------------------------------

INSERT INTO `product` (`Product_ID`, `Name`, `Stock`, `Cost`, `Price`) VALUES
(1, 'White Bread Loaf',              40, 25.00,  65.00),
(2, 'Chocolate Chip Cookies (Dozen)', 30, 45.00, 120.00),
(3, 'Classic Cupcake',                60, 12.00,  35.00),
(4, 'Red Velvet Cake (Whole)',         8, 250.00, 650.00),
(5, 'Chocolate Brownie',              50, 15.00,  40.00),
(6, 'Cinnamon Roll',                  36, 18.00,  50.00),
(7, 'Blueberry Muffin',               45, 14.00,  38.00),
(8, 'Croissant',                      40, 20.00,  55.00);

-- --------------------------------------------------------
-- Recipes (grams of ingredient needed per one unit of product)
-- --------------------------------------------------------

INSERT INTO `recipe` (`Ingredient_ID`, `Product_ID`, `Qty_Per_Unit`) VALUES
-- White Bread Loaf
(1, 1, 500), (11, 1, 7), (9, 1, 10), (2, 1, 15), (4, 1, 20),
-- Chocolate Chip Cookies (Dozen)
(1, 2, 280), (4, 2, 170), (3, 2, 150), (2, 2, 100), (5, 2, 100), (12, 2, 200), (8, 2, 5), (9, 2, 3), (10, 2, 5),
-- Classic Cupcake
(1, 3, 30), (2, 3, 25), (4, 3, 20), (5, 3, 15), (6, 3, 20), (7, 3, 2),
-- Red Velvet Cake (Whole)
(1, 4, 480), (2, 4, 400), (4, 4, 230), (5, 4, 200), (13, 4, 30), (6, 4, 240), (14, 4, 250), (15, 4, 300), (8, 4, 5), (10, 4, 10),
-- Chocolate Brownie
(1, 5, 20), (2, 5, 30), (4, 5, 25), (13, 5, 15), (5, 5, 20), (12, 5, 15),
-- Cinnamon Roll
(1, 6, 60), (2, 6, 20), (4, 6, 25), (6, 6, 30), (11, 6, 3), (5, 6, 15),
-- Blueberry Muffin
(1, 7, 40), (2, 7, 25), (4, 7, 20), (5, 7, 15), (6, 7, 25), (7, 7, 2),
-- Croissant
(1, 8, 50), (4, 8, 35), (2, 8, 5), (11, 8, 3), (6, 8, 15), (9, 8, 2);
