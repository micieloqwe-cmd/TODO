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

## 📱 VISUAL DEMONSTRATION (หน้าจอการใช้งาน)

### 👤 ส่วนของผู้ใช้งาน (User Interface)
อินเทอร์เฟซสไตล์ Glassmorphism ที่สวยงามและเน้นการใช้งานจริง

| **Login & Access** | **Personal Dashboard** |
| :---: | :---: |
| <img src="https://github.com/user-attachments/assets/bb265ebe-412c-4004-91dc-e58867dc4109" width="100%" style="border-radius:12px; border: 1px solid #44D7B6; box-shadow: 0 4px 8px rgba(0,0,0,0.3);"/> | <img src="https://github.com/user-attachments/assets/a28ed0c5-caf7-4554-81ed-1559f26c63b2" width="100%" style="border-radius:12px; border: 1px solid #44D7B6;"/> |
| *เข้าสู่ระบบด้วยความปลอดภัย* | *ภาพรวมสถิติและสถานะงานส่วนตัว* |

<br>

| **Task Management** | **Advanced Filtering** | **Responsive Design** |
| :---: | :---: | :---: |
| <img src="https://github.com/user-attachments/assets/8c72f805-274f-4bf2-8349-7439bcb18be6" width="100%" style="border-radius:12px;"/> | <img src="https://github.com/user-attachments/assets/3446a170-c4cc-4f01-90c3-5df63b5383cc" width="100%" style="border-radius:12px;"/> | <img src="https://github.com/user-attachments/assets/497502c0-9e6c-434b-8f8d-a0065af15838" width="100%" style="border-radius:12px;"/> |
| *จัดการ Todo แบบ AJAX* | *ค้นหาและกรองงานละเอียด* | *รองรับทุกอุปกรณ์* |

---

### 🛡️ ส่วนของผู้ดูแลระบบ (Admin Control Center)
ระบบหลังบ้านที่ทรงพลัง สำหรับควบคุมและตรวจสอบภาพรวมทั้งระบบ

| **System Dashboard** | **User Management** | **Global Todo List** |
| :---: | :---: | :---: |
| <img src="https://github.com/user-attachments/assets/eddf6a71-2b3a-4718-b01c-fa415c2cdae5" width="100%" style="border-radius:12px; border: 1px solid #8A2BE2;"/> | <img src="https://github.com/user-attachments/assets/bb8abbc2-40aa-414c-9225-0a57dbec2f02" width="100%" style="border-radius:12px; border: 1px solid #8A2BE2;"/> | <img src="https://github.com/user-attachments/assets/f5134f5d-4959-4950-a78e-0694768e28b5" width="100%" style="border-radius:12px; border: 1px solid #8A2BE2;"/> |
| *สถิติระบบและกราฟวิเคราะห์* | *จัดการสิทธิ์และข้อมูลสมาชิก* | *ตรวจสอบงานทั้งหมดในระบบ* |

---

## ✨ KEY CAPABILITIES (ฟีเจอร์ระดับโปร)

### 👤 USER EXPERIENCE (UX)
* ✅ **Smart CRUD:** เพิ่ม แก้ไข ลบ และ Toggle สถานะงานแบบ Real-time (AJAX)
* ✅ **Priority Engine:** ระบบคัดกรองความสำคัญ สูง 🔴 / กลาง 🟡 / ต่ำ 🟢
* ✅ **Deadline Intelligence:** ระบบ Badge แจ้งเตือนสถานะ "วันนี้ / พรุ่งนี้ / เกินกำหนด"
* ✅ **Live Analytics:** Dashboard ส่วนตัวพร้อม Donut & Bar Charts ติดตาม Progress %

### 🛡️ ADMINISTRATIVE HUB
* ✅ **System Overview:** ดูภาพรวม Users และ Todos ทั้งหมดในระบบ
* ✅ **User Authority:** ระบบจัดการบทบาท (Roles) และสถานะสมาชิก
* ✅ **Global Monitoring:** ตรวจสอบสถิติระบบผ่าน Charts: Top Active Users & Priority Breakdown

---

## 📂 โครงสร้างไฟล์
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
