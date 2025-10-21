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

.PHONY: help up down restart logs test deps db build clean deepclean ci shell

# Color formatting
GREEN := \033[0;32m
CYAN := \033[0;36m
YELLOW := \033[1;33m
RESET := \033[0m

# ==============================
#   HELP MENU
# ==============================
help:
	@echo ""
	@echo "$(CYAN)Available targets:$(RESET)"
	@echo "  $(GREEN)make up$(RESET)          - Start the main app (Apache + MySQL)"
	@echo "  $(GREEN)make down$(RESET)        - Stop all running app containers"
	@echo "  $(GREEN)make restart$(RESET)     - Rebuild and restart the entire app"
	@echo "  $(GREEN)make logs$(RESET)        - Follow Apache logs"
	@echo "  $(GREEN)make test$(RESET)        - Build → DB → Dependencies → Run PHPUnit tests"
	@echo "  $(GREEN)make deps$(RESET)        - Install dependencies only if composer.lock changed"
	@echo "  $(GREEN)make db$(RESET)          - Start test database container only"
	@echo "  $(GREEN)make build$(RESET)       - Build the PHP test image"
	@echo "  $(GREEN)make clean$(RESET)       - Stop test containers (keep volumes)"
	@echo "  $(GREEN)make deepclean$(RESET)   - Stop + remove volumes + dependency cache"
	@echo "  $(GREEN)make shell$(RESET)       - Enter running web container shell"
	@echo "  $(GREEN)make ci$(RESET)          - Clean build and run tests (for CI/CD)"
	@echo ""

# ==============================
#   APPLICATION MANAGEMENT
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
	@echo "$(CYAN)--- Apache logs ---$(RESET)"
	$(APP_COMPOSE) logs -f $(APP_SERVICE)

# ==============================
#   TESTING AND DEPENDENCIES
# ==============================
# Run full test pipeline
test: build db deps
	@echo "$(YELLOW)Running PHPUnit tests...$(RESET)"
	$(TEST_COMPOSE) run --rm phpunit
	@echo "$(GREEN)✅ All tests completed$(RESET)"

# Start the test database only
db:
	@echo "$(YELLOW)Starting MySQL test database...$(RESET)"
	$(TEST_COMPOSE) up -d test_db
	@echo "$(GREEN)✅ Database is up and healthy$(RESET)"

# Install dependencies only when composer.lock has changed
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

# ==============================
#   CLEANUP AND UTILITIES
# ==============================
clean:
	@echo "$(YELLOW)Stopping test containers...$(RESET)"
	$(TEST_COMPOSE) down
	@echo "$(GREEN)✅ Test containers stopped$(RESET)"

deepclean: clean down
	@echo "$(YELLOW)Removing Docker volumes and cache...$(RESET)"
	- docker volume rm $$(docker volume ls -q | grep '_vendor_cache$$') 2>/dev/null || true
	- docker volume rm $$(docker volume ls -q | grep '_composer_cache$$') 2>/dev/null || true
	- rm -f $(STAMP_FILE)
	@echo "$(GREEN)✅ Clean environment ready$(RESET)"

# Full clean build and test (for CI/CD)
ci: clean build db deps
	@echo "$(YELLOW)Running CI pipeline...$(RESET)"
	$(TEST_COMPOSE) run --rm phpunit
	@echo "$(GREEN)✅ CI tests passed successfully$(RESET)"

# Access shell inside running web container
shell:
	@echo "$(CYAN)Entering $(APP_SERVICE) container...$(RESET)"
	$(APP_COMPOSE) exec $(APP_SERVICE) bash
