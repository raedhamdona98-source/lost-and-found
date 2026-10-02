# Digital Lost-and-Found Management System

A graduation project for Liwa University. The system manages lost and found
items, report matching, ownership claims, verification, collection, returns,
notifications, and administrative analytics.

## Technology

- PHP 8.2
- MariaDB / MySQL
- phpMyAdmin
- HTML and CSS
- XAMPP

## Main roles

- Student: registers, reports lost/found items, searches, tracks reports, and submits claims.
- Lost & Found Staff: reviews reports, verifies ownership, schedules collection, and confirms returns.
- Administrator: approves registrations, creates staff accounts, monitors reports, and views analytics.

## Local installation

1. Place the project in `C:\xampp\htdocs\lost-and-found`.
2. Start Apache and MySQL from XAMPP.
3. Create a database named `lost_and_found` using `utf8mb4_unicode_ci`.
4. Import `database/schema.sql` using phpMyAdmin.
5. Open `http://localhost/lost-and-found/`.
6. Visit `http://localhost/lost-and-found/setup_admin.php` once to create the first administrator.

The default XAMPP database connection uses user `root` with an empty password.
It can be changed in `config/database.php`.

## Workflow

Incident → Report → Search → Matching → Ownership Verification → Retrieval → Case Closed
