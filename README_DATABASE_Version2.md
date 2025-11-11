```markdown
# Database Deployment Notes

檔案：
- sql/01_create_database.sql
- sql/02_indexes_and_constraints.sql
- sql/03_stored_procedures.sql
- sql/04_views_and_queries.sql
- sql/05_seed_data.sql

建議 MySQL 版本：8.0+（5.7 也可，但儲存程序語法需注意）

匯入順序：
1. mysql -u root -p < sql/01_create_database.sql
2. mysql -u root -p < sql/02_indexes_and_constraints.sql
3. mysql -u root -p < sql/03_stored_procedures.sql
4. mysql -u root -p < sql/04_views_and_queries.sql
5. mysql -u root -p < sql/05_seed_data.sql

管理者建立：
- 若要快速建立初始 admin，可在資料庫執行命令，並使用 PHP 的 password_hash 產生密碼雜湊（或向我提供要用的密碼，我替你產生 SQL）。
  範例：
    INSERT INTO admins (username, password_hash, display_name) VALUES ('admin', '<password_hash>', '系統管理者');

呼叫儲存程序範例（在 PHP 中）：
- CALL sp_register_student(p_activity_id, p_student_id, @res, @msg, @token);
- SELECT @res AS result, @msg AS message, @token AS qr_token;