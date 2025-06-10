# To-Do List Web Application

A full-featured To-Do List web application built with PHP, MySQL, HTML, CSS, and JavaScript. This application allows users to register, login, and manage their tasks with features like adding, editing, marking as complete, and deleting tasks.

## Features

- User authentication (register/login)
- Add new tasks
- Edit existing tasks
- Mark tasks as complete/incomplete
- Delete tasks
- Responsive design for all devices

## Requirements

- XAMPP (or any other local server with PHP and MySQL)
- Web browser

## Installation

1. Clone or download this repository to your XAMPP's htdocs folder
2. Start Apache and MySQL services in XAMPP Control Panel
3. Open your web browser and navigate to `http://localhost/To%20do%20list/`
4. The application will automatically create the required database and tables on first run

## Database Structure

The application uses two main tables:

### Users Table
- id: Primary key, auto-increment
- username: Unique username for each user
- password: Hashed password for security
- created_at: Timestamp when the user was created

### Tasks Table
- id: Primary key, auto-increment
- user_id: Foreign key referencing the users table
- task: The task description
- status: 0 for incomplete, 1 for complete
- created_at: Timestamp when the task was created

## Usage

1. Register a new account or login with existing credentials
2. Add new tasks using the input field at the top of the tasks page
3. Mark tasks as complete by checking the checkbox
4. Edit tasks by clicking the edit icon
5. Delete tasks by clicking the delete icon
6. Logout when finished

## Security Features

- Password hashing using PHP's password_hash() function
- Protection against SQL injection using prepared statements
- Input validation and sanitization
- Session-based authentication

## License

This project is open-source and available for personal and commercial use.