# ==============================
#   PROJECT CONFIGURATION
# ==============================
APP_COMPOSE   = docker compose -f docker-compose.yml
TEST_COMPOSE  = docker compose -f docker-compose.test.yml
APP_SERVICE   = web
TEST_IMAGE    = local/asknicely-test:8.2
BACKEND_DIR   = ./backend
LOCK_FILE     = $(BACKEND_DIR)/composer.lock
STAMP_FILE    = .vendor_lock.sha256

# Frontend specific
FRONTEND_DIR      = ./frontend
FE_SERVICE        = frontend
FE_TEST_SERVICE   = frontend-test
FE_PORT           = 5173
FE_API_BASE       = http://localhost:8089

.PHONY: help up down restart logs test deps db build clean deepclean ci shell \
        fe-up fe-logs fe-shell fe-test test-all

# ==============================
#   COLORS
# ==============================
GREEN  := \033[0;32m
CYAN   := \033[0;36m
YELLOW := \033[1;33m
RESET  := \033[0m

# ==============================
#   HELP MENU
# ==============================
help:
	@echo ""
	@echo "$(CYAN)Available targets:$(RESET)"
	@echo "  $(GREEN)make up$(RESET)            - Start the main app (backend stack)"
	@echo "  $(GREEN)make down$(RESET)          - Stop all running app containers"
	@echo "  $(GREEN)make restart$(RESET)       - Rebuild and restart the entire app"
	@echo "  $(GREEN)make logs$(RESET)          - Follow backend (Apache/PHP) logs"
	@echo "  $(GREEN)make shell$(RESET)         - Enter running backend container shell"
	@echo "  $(GREEN)make test$(RESET)          - Build → DB → Dependencies → Run PHPUnit tests"
	@echo "  $(GREEN)make deps$(RESET)          - Install PHP deps only if composer.lock changed"
	@echo "  $(GREEN)make db$(RESET)            - Start test database container only"
	@echo "  $(GREEN)make build$(RESET)         - Build the PHP test image"
	@echo "  $(GREEN)make clean$(RESET)         - Stop test containers (keep volumes)"
	@echo "  $(GREEN)make deepclean$(RESET)     - Stop + remove volumes + dependency cache"
	@echo "  $(GREEN)make ci$(RESET)            - Clean build and run backend tests (CI)"
	@echo ""
	@echo "  $(CYAN)[Frontend]$(RESET)"
	@echo "  $(GREEN)make fe-up$(RESET)         - Start frontend dev server (Vite)"
	@echo "  $(GREEN)make fe-logs$(RESET)       - Follow frontend logs"
	@echo "  $(GREEN)make fe-shell$(RESET)      - Enter running frontend container shell"
	@echo "  $(GREEN)make fe-test$(RESET)       - Run frontend unit tests (vitest)"
	@echo "  $(GREEN)make test-all$(RESET)      - Run backend (PHPUnit) + frontend (vitest) tests"
	@echo ""

# ==============================
#   APPLICATION MANAGEMENT (BACKEND)
# ==============================
up:
	@echo "$(YELLOW)Starting main application...$(RESET)"
	$(APP_COMPOSE) up -d
	@echo "$(GREEN)✅ App is running at http://localhost:8089$(RESET)"

down:
	@echo "$(YELLOW)Stopping all containers...$(RESET)"
	$(APP_COMPOSE) down
	@echo "$(GREEN)✅ Containers stopped$(RESET)"

restart: down build up

logs:
	@echo "$(CYAN)--- Backend logs ($(APP_SERVICE)) ---$(RESET)"
	$(APP_COMPOSE) logs -f $(APP_SERVICE)

shell:
	@echo "$(CYAN)Entering $(APP_SERVICE) container...$(RESET)"
	$(APP_COMPOSE) exec $(APP_SERVICE) bash

# ==============================
#   TESTING AND DEPENDENCIES (BACKEND)
# ==============================
# Run full backend test pipeline
test: build db deps
	@echo "$(YELLOW)Running PHPUnit tests...$(RESET)"
	$(TEST_COMPOSE) run --rm phpunit
	@echo "$(GREEN)✅ Backend tests completed$(RESET)"

# Start the test database only
db:
	@echo "$(YELLOW)Starting MySQL test database...$(RESET)"
	$(TEST_COMPOSE) up -d test_db
	@echo "$(GREEN)✅ Database is up and healthy$(RESET)"

# Install PHP dependencies only when composer.lock has changed
deps:
	@mkdir -p $(BACKEND_DIR)/vendor
	@sha256sum $(LOCK_FILE) 2>/dev/null | awk '{print $$1}' > .current_lock || true
	@if [ ! -f $(STAMP_FILE) ] || [ "$$(cat .current_lock)" != "$$(cat $(STAMP_FILE) 2>/dev/null)" ]; then \
	  echo "$(YELLOW)>> composer.lock changed or first run, installing dependencies...$(RESET)"; \
	  $(TEST_COMPOSE) run --rm deps; \
	  cp .current_lock $(STAMP_FILE); \
	else \
	  echo "$(GREEN)>> Dependencies are up to date, skipping install.$(RESET)"; \
	fi
	@rm -f .current_lock

# Build the PHP CLI test image
build:
	@echo "$(YELLOW)Building test image $(TEST_IMAGE)...$(RESET)"
	docker build -f $(BACKEND_DIR)/Dockerfile.test $(BACKEND_DIR) -t $(TEST_IMAGE)
	@echo "$(GREEN)✅ Test image built successfully$(RESET)"

# Cleanup for backend test stack
clean:
	@echo "$(YELLOW)Stopping test containers...$(RESET)"
	$(TEST_COMPOSE) down
	@echo "$(GREEN)✅ Test containers stopped$(RESET)"

deepclean: clean down
	@echo "$(YELLOW)Removing Docker volumes and caches...$(RESET)"
	- docker volume rm $$(docker volume ls -q | grep '_vendor_cache$$') 2>/dev/null || true
	- docker volume rm $$(docker volume ls -q | grep '_composer_cache$$') 2>/dev/null || true
	- rm -f $(STAMP_FILE)
	@echo "$(GREEN)✅ Clean environment ready$(RESET)"

# Full clean build and backend tests (for CI/CD)
ci: clean build db deps
	@echo "$(YELLOW)Running backend CI pipeline...$(RESET)"
	$(TEST_COMPOSE) run --rm phpunit
	@echo "$(GREEN)✅ Backend CI tests passed successfully$(RESET)"

# ==============================
#   FRONTEND TASKS
# ==============================
fe-up:
	@echo "$(YELLOW)Starting frontend (Vite dev server)...$(RESET)"
	$(APP_COMPOSE) up -d $(FE_SERVICE)
	@echo "$(GREEN)✅ Frontend is running at http://localhost:$(FE_PORT)$(RESET)"
	@echo "$(CYAN)   API base: $(FE_API_BASE)$(RESET)"

fe-logs:
	@echo "$(CYAN)--- Frontend logs ($(FE_SERVICE)) ---$(RESET)"
	$(APP_COMPOSE) logs -f $(FE_SERVICE)

fe-shell:
	@echo "$(CYAN)Entering $(FE_SERVICE) container...$(RESET)"
	$(APP_COMPOSE) exec $(FE_SERVICE) sh

# Run vitest once
fe-test:
	@echo "Running frontend unit tests (vitest) with cache..."
	$(TEST_COMPOSE) run --rm frontend-test
	@echo "✅ Frontend tests completed"

# Run both backend + frontend tests
test-all: test fe-test
	@echo "$(GREEN)✅ All backend & frontend tests passed$(RESET)"
