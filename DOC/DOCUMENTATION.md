# Project Documentation

## Overview
This project is a Helpdesk system designed to manage tickets, notifications, and user interactions efficiently. It is built using the Laravel framework and follows best practices for web application development.

## Features
- Ticket management system
- User roles and permissions
- Notifications via email and WhatsApp
- RESTful API endpoints
- Database seeding for initial setup

## Requirements
- PHP 8.0 or higher
- Composer
- Node.js and npm
- SQLite (or another database supported by Laravel)

## Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/alvinastr/Helpdesk-ITSO.git
   cd Helpdesk-ITSO
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies:
   ```bash
   npm install
   ```

4. Compile assets:
   ```bash
   npm run dev
   ```

5. Set up the environment file:
   ```bash
   cp .env.example .env
   ```
   Update the `.env` file with your database and mail configuration.

6. Generate the application key:
   ```bash
   php artisan key:generate
   ```

7. Run database migrations:
   ```bash
   php artisan migrate
   ```

8. Seed the database:
   ```bash
   php artisan db:seed
   ```

## Usage
- Start the development server:
  ```bash
  php artisan serve
  ```
  The application will be available at `http://localhost:8000`.

- Access the admin panel using the seeded admin credentials.

## Testing
Run the test suite using PHPUnit:
```bash
php artisan test
```

## Deployment
Use the `deploy-production.sh` script to deploy the application to a production environment. Ensure all environment variables are correctly configured.

## Contributing
1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Submit a pull request with a detailed description of your changes.

## License
This project is licensed under the MIT License. See the LICENSE file for details.