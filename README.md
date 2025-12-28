# 🚀 NexCreate

**UGC + AI + Marketplace Platform**

A complete, production-ready Laravel 11 application designed for the Norwegian UGC (User-Generated Content) market. Built with Docker for easy deployment anywhere.

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Requirements](#-requirements)
- [Quick Start](#-quick-start)
- [Manual Setup](#-manual-setup)
- [Environment Variables](#-environment-variables)
- [Project Structure](#-project-structure)
- [API Documentation](#-api-documentation)
- [Deployment](#-deployment)
- [License](#-license)

---

## ✨ Features

- **UGC Marketplace**: Connect brands with content creators
- **AI Integration**: Ready for AI-powered features
- **Multi-Role System**: Admin, Creator, Client roles
- **API-First**: RESTful API with Laravel Sanctum authentication
- **Docker Ready**: One-command deployment
- **SEO Optimized**: Built with search engines in mind
- **Norwegian Market Focus**: Designed for the Norwegian market

---

## 🛠 Tech Stack

| Component | Technology |
|-----------|------------|
| **Backend** | Laravel 11 |
| **PHP** | 8.3 |
| **Database** | MySQL 8 |
| **Cache** | Redis |
| **Web Server** | Nginx |
| **Containerization** | Docker & Docker Compose |
| **Authentication** | Laravel Sanctum |
| **Payments** | Stripe (ready) |

---

## 📦 Requirements

- Docker & Docker Compose
- Git

That's it! Everything else runs inside Docker containers.

---

## 🚀 Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/Tamerb86/NexCreate-.git
cd NexCreate-
```

### 2. Copy environment file

```bash
cp backend/.env.example backend/.env
```

### 3. Start all services

```bash
docker-compose up -d
```

### 4. Install dependencies & setup

```bash
# Install Composer dependencies
docker exec -it nexcreate-app composer install

# Generate application key
docker exec -it nexcreate-app php artisan key:generate

# Run migrations
docker exec -it nexcreate-app php artisan migrate
```

### 5. Access the application

- **Application**: http://localhost:8000
- **API**: http://localhost:8000/api
- **Health Check**: http://localhost:8000/health

---

## 🔧 Manual Setup

### Build and start containers

```bash
# Build images
docker-compose build

# Start in detached mode
docker-compose up -d

# View logs
docker-compose logs -f
```

### Useful commands

```bash
# Enter the app container
docker exec -it nexcreate-app bash

# Run artisan commands
docker exec -it nexcreate-app php artisan <command>

# Run composer commands
docker exec -it nexcreate-app composer <command>

# Stop all containers
docker-compose down

# Stop and remove volumes (WARNING: deletes data)
docker-compose down -v
```

---

## ⚙️ Environment Variables

Key environment variables in `backend/.env`:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | NexCreate |
| `APP_ENV` | Environment | local |
| `APP_DEBUG` | Debug mode | true |
| `APP_URL` | Application URL | http://localhost:8000 |
| `DB_HOST` | Database host | mysql |
| `DB_DATABASE` | Database name | nexcreate |
| `DB_USERNAME` | Database user | root |
| `DB_PASSWORD` | Database password | root |
| `REDIS_HOST` | Redis host | redis |
| `STRIPE_KEY` | Stripe public key | - |
| `STRIPE_SECRET` | Stripe secret key | - |

---

## 📁 Project Structure

```
NexCreate/
├── backend/                 # Laravel application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Providers/
│   ├── config/
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   ├── routes/
│   │   ├── api.php
│   │   ├── web.php
│   │   └── console.php
│   ├── public/
│   ├── resources/
│   ├── storage/
│   ├── tests/
│   ├── .env.example
│   └── composer.json
├── docker/
│   ├── nginx.conf           # Nginx configuration
│   ├── php.ini              # PHP configuration
│   └── mysql/
│       └── init.sql         # Database initialization
├── Dockerfile               # PHP-FPM image
├── docker-compose.yml       # Services orchestration
└── README.md
```

---

## 📚 API Documentation

### Base URL

```
http://localhost:8000/api
```

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | API info |
| GET | `/health` | Health check |
| GET | `/v1/user` | Get authenticated user (requires auth) |

### Authentication

This API uses Laravel Sanctum for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your-token>
```

---

## 🚢 Deployment

### Deploy to VPS (Hostinger, DigitalOcean, etc.)

1. SSH into your server
2. Install Docker and Docker Compose
3. Clone the repository
4. Update `.env` with production values
5. Run `docker-compose up -d`
6. Set up SSL with Certbot

### Deploy to Railway

1. Connect your GitHub repository
2. Railway will auto-detect the Dockerfile
3. Add environment variables in Railway dashboard
4. Deploy!

---

## 🔒 Security

- All passwords are hashed using bcrypt
- API authentication via Laravel Sanctum
- CORS configured for API access
- Security headers in Nginx
- Environment variables for sensitive data

---

## 📄 License

This project is proprietary software. All rights reserved.

---

## 🤝 Support

For support, please contact the development team.

---

**Built with ❤️ for the Norwegian UGC Market**
