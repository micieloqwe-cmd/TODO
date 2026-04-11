

# ⚡ TASKFLOW — The Ultimate Todo Ecosystem
> **Next-Gen Task Management** — ระบบจัดการงานแบบ Full-stack ที่ผสานความปลอดภัยระดับองค์กร เข้ากับดีไซน์ Modern Glassmorphism

<p align="center">
  <img src="https://img.shields.io/badge/PLATFORM-TASKFLOW-blueviolet?style=for-the-badge&logo=target&logoColor=white"/>
  <img src="https://img.shields.io/badge/UI_STYLE-GLASSMORPHISM-44D7B6?style=for-the-badge&logo=figma&logoColor=white"/>
  <a href="https://tododevtasks.lovestoblog.com/login.php?i=1" target="_blank">
    <img src="https://img.shields.io/badge/LIVE_DEMO-PRODUCTION-success?style=for-the-badge&logo=google-chrome&logoColor=white"/>
  </a>
</p>

---

## 💎 PLATFORM OVERVIEW
"ไม่ใช่แค่ Todo App ทั่วไป แต่คือระบบบริหารจัดการงานที่สมบูรณ์แบบ"

| 🚀 **High Performance** | 🔐 **Enterprise Security** | 📊 **Data Insight** |
| :--- | :--- | :--- |
| **Vanilla JS + AJAX API** | **CSRF & Bcrypt Protection** | **Chart.js 4 Integration** |
| ตอบสนองทันใจไม่ต้อง Refresh หน้าจอ | ปลอดภัยจากการโจมตีทุกรูปแบบ | วิเคราะห์ผลงานผ่านกราฟอัจฉริยะ |

---

## ✨ KEY CAPABILITIES (ฟีเจอร์ระดับโปร)

### 👤 USER EXPERIENCE (UX)
* **Smart CRUD:** เพิ่ม แก้ไข ลบ และ Toggle สถานะงานแบบ Real-time (AJAX)
* **Priority Engine:** ระบบคัดกรองความสำคัญ สูง 🔴 / กลาง 🟡 / ต่ำ 🟢
* **Deadline Intelligence:** ระบบ Badge แจ้งเตือนสถานะ "วันนี้ / พรุ่งนี้ / เกินกำหนด"
* **Live Analytics:** Dashboard ส่วนตัวพร้อม Donut & Bar Charts ติดตาม Progress %

### 🛡️ ADMINISTRATIVE HUB
* **System Overview:** ดูภาพรวม Users และ Todos ทั้งหมดในระบบ
* **User Authority:** ระบบจัดการบทบาท (Roles) และสถานะสมาชิก
* **Global Monitoring:** ตรวจสอบสถิติระบบผ่าน Charts: Top Active Users & Priority Breakdown

---

## 📁 โครงสร้างไฟล์

```
todo_app/
├── index.php              ← หน้ารายการงาน (Todo List)
├── dashboard.php          ← หน้า Dashboard + Charts (User)
├── login.php              ← เข้าสู่ระบบ
├── register.php           ← สมัครสมาชิก
├── logout.php             ← ออกจากระบบ
├── todo_app_updated.sql   ← SQL สำหรับสร้างฐานข้อมูล
│
├── includes/
│   ├── config.php         ← Database config + PDO connection
│   ├── auth.php           ← Auth helpers (session, CSRF, hash)
│   └── sidebar.php        ← Sidebar component (User)
│
├── api/
│   ├── todo_action.php    ← CRUD API (create/read/update/delete/toggle)
│   └── stats.php          ← Stats API (สำหรับ AJAX refresh)
│
├── admin/
│   ├── index.php          ← Admin Dashboard (System overview)
│   ├── todos.php          ← Admin: จัดการ Todo ทั้งระบบ
│   ├── users.php          ← Admin: จัดการ Users
│   ├── 403.php            ← Error page (Access denied)
│   ├── assets/
│   │   └── admin.css      ← Admin stylesheet
│   └── includes/
│       ├── admin_guard.php    ← Admin authentication check
│       └── admin_sidebar.php  ← Admin sidebar component
│
└── assets/
    ├── css/style.css      ← Stylesheet (Main + Dark theme)
    └── js/app.js          ← Frontend logic (AJAX, interactivity)
---

## 📂 PROJECT ARCHITECTURE
```text
todo_app/
├── 🏢 admin/             # ระบบควบคุมส่วนกลาง (Admin Control Center)
├── ⚡ api/               # หัวใจหลักของการสื่อสาร (CRUD & Stats API)
├── 🛡️ includes/          # ระบบหลังบ้าน (Config, Auth, Security Guards)
├── 🎨 assets/            # งานดีไซน์ (Glassmorphism CSS & Vanilla JS)
└── 📜 todo_app_updated.sql # แผนผังฐานข้อมูลระดับโครงสร้าง
