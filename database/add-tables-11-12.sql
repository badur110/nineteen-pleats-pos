INSERT INTO restaurant_tables (name, sort_order, is_active) VALUES
('მაგიდა 11', 11, 1),
('მაგიდა 12', 12, 1)
ON DUPLICATE KEY UPDATE
  sort_order = VALUES(sort_order),
  is_active = 1;
