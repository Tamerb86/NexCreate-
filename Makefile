# =============================================================================
# NexCreate - Makefile
# Quick commands for development and deployment
# =============================================================================

.PHONY: help build up down restart logs shell migrate fresh seed test

# Default target
help:
	@echo "NexCreate - Available Commands:"
	@echo ""
	@echo "  make build     - Build Docker images"
	@echo "  make up        - Start all containers"
	@echo "  make down      - Stop all containers"
	@echo "  make restart   - Restart all containers"
	@echo "  make logs      - View container logs"
	@echo "  make shell     - Enter app container shell"
	@echo "  make migrate   - Run database migrations"
	@echo "  make fresh     - Fresh migrate with seeders"
	@echo "  make seed      - Run database seeders"
	@echo "  make test      - Run tests"
	@echo "  make install   - First time setup"
	@echo ""

# Build Docker images
build:
	docker-compose build

# Start containers
up:
	docker-compose up -d

# Stop containers
down:
	docker-compose down

# Restart containers
restart:
	docker-compose restart

# View logs
logs:
	docker-compose logs -f

# Enter app shell
shell:
	docker exec -it nexcreate-app bash

# Run migrations
migrate:
	docker exec -it nexcreate-app php artisan migrate

# Fresh migrate with seeders
fresh:
	docker exec -it nexcreate-app php artisan migrate:fresh --seed

# Run seeders
seed:
	docker exec -it nexcreate-app php artisan db:seed

# Run tests
test:
	docker exec -it nexcreate-app php artisan test

# First time setup
install:
	@echo "Setting up NexCreate..."
	cp backend/.env.example backend/.env
	docker-compose build
	docker-compose up -d
	@echo "Waiting for MySQL to be ready..."
	sleep 10
	docker exec -it nexcreate-app composer install
	docker exec -it nexcreate-app php artisan key:generate
	docker exec -it nexcreate-app php artisan migrate
	@echo ""
	@echo "✅ NexCreate is ready!"
	@echo "🌐 Access: http://localhost:8000"
