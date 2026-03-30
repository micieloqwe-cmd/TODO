# TaskFlow — Todo App

ระบบ Account Login + Todo List Management พร้อม Dashboard  
พัฒนาด้วย PHP + MySQL + Vanilla JS

---

## 📁 โครงสร้างไฟล์

```
todo_app/
├── index.php              ← หน้ารายการงาน (Todo List)
├── dashboard.php          ← หน้า Dashboard + Charts
├── login.php              ← เข้าสู่ระบบ
├── register.php           ← สมัครสมาชิก
├── logout.php             ← ออกจากระบบ
├── todo_app_updated.sql   ← SQL สำหรับสร้างฐานข้อมูล
│
├── includes/
│   ├── config.php         ← Database config + PDO connection
│   ├── auth.php           ← Auth helpers (session, CSRF, hash)
│   └── sidebar.php        ← Sidebar component
│
├── api/
│   ├── todo_action.php    ← CRUD API (create/read/update/delete/toggle)
│   └── stats.php          ← Stats API (สำหรับ AJAX refresh)
│
└── assets/
    ├── css/style.css      ← Stylesheet
    └── js/app.js          ← Frontend logic
```

---

## 🚀 วิธีติดตั้ง

### 1. Setup Database
```sql
-- ใน phpMyAdmin หรือ MySQL client รัน:
source todo_app_updated.sql
```
หรือ Import ไฟล์ `todo_app_updated.sql` ผ่าน phpMyAdmin

### 2. แก้ไข Database Config
เปิด `includes/config.php` และแก้ไข:
```php
define('DB_HOST', 'localhost');   // Host ของ MySQL
define('DB_USER', 'root');        // Username
define('DB_PASS', '');            // Password
define('DB_NAME', 'todo_app');    // ชื่อ Database
```

### 3. วางไฟล์ใน Server
- **XAMPP**: วางใน `C:/xampp/htdocs/todo_app/`
- **WAMP**: วางใน `C:/wamp/www/todo_app/`
- **Linux/Mac**: วางใน `/var/www/html/todo_app/`

### 4. เปิดในเบราว์เซอร์
```
http://localhost/todo_app/
```

---

## ✨ ฟีเจอร์ทั้งหมด

### 🔐 Authentication
- สมัครสมาชิก (ชื่อ, Email, Password)
- เข้าสู่ระบบ / ออกจากระบบ
- Session timeout (1 ชั่วโมง)
- CSRF Token ป้องกันการโจมตี
- Password hashing ด้วย bcrypt (cost 12)

### 📋 Todo Management
- ✅ เพิ่ม / แก้ไข / ลบงาน
- ✅ กด Checkbox → เปลี่ยนสถานะ pending ↔ done ทันที (AJAX)
- ✅ ระดับความสำคัญ: สูง 🔴 / กลาง 🟡 / ต่ำ 🟢
- ✅ กำหนดวันส่ง (Due Date) พร้อม badge วันนี้ / พรุ่งนี้ / เกินกำหนด
- ✅ ค้นหาแบบ Live Search
- ✅ Filter ตามสถานะและความสำคัญ
- ✅ Pagination (8 รายการ/หน้า)
- ✅ แยกข้อมูลตามผู้ใช้ (User ownership)

### 📊 Dashboard
- 📈 สถิติรวม: ทั้งหมด / เสร็จ / ยังไม่เสร็จ / เกินกำหนด
- 📊 Completion % Progress Bar
- 🍩 Donut Chart: สัดส่วนสถานะ
- 🍩 Donut Chart: สัดส่วนความสำคัญ
- 📊 Bar Chart: เสร็จ vs ไม่เสร็จ แยกตามความสำคัญ
- ⚠ รายการเกินกำหนด
- 📅 งานกำหนดวันนี้

### 🔒 Security
- PDO Prepared Statements (ป้องกัน SQL Injection)
- CSRF Token ทุก Form และ AJAX request
- Password hashed ด้วย bcrypt
- XSS Protection (htmlspecialchars ทุกจุด)
- User ownership verification (ดูได้เฉพาะงานของตัวเอง)
- Session httponly + samesite cookies
- Session regeneration หลัง login

---

## ⚙️ Requirements
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- PDO extension enabled
- XAMPP / WAMP / LAMP / MAMP หรือ server ใดก็ได้

---

## 🎨 Tech Stack
- **Backend**: PHP 8 + PDO
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla JS + CSS3
- **Charts**: Chart.js 4
- **Fonts**: Syne + DM Sans (Google Fonts)
- **Design**: Dark Glass Morphism Theme
