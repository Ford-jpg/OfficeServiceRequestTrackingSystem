# Installation

## Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm

---

## Setup Instructions

1. **Clone the repository & navigate to the project:**
   ```bash
   git clone <repository-url>
   cd OfficeServiceRequestTrackingSystem
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure the environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Set up the database & seed demo data:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

5. **Link storage & build frontend assets:**
   ```bash
   php artisan storage:link
   npm run build
   ```

6. **Start the application:**
   ```bash
   php artisan serve
   ```

Access the panel at: [http://localhost:8000](http://localhost:8000)

---

## Default Login Credentials

**Password for all accounts:** `password`

| Role | Email |
| --- | --- |
| Admin | `admin@example.com` |
| Service Manager | `manager@example.com` |
| Facilities Technician | `tech.facilities@example.com` |
| IT Technician | `tech.it@example.com` |
| Employee | `jane.doe@example.com` |
