# EduDash - Student Management System (PHP & MySQL)
A modern, unified 3-Panel Education Management System crafted for schools, colleges, and training academies.

## Database Connection Details (Pre-configured)
- **Host**: `sql108.freehosting.digitalplat.org`
- **Database**: `alalr_43295771_surajitdb`
- **User**: `alalr_43295771`
- **Password**: `INU573Wl54n93nVW5NU03wValzoM`
- **Config file**: `config/db.php`

---

## 3 Unified Control Panels

### 1. Admin Dashboard (Main Control)
- **Student & Teacher Management**: Add, Edit, Delete, Filter, CSV Export.
- **Registration Approval Queue**: One-click review, approve, or reject student self-registrations.
- **Academic Structure**: Create Classes, Sections, Departments, and assign Class Teachers.
- **Class Promotion Engine**: Select source class cohort, choose target class, and promote students with 1 click.
- **Subject Curriculum**: Manage subjects (Theory, Practical, Combined), codes, and faculty assignments.
- **Daily Attendance Overview**: Live attendance rates across classes with printable rosters.
- **Marks & Examination Reports**: Consolidated grade sheets with automated grading scale.
- **Fee Management**: Issue fee invoices, record online/cash transactions, and track outstanding balances.
- **Notice Board**: Broadcast circulars and alerts to all, faculty, or students.
- **Analytical Reports & System Settings**: Demographic statistics, session year setup, and audit logs.

### 2. Teacher Dashboard
- **My Classes & Rosters**: View assigned student lists and contact details.
- **Daily Attendance Taking**: Radio button attendance with 1-click "All Present" preset.
- **Assignments & Grading**: Create assignments, inspect student solution submissions, and award scores with feedback.
- **Exam Schedules**: Plan examination timetables and room allocations.
- **Performance Analytics**: Class rankings and average score analysis.
- **Study Materials Upload**: Share lecture handouts, PDFs, and syllabus links.
- **Leave Requests Review**: Approve or reject student leave applications with custom remarks.

### 3. Student Dashboard
- **Profile & Update Request**: View verified academic ID card and request profile updates.
- **Attendance Ledger**: View daily attendance calendar and percentage.
- **Examination Results**: View report cards, subject marks, and grades.
- **Submit Assignments**: View active assignments and submit work directly.
- **Class & Exam Routine**: Weekly class timetable (Monday to Saturday) and upcoming exam schedule.
- **Study Materials Download**: Download lecture notes and syllabus resources.
- **Fee Status**: Check fee invoices, paid amounts, balance due, and printable statements.
- **Apply for Leave**: Submit absence requests to teachers.

---

## Default Login Credentials

| Role | Username | Password |
|---|---|---|
| **Admin** | `admin` | `admin123` |
| **Teacher** | `teacher1` | `teacher123` |
| **Student** | `student1` | `student123` |

*(Note: The login page also features quick demo auto-fill buttons for instant testing!)*

---

## Installation & Deployment Guide on DigitalPlat / FreeHosting

1. **Upload Files**:
   - Extract the contents of `student_management_system.zip`.
   - Upload all files and folders to your website's root folder (`public_html` or `htdocs`) via cPanel File Manager or FTP.

2. **Import Database**:
   - Open **phpMyAdmin** in your DigitalPlat / FreeHosting cPanel.
   - Select the database `alalr_43295771_surajitdb`.
   - Click on the **Import** tab at the top.
   - Choose the file `database/schema.sql` and click **Go**.
   - All 19 relational tables and seed data will be created automatically.

3. **Launch the System**:
   - Open your website domain in any web browser: `http://your-domain.freehosting.digitalplat.org/`
   - Log in using any of the credentials above!
