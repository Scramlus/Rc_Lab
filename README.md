# RC Lab Registration

Simple RC Lab registration page with a PHP API and MySQL database.

## cPanel Setup

1. Create a MySQL database in cPanel.
2. Create a MySQL user and give it access to the database.
3. Open phpMyAdmin and import `database.sql`.
4. Copy `api/config.example.php` to `api/config.php` on the server.
5. Put your cPanel database name, user, and password in `api/config.php`.

The form saves all fields, but the public page only shows nickname and category.

Do not commit `api/config.php` to GitHub because it contains your database password.
