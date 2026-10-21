THEATER ARTS DREAM JOB APPLICATION SYSTEM

REQUIREMENTS
- XAMPP for macOS with Apache, MySQL, PHP PDO MySQL extension
- Browser

SETUP
1. Copy the theater_arts_application folder into /Applications/XAMPP/xamppfiles/htdocs/
2. Open XAMPP Manager and start Apache and MySQL.
3. Go to http://localhost/phpmyadmin
4. Click Import, choose database.sql, and click Go. This creates the database and table.
5. Open http://localhost/theater_arts_application/
6. To add, complete the form and click Submit application.
7. To update, click Edit beside a record, change details, then Save changes.
8. To delete, click Delete beside a record and confirm.

FILES
index.php - CRUD application form and submitted applications list
 db.php - PDO database connection
 style.css - page design
 database.sql - MySQL schema

NOTES
- No login/registration module (as required).
- Records are stored in MySQL, not just the browser.
- Database has 11 columns, including primary key and date added.
- Local classroom demo only: before publishing publicly, add authentication and stronger data privacy protections.
- Export screenshots of database.sql in VS Code, folder in VS Code, and add/edit/delete in the browser.
- Screen record your demonstration; upload the video to Google Drive and code to GitHub yourself.
