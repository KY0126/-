```markdown
# 教學研習活動管理 (前端 + 後端 + 資料庫)

此專案包含：
- 前端：public/*.html 與 assets/*
  - activities.html（活動管理，僅管理者可新增/修改/刪除）
  - registrations.html（學生報名/取消）
  - checkin.html（QR 簽到）
  - calendar.html（FullCalendar 行事曆）
  - stats.html（Chart.js 統計）
  - admin_users.html（管理者帳號管理）
  - index.html（首頁）
- 後端 API：api/*.php
  - auth.php (登入/登出/狀態)
  - admins.php (管理者帳號管理)
  - activities.php (活動 CRUD, events)
  - registration.php (報名/取消)
  - checkin.php (QR 簽到)
  - stats.php (統計)
  - send_email.php (email 發送，PHPMailer)
- 資料庫腳本：sql/*.sql
  - 建表、索引、儲存程序、view、種子資料
- Composer：composer.json（PHPMailer）

要點與部署步驟：
1. 下載或複製本專案檔案到伺服器，將 public/ 設為 web root（或對應路徑）。
2. 匯入 SQL（請依序）：
   - mysql -u root -p < sql/01_create_database.sql
   - mysql -u root -p < sql/02_indexes_and_constraints.sql
   - mysql -u root -p < sql/03_stored_procedures.sql
   - mysql -u root -p < sql/04_views_and_queries.sql
   - mysql -u root -p < sql/05_seed_data.sql
3. 在 api/config.php 設定資料庫與 Email 參數（請修改 user/password）。
4. 若要使用 send_email.php 的 PHPMailer，於專案根目錄執行：
   - composer install
5. 確保 PHP session 可正常運作（session.save_path 可寫）並啟用 HTTPS（生產環境）。
6. 若尚無管理者帳號，請以 SQL 新增一位 admin（範例見 README_DATABASE.md）。

安全建議：
- 在生產環境強制使用 HTTPS。
- 為管理頁面增加 CSRF 防護（範例未加入）。
- 若需更完整身份驗證（RBAC、OAuth 或 SSO），可再延伸。

若你要我：
- 我可以幫你產生初始管理者 SQL（含 password_hash）。
- 或將整個專案打包成 zip 檔案（準備好給你下載）。


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
