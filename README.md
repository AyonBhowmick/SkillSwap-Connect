# SkillSwap Connect

A web-based **skill exchange and paid learning platform** that connects learners and mentors. Users can learn new skills by exchanging skills they already have, or through paid learning sessions when an exchange isn't available.

---

## ✨ Features

### 👩‍🎓 Learner
- Browse available skills
- Request a skill from a mentor (exchange or paid)
- Track request status: Pending / Accepted / Rejected
- Communicate with mentors
- Get a certificate after completing a course

### 🧑‍🏫 Mentor
- Offer and manage professional skills
- View learner requests
- Accept or reject exchange and paid requests

### 🛡️ Admin
- Approve or reject users
- Manage skills and monitor skill postings
- Review requests and resolve disputes
- Track overall platform activity

### 🔐 General
- Sign up as Learner or Mentor, with login and password recovery
- Role-based access (Learner, Mentor, Admin)

---

## 🛠️ Tech Stack
- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL (`skillswap_db`)
- **Tools:** XAMPP, Git & GitHub

---

## 🚀 How to Run

**Requirements:** XAMPP (or WAMP) with PHP 8+, MySQL, and a web browser.

1. Clone the repository into XAMPP's `htdocs` folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/AyonBhowmick/SkillSwap-Connect.git
   ```
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` and create a database named:
   ```
   skillswap_db
   ```
4. Open the **SQL** tab of `skillswap_db`, paste the contents of the `sql_query` file, and run it to create the tables.
5. Check the database settings in `db.php`:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $dbname = "skillswap_db";
   ```
6. Open the project in your browser:
   ```
   http://localhost/SkillSwap-Connect
   ```

---

## 📸 Screenshots

| | |
|---|---|
| ![Screenshot 1](SS/Screenshot%202026-10-03%20033230.png) | ![Screenshot 2](SS/Screenshot%202026-10-03%20033302.png) |
| ![Screenshot 3](SS/Screenshot%202026-10-03%20033317.png) | ![Screenshot 4](SS/Screenshot%202026-10-03%20033329.png) |
| ![Screenshot 5](SS/Screenshot%202026-10-03%20033424.png) | ![Screenshot 6](SS/Screenshot%202026-10-03%20033450.png) |
| ![Screenshot 7](SS/Screenshot%202026-10-03%20033518.png) | ![Screenshot 8](SS/Screenshot%202026-10-03%20033641.png) |
| ![Screenshot 9](SS/Screenshot%202026-10-03%20033710.png) | |

---

## 📂 Project Files
| File | Purpose |
|---|---|
| `index.php` | Home page |
| `signup.php`, `login.php`, `logout.php`, `forgot_password.php` | Authentication |
| `learner.php`, `browser.php` | Learner dashboard and skill browsing |
| `mentor.php` | Mentor dashboard |
| `admin.php`, `admin_users.php`, `admin_manage_skills.php`, `admin_requests.php` | Admin panel |
| `certificate.php` | Certificate generation |
| `db.php` | Database connection |
| `sql_query` | SQL to create the database tables |

---

## 🧩 Challenges
- Designing a role-based system (Learner, Mentor, Admin)
- Managing skill request workflows and statuses
- Secure authentication and access control
- Coordinating teamwork with Git and GitHub

## 🔮 Future Improvements
- Real-time chat using WebSockets
- Online payment gateway integration
- Skill rating and review system
- Fully responsive, mobile-friendly UI

---

## 👥 Team
- Ayon Kumar Bhowmick Ovi
- Golam Iffat Rahman Farah
- Mst. Sharaf Anika Esha
