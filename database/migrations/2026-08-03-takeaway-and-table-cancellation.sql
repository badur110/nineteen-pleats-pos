SET NAMES utf8mb4;

INSERT INTO restaurant_tables (name, sort_order, is_active) VALUES
('მაგიდა 11', 11, 1),
('მაგიდა 12', 12, 1),
('გატანა 1', 1001, 1),
('გატანა 2', 1002, 1)
ON DUPLICATE KEY UPDATE sort_order=VALUES(sort_order), is_active=1;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS cancelled_total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total,
  ADD COLUMN IF NOT EXISTS cancel_reason VARCHAR(255) NULL AFTER card_amount,
  ADD COLUMN IF NOT EXISTS cancelled_by INT NULL AFTER cancel_reason,
  ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP NULL DEFAULT NULL AFTER cancelled_by;
