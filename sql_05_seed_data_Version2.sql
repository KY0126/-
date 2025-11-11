-- 05_seed_data.sql
USE workshop;

INSERT IGNORE INTO students (student_id, name, email) VALUES
('S1001', '張小明', 's1001@example.com'),
('S1002', '林小華', 's1002@example.com'),
('S1003', '王小美', 's1003@example.com');

INSERT INTO activities (title, description, start_time, end_time, location_name, lat, lng, capacity, semester, hours)
VALUES
('教學法研習 I', '基礎教學法與實務', '2025-03-10 09:00:00', '2025-03-10 12:00:00', '第一會議室', 25.0330, 121.5654, 30, '2025-1', 3),
('教學法研習 II', '進階教學策略', '2025-04-20 13:30:00', '2025-04-20 16:30:00', '第二會議室', 25.0340, 121.5640, 20, '2025-1', 3),
('線上教學工具', '介紹多種線上教學平台', '2025-05-05 14:00:00', '2025-05-05 16:00:00', 'Zoom', NULL, NULL, 0, '2025-1', 2);

INSERT INTO registrations (activity_id, student_id, status, qr_token, registered_at)
VALUES
(1, 'S1001', 'registered', UUID(), NOW()),
(1, 'S1002', 'checked_in', UUID(), NOW()),
(2, 'S1003', 'registered', UUID(), NOW());