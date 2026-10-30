# completed event-manager-with-php
creating an event manager website with phpMyAdmin
# UTB Events — Student Portal

This is a small PHP/MySQL web application for managing university events (student portal + admin interface).

Purpose

- Allow students to browse events, register/unregister, and leave feedback.
- Provide an admin interface for creating, updating and deleting events, managing users, and generating reports.

Quick features

- Student signup/login, profile editing, profile picture upload
- Event listing with search/sort and event detail pages
- Event registration (AJAX) and feedback/ratings
- Admin CRUD for events, user role management, reports (with CSV export)

Setup (XAMPP / Windows)

1. Place the `assg` folder in your web server document root (e.g., `c:\\xampp\\htdocs\\assgv2\\assg`).
2. Create a MySQL database named `event_management` (or another name — see `config.php`).
3. Import the database schema in `db/schema.sql` (use phpMyAdmin or mysql CLI).
4. If your MySQL server uses a non-default port (this project originally used 3307), update `config.php` or edit `connection.php` accordingly.
   - To override defaults, edit `config.php` and set `$DB_HOST`, `$DB_USER`, `$DB_PASS`, `$DB_NAME`, and `$DB_PORT`.
5. Start Apache & MySQL (XAMPP) and open the site in your browser (e.g., `http://localhost/assg`).

Security notes

- Database interactions use prepared statements in key areas to reduce SQL injection risk. Review code before deploying to production.

Admin CSV reports

- As an admin, visit `admin/admin_reports.php` and click "Download CSV" to export registration statistics.
