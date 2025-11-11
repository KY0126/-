-- 04_views_and_queries.sql
USE workshop;

DROP VIEW IF EXISTS vw_activity_with_counts;
CREATE VIEW vw_activity_with_counts AS
SELECT
  a.*,
  IFNULL( (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status IN ('registered','checked_in')), 0) AS registered_count,
  IFNULL( (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status = 'checked_in'), 0) AS checked_in_count
FROM activities a;

DROP VIEW IF EXISTS vw_student_checkedin;
CREATE VIEW vw_student_checkedin AS
SELECT
  r.student_id,
  r.activity_id,
  a.title,
  a.hours,
  a.semester,
  a.start_time,
  r.checked_in_at
FROM registrations r
JOIN activities a ON r.activity_id = a.id
WHERE r.status = 'checked_in';

DROP VIEW IF EXISTS vw_activity_attendance_summary;
CREATE VIEW vw_activity_attendance_summary AS
SELECT
  a.semester,
  a.id AS activity_id,
  a.title,
  a.hours,
  IFNULL( (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status = 'checked_in'), 0) AS attended,
  IFNULL( (SELECT COUNT(*) FROM registrations r WHERE r.activity_id = a.id AND r.status IN ('registered','checked_in')), 0) AS registered
FROM activities a;