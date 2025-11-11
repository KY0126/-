-- 02_indexes_and_constraints.sql
USE workshop;

ALTER TABLE activities
  ADD INDEX idx_start_time (start_time),
  ADD INDEX idx_semester (semester);