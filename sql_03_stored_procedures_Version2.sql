-- 03_stored_procedures.sql
USE workshop;
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_register_student$$
CREATE PROCEDURE sp_register_student(
  IN p_activity_id INT,
  IN p_student_id VARCHAR(64),
  OUT p_result INT,
  OUT p_message VARCHAR(255),
  OUT p_qr_token VARCHAR(128)
)
BEGIN
  DECLARE v_capacity INT;
  DECLARE v_count INT;
  DECLARE v_exists INT;
  DECLARE v_token VARCHAR(128);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '資料庫錯誤，報名失敗';
    SET p_qr_token = NULL;
  END;

  START TRANSACTION;

  SELECT capacity INTO v_capacity FROM activities WHERE id = p_activity_id FOR UPDATE;
  IF v_capacity IS NULL THEN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '活動不存在';
    SET p_qr_token = NULL;
    LEAVE proc_end;
  END IF;

  SELECT COUNT(*) INTO v_count FROM registrations WHERE activity_id = p_activity_id AND status IN ('registered','checked_in');

  IF v_capacity > 0 AND v_count >= v_capacity THEN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '名額已滿';
    SET p_qr_token = NULL;
    LEAVE proc_end;
  END IF;

  SELECT COUNT(*) INTO v_exists FROM registrations WHERE activity_id = p_activity_id AND student_id = p_student_id AND status != 'cancelled';
  IF v_exists > 0 THEN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '已報名過此活動';
    SET p_qr_token = NULL;
    LEAVE proc_end;
  END IF;

  SET v_token = UUID();
  INSERT INTO registrations (activity_id, student_id, status, qr_token) VALUES (p_activity_id, p_student_id, 'registered', v_token);

  COMMIT;
  SET p_result = 1;
  SET p_message = '報名成功';
  SET p_qr_token = v_token;

proc_end: 
  ;
END$$

DROP PROCEDURE IF EXISTS sp_cancel_registration$$
CREATE PROCEDURE sp_cancel_registration(
  IN p_activity_id INT,
  IN p_student_id VARCHAR(64),
  OUT p_result INT,
  OUT p_message VARCHAR(255)
)
BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '資料庫錯誤，取消失敗';
  END;

  START TRANSACTION;
    UPDATE registrations
      SET status = 'cancelled'
      WHERE activity_id = p_activity_id AND student_id = p_student_id AND status != 'cancelled';
    IF ROW_COUNT() > 0 THEN
      COMMIT;
      SET p_result = 1;
      SET p_message = '已取消報名';
    ELSE
      ROLLBACK;
      SET p_result = 0;
      SET p_message = '找不到可取消的報名';
    END IF;
END$$

DROP PROCEDURE IF EXISTS sp_checkin_by_token$$
CREATE PROCEDURE sp_checkin_by_token(
  IN p_token VARCHAR(128),
  OUT p_result INT,
  OUT p_message VARCHAR(255)
)
BEGIN
  DECLARE v_reg_id BIGINT;
  DECLARE v_status VARCHAR(32);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '資料庫錯誤，簽到失敗';
  END;

  START TRANSACTION;
    SELECT id, status INTO v_reg_id, v_status FROM registrations WHERE qr_token = p_token FOR UPDATE;
    IF v_reg_id IS NULL THEN
      ROLLBACK;
      SET p_result = 0;
      SET p_message = '無效的 QR token';
      LEAVE proc_end2;
    END IF;

    IF v_status = 'checked_in' THEN
      ROLLBACK;
      SET p_result = 1;
      SET p_message = '已簽到';
      LEAVE proc_end2;
    END IF;

    UPDATE registrations SET status = 'checked_in', checked_in_at = NOW() WHERE id = v_reg_id;
    COMMIT;
    SET p_result = 1;
    SET p_message = '簽到成功';

proc_end2: 
    ;
END$$

DROP PROCEDURE IF EXISTS sp_checkin_by_activity_student$$
CREATE PROCEDURE sp_checkin_by_activity_student(
  IN p_activity_id INT,
  IN p_student_id VARCHAR(64),
  OUT p_result INT,
  OUT p_message VARCHAR(255)
)
BEGIN
  DECLARE v_reg_id BIGINT;
  DECLARE v_status VARCHAR(32);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    SET p_result = 0;
    SET p_message = '資料庫錯誤，簽到失敗';
  END;

  START TRANSACTION;
    SELECT id, status INTO v_reg_id, v_status FROM registrations WHERE activity_id = p_activity_id AND student_id = p_student_id AND status != 'cancelled' FOR UPDATE;
    IF v_reg_id IS NULL THEN
      ROLLBACK;
      SET p_result = 0;
      SET p_message = '找不到報名紀錄';
      LEAVE proc_end3;
    END IF;

    IF v_status = 'checked_in' THEN
      ROLLBACK;
      SET p_result = 1;
      SET p_message = '已簽到';
      LEAVE proc_end3;
    END IF;

    UPDATE registrations SET status = 'checked_in', checked_in_at = NOW() WHERE id = v_reg_id;
    COMMIT;
    SET p_result = 1;
    SET p_message = '簽到成功';

proc_end3:
    ;
END$$

DELIMITER ;