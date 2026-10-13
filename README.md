CampusFlow — Student Project & Task Manager

CampusFlow is a web-based dashboard designed to help students organize projects, manage tasks, track deadlines, and monitor progress from one place.

Features

- Dashboard: View active projects, completed tasks, tasks in progress, and upcoming deadlines.
- Project management: Create, edit, and delete projects.
- Task management: Create, edit, and delete tasks, set priorities and due dates, and update task status.
- Upcoming tasks: View tasks due within the next seven days.
- Database integration: Store and retrieve project and task information using MariaDB.
- Responsive interface: Built with Bootstrap and custom CSS.

Technologies Used

- HTML5
- CSS3
- JavaScript
- Bootstrap 5
- Bootstrap Icons
- PHP
- MariaDB
- PDO

Local Setup

1. Install PHP with PDO MySQL support and MariaDB.

2. Create a MariaDB database named "campusflow".

3. Import the SQL schema from the "database/" directory.

4. Configure your database connection in "config/database.php".

5. Start the PHP development server from the project directory:
   
   php -S 127.0.0.1:8000 -t public

6. Open "http://127.0.0.1:8000/" in your browser.

Security

Database credentials and local configuration files must not be committed to the repository. Configure credentials securely for your hosting environment.

Project Status

Core project and task management features have been implemented. Additional features may be added in future versions.

