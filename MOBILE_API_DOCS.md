# Pro Walker Labor - Mobile Application API Documentation

เอกสารฉบับนี้จัดทำขึ้นเพื่อเป็นคู่มือสำหรับนักพัฒนาในการสร้าง Mobile Application ที่เชื่อมต่อกับระบบฐานข้อมูลหลักของ Pro Walker Labor ผ่าน API (RESTful)

## ข้อมูลพื้นฐาน (Base URL)
- **Production URL:** `https://your-domain.com/api` (รอเปลี่ยนเป็นโดเมนจริงของคุณ)
- **Local URL:** `http://localhost:8000/api` หรือ `http://127.0.0.1:8000/api`

## การยืนยันตัวตน (Authentication)
ระบบนี้ใช้ **Token-based Authentication** ผ่าน **Laravel Sanctum**
ทุกคำขอ (Request) ที่ต้องการดึงข้อมูลในระบบจะต้องแนบ Header ดังนี้:
```http
Authorization: Bearer {YOUR_ACCESS_TOKEN_HERE}
Accept: application/json
```

---

## 1. Authentication (การล็อกอินและจัดการผู้ใช้งาน)

### 1.1 Login (ล็อกอินเพื่อรับ Token)
- **URL:** `/login`
- **Method:** `POST`
- **Headers:** `Accept: application/json`
- **Body:**
  ```json
  {
      "email": "staff@example.com",
      "password": "staff_password_1234",
      "device_name": "mobile_app" // ไม่บังคับ (Optional)
  }
  ```
- **Success Response (200 OK):**
  ```json
  {
      "status": "success",
      "message": "Login successful",
      "data": {
          "token": "1|abcdefghijklmnopqrstuvwxyz...",
          "user": {
              "id": 1,
              "name": "Staff User",
              "email": "staff@example.com",
              "role": "staff",
              "avatar_url": "https://..."
          }
      }
  }
  ```
- **Error Response (401 Unauthorized):**
  `{ "status": "error", "message": "The provided credentials are incorrect." }`

### 1.2 Get User Profile (ข้อมูลผู้ใช้ปัจจุบัน)
- **URL:** `/user`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer {token}`, `Accept: application/json`
- **Success Response (200 OK):** คืนค่าข้อมูลโปรไฟล์ทั้งหมด (เช่น `id`, `name`, `role`, `status`, `avatar_url`)

### 1.3 Logout (ออกจากระบบและทำลาย Token)
- **URL:** `/logout`
- **Method:** `POST`
- **Headers:** `Authorization: Bearer {token}`, `Accept: application/json`
- **Success Response (200 OK):**
  `{ "status": "success", "message": "Successfully logged out" }`

---

## 2. Employees (ข้อมูลแรงงาน / ลูกจ้าง)

### 2.1 Get Employee List (รายการแรงงาน)
- **URL:** `/employees`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer {token}`, `Accept: application/json`
- **Query Parameters (ตัวเลือก):**
  - `page`: หน้าที่ต้องการแสดง (ค่าเริ่มต้น 1)
  - `search`: ค้นหาจาก เลขบัตร (employee_reference_id), ชื่อ (employeeFirstName), นามสกุล (employeeLastName)
  *ตัวอย่าง: `/employees?search=สมชาย&page=1`*
- **Success Response (200 OK):**
  ```json
  {
      "status": "success",
      "data": [
          {
              "id": 1,
              "employeeFirstName": "Somchai",
              "employeeLastName": "Munkong",
              "employer": {
                  "id": 5,
                  "company_name_en": "ABC Corp",
                  "company_name_th": "บริษัท เอบีซี"
              }
              // ... และฟิลด์อื่นๆ อีกมากมาย
          }
      ],
      "meta": {
          "current_page": 1,
          "last_page": 5,
          "per_page": 15,
          "total": 75
      }
  }
  ```

### 2.2 Get Employee Detail (ข้อมูลแรงงานรายบุคคล)
- **URL:** `/employees/{id}`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer {token}`, `Accept: application/json`
- **Success Response (200 OK):** คืนค่าออบเจกต์ข้อมูล `Employee` ทั้งหมดพร้อมสัญชาติและนายจ้าง (Employer) ต้นสังกัด

---

## 3. Employers (ข้อมูลนายจ้าง / บริษัท)

### 3.1 Get Employer List (รายการนายจ้าง)
- **URL:** `/employers`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer {token}`, `Accept: application/json`
- **Query Parameters (ตัวเลือก):**
  - `page`: หน้าที่ต้องการแสดง (ค่าเริ่มต้น 1)
  - `search`: ค้นหาจาก ชื่อบริษัท (company_name_th, company_name_en) หรือ รหัสนายจ้าง (employerId)
- **Success Response (200 OK):** คืนค่าอาร์เรย์รายการของ `Employer` พร้อมระบบแบ่งหน้าเหมือนกับ Employee

### 3.2 Get Employer Detail (ข้อมูลนายจ้างรายบุคคล)
- **URL:** `/employers/{id}`
- **Method:** `GET`
- **Headers:** `Authorization: Bearer {token}`, `Accept: application/json`
- **Success Response (200 OK):** คืนค่าข้อมูล `Employer` พร้อมลิสต์ลูกจ้าง (Employees) ล่าสุด 5 คน

---

*(นี่คือเอกสาร API ในเฟสที่ 1 ซึ่งครอบคลุมระบบ Authentication และข้อมูลหลัก สำหรับเฟสถัดๆ ไป (เช่น การบันทึก/แก้ไขข้อมูล, ระบบงาน Production) สามารถต่อยอดสร้างเพิ่มเข้ามาที่โฟลเดอร์ `app/Http/Controllers/Api` ได้เลย)*