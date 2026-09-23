# Docker Setup Guide: Salon Appointment Manager

This project is fully configured to run inside Docker using PHP 8.2-FPM, Nginx, MySQL 8.0, Redis, Mailpit, and Node.js.

---

## 🚀 Quick Start (When You Are Ready to Run)

### 1. Initialize the Environment
Copy the pre-configured `.env.docker` file to `.env`:
```bash
cp .env.docker .env
```

### 2. Build and Start the Containers
Start all core services (PHP application, Nginx web server, MySQL database, Redis, Mailpit):
```bash
docker compose up -d --build
```
> **Note:** The `docker-entrypoint.sh` script automatically:
> - Installs composer dependencies (if missing)
> - Generates the Laravel `APP_KEY` (if empty)
> - Creates the `public/storage` symbolic link
> - Prepares storage directories with proper permissions
> - Waits for the MySQL database to be ready

### 3. Run Database Migrations & Seeds
Once the containers are up, execute the migrations and default database seeds:
```bash
docker compose exec app php artisan migrate --seed
```

Default administrator credentials (created by `UserSeeder`):
- **Email:** `admin@gmail.com`
- **Password:** `123456789`

### 4. Build Frontend Assets
To compile Vite assets (CSS/JS, Tailwind, TUI Calendar):
```bash
docker compose run --rm npm run build
```

Or run the Vite development hot-reload server:
```bash
docker compose --profile dev up npm
```

---

## 🌐 Exposed Services & Ports

| Service | Port | Description |
|---|---|---|
| **Web (Nginx / Laravel)** | [http://localhost:8000](http://localhost:8000) | Main application interface |
| **Mailpit (Web UI)** | [http://localhost:8025](http://localhost:8025) | Local email preview inbox |
| **Mailpit (SMTP)** | `localhost:1025` | SMTP port for outgoing mail |
| **MySQL Database** | `localhost:3306` | MySQL 8.0 server |
| **Redis** | `localhost:6379` | In-memory cache & sessions |
| **Vite Dev Server** | `localhost:5173` | Hot Module Replacement (when running `npm`) |

---

## 🛠 Useful Daily Commands

### Artisan Commands
```bash
# Run any artisan command:
docker compose exec app php artisan <command>

# Clear caches:
docker compose exec app php artisan optimize:clear

# Open interactive tinker shell:
docker compose exec app php artisan tinker

# Refresh roles & permissions:
docker compose exec app php artisan app:refresh-roles-and-permissions
```

### Composer Commands
```bash
docker compose exec app composer install
docker compose exec app composer require <package-name>
```

### NPM Commands
```bash
docker compose run --rm npm install
docker compose run --rm npm run build
```

### Inspecting Logs
```bash
# View all logs:
docker compose logs -f

# View application / PHP logs:
docker compose logs -f app

# View web server logs:
docker compose logs -f web

# View database logs:
docker compose logs -f db
```

### Stopping the Containers
```bash
# Stop containers:
docker compose down

# Stop containers and remove volumes (wipes database):
docker compose down -v
```
