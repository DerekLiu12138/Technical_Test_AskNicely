# 🧑‍💻 AskNicely Engineer Technical Test

This repository implements a **full-stack PHP + MySQL + Vue** web application for the AskNicely Engineer test.
 It demonstrates backend CRUD operations, CSV importing, data persistence via MySQL, RESTful APIs, and containerized unit testing with PHPUnit and Docker.

------

## 📘 Table of Contents

1. Overview
2. Tech Stack
3. Project Structure
4. Core Features
5. Setup & Run (Docker + Makefile)
6. Database Schema
7. API Endpoints
8. Testing (PHPUnit)
9. Development Workflow
10. Future Improvements

------

## 🧩 Overview

This app provides a simple employee management system:

- Upload a CSV file of employees and companies
- Parse and import it into MySQL
- Display the employee list grouped by company
- Edit employee email addresses inline
- Display average salary per company
- Run automated PHPUnit tests in isolated Docker environment

------

## ⚙️ Tech Stack

| Layer         | Technology             | Purpose                              |
| ------------- | ---------------------- | ------------------------------------ |
| Backend       | **PHP 8.2 + PDO**      | API logic and DB interaction         |
| Frontend      | **Vue 3 + Vite**       | Interactive UI (CSV upload + edit)   |
| Database      | **MySQL 8.0**          | Persistent data store                |
| Web Server    | **Nginx + PHP-Apache** | Serves frontend and backend API      |
| Testing       | **PHPUnit 10**         | Unit and integration testing         |
| Orchestration | **Docker Compose**     | Consistent, isolated dev environment |
| Automation    | **Makefile**           | One-command build/test/run workflow  |

------

## 📁 Project Structure

```
Technical_Test_AskNicely/
├── .env
├── docker-compose.yml
├── docker-compose.test.yml
├── makefile
├── employees.csv
├── README.md
│
├── backend/
│   ├── config/
│   ├── public/
│   │   ├── .htaccess
│   │   └── index.php
│   ├── src/
│   │   ├── Admin.php
│   │   ├── bootstrap.php
│   │   ├── CsvImporter.php
│   │   └── Repositories.php
│   ├── tests/
│   │   ├── bootstrap.php
│   │   ├── CompanyRepoTest.php
│   │   ├── CsvImporterTest.php
│   │   └── EmployeeRepoTest.php
│   ├── composer.json / composer.lock
│   ├── Dockerfile / Dockerfile.test
│   └── phpunit.xml
│
├── db/
│   └── init.sql
│
├── frontend/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── AveragesTable.vue
│   │   │   ├── EmployeesTable.vue
│   │   │   ├── Toast.vue
│   │   │   └── UploadCsv.vue
│   │   ├── utils/
│   │   │   ├── api.js
│   │   │   └── format.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── tests/
│   │   ├── api.test.js
│   │   ├── App.test.js
│   │   ├── AveragesTable.test.js
│   │   ├── EmployeesTable.test.js
│   │   ├── format.test.js
│   │   └── UploadCsv.test.js
│   ├── index.html
│   ├── Dockerfile
│   ├── nginx.conf
│   ├── package.json / package-lock.json
│   ├── vite.config.js
│   ├── vitest.config.js
│   └── vitest.setup.js
└──

```

------

## 🚀 Setup & Run (Docker + Makefile)

### 🧰 1. Prerequisites

- Docker & Docker Compose
- `make` command (built-in on macOS/Linux, or install on Windows via WSL)
- Free ports: `8088`, `8089`, `3307`

### ⚙️ 2. Build and Run (via Makefile)

These are wired to the compose files and test image `local/asknicely-test:8.2`.

| Command              | Stack    | Description                                                  |
| -------------------- | -------- | ------------------------------------------------------------ |
| **`make up`**        | App      | Build and start the full stack (frontend + backend + database). |
| **`make down`**      | App      | Stop all running containers while keeping volumes intact.    |
| **`make restart`**   | App      | Rebuild and restart the entire environment.                  |
| **`make logs`**      | Backend  | Tail backend (Apache/PHP) logs in real time.                 |
| **`make shell`**     | Backend  | Open an interactive Bash shell inside the backend container. |
| **`make build`**     | Tests    | Build the dedicated PHP CLI test image (`local/asknicely-test:8.2`). |
| **`make db`**        | Tests    | Spin up the isolated MySQL test database container only.     |
| **`make deps`**      | Tests    | Install PHP dependencies *only if* `composer.lock` has changed (hash-guarded incremental install). |
| **`make test`**      | Tests    | Full backend test pipeline — build → start DB → install deps → run PHPUnit. |
| **`make clean`**     | Tests    | Stop test containers but preserve cached volumes.            |
| **`make deepclean`** | Both     | Stop all containers and remove all volumes, caches, and lock hashes. |
| **`make ci`**        | Tests    | Clean build and execute backend tests — used in CI/CD environments. |
| **`make fe-up`**     | Frontend | Start the Vite development server for hot-reload frontend (`http://localhost:5173`). |
| **`make fe-logs`**   | Frontend | Tail frontend (Vite) logs.                                   |
| **`make fe-shell`**  | Frontend | Enter the running frontend container shell (sh).             |
| **`make fe-test`**   | Frontend | Execute all frontend unit tests via Vitest.                  |
| **`make test-all`**  | Both     | Run both backend PHPUnit and frontend Vitest suites together. |

------

### ▶️ Example Console Output

```
Building test image local/asknicely-test:8.2...
docker build -f ./backend/Dockerfile.test ./backend -t local/asknicely-test:8.2
[+] Building 3.4s (12/12) FINISHED                                                                                               
✅ Test image built successfully
Starting MySQL test database...
docker compose -f docker-compose.test.yml up -d test_db
[+] Running 1/1
✅ Database is up and healthy
>> Dependencies are up to date, skipping install.
Running PHPUnit tests...
docker compose -f docker-compose.test.yml run --rm phpunit
[+] Creating 2/2
[+] Running 1/1
PHPUnit 10.5.58 by Sebastian Bergmann and contributors.
Runtime:       PHP 8.2.29
Configuration: /app/phpunit.xml

.....                                                               5 / 5 (100%)

Time: 00:00.158, Memory: 8.00 MB

OK (5 tests, 18 assertions)
✅ Backend tests completed
```

------

## 🧮 Database Schema

### companies

| Column | Type         | Description    |
| ------ | ------------ | -------------- |
| id     | INT PK       | Auto increment |
| name   | VARCHAR(255) | Company name   |

### employees

| Column     | Type         | Description     |
| ---------- | ------------ | --------------- |
| id         | INT PK       | Auto increment  |
| company_id | INT          | Foreign key     |
| name       | VARCHAR(255) | Employee name   |
| email      | VARCHAR(255) | Email address   |
| salary     | INT          | Employee salary |

------

## 🔌 API Endpoints

| Method      | Endpoint                  | Description                         | Request Body                            | Response                                   |
| ----------- | ------------------------- | ----------------------------------- | --------------------------------------- | ------------------------------------------ |
| **`GET`**   | `/api/employees`          | Retrieve all employees.             | –                                       | `[ { id, company, name, email, salary } ]` |
| **`PATCH`** | `/api/employees/:id`      | Update an employee’s email address. | `{ "email": "new@email.com" }`          | Updated employee object                    |
| **`POST`**  | `/api/import`             | Upload and import a CSV file.       | `multipart/form-data` with `file` field | `{ imported, skipped, errors[] }`          |
| **`GET`**   | `/api/companies/averages` | Get average salary per company.     | –                                       | `[ { company, average_salary } ]`          |

------

## 🧪 Testing (PHPUnit)

All backend tests run inside an isolated Docker environment to ensure consistent results across machines：

```
make test
```

**Tests cover:**

- **Build** the PHP CLI test image (`local/asknicely-test:8.2`).
- **Start** the MySQL test database (`test_db`) with health checks.
- **Install** dependencies if `composer.lock` has changed (via SHA-256 guard).
- **Execute** the PHPUnit test suite.

------

## ✅ Test Coverage

- **CSV Import Validation**
  - Detects invalid headers, malformed rows, and BOM/NBSP edge cases.
  - Verifies correct handling of duplicate records and blank rows.
- **Repository Logic**
  - Ensures `CompanyRepo` and `EmployeeRepo` handle insertions, updates, and foreign key integrity correctly.
- **Database CRUD**
  - End-to-end testing against a real MySQL container (schema from `db/init.sql`).
  - Confirms that all read/write operations produce consistent results.
- **API Response Validation**
  - Tests for correct HTTP status codes, payload structure, and error responses across all endpoints.

------

## 🔄 Development Workflow

| Action                | Command          | Description                                                  |
| --------------------- | ---------------- | ------------------------------------------------------------ |
| **Start Application** | `make up`        | Builds and starts all core services (backend, frontend, database). |
| **Stop Application**  | `make down`      | Gracefully stops all running containers while keeping data volumes. |
| **Run Tests**         | `make test`      | Executes the full backend test suite (PHPUnit).              |
| **Open Shell**        | `make shell`     | Launches an interactive shell inside the backend container.  |
| **Reset Environment** | `make deepclean` | Stops all containers, removes caches, and resets dependency volumes for a clean rebuild. |

------

## 💡 Future Improvements

| Area              | Enhancement                                                | Description                                                  |
| ----------------- | ---------------------------------------------------------- | ------------------------------------------------------------ |
| **Code Quality**  | Integrate **PHPStan**, **ESLint**, and **Prettier**        | Enforce static analysis and consistent formatting across backend and frontend codebases. |
| **Security**      | Implement **JWT-based authentication**                     | Protect API routes and prepare for multi-user access control. |
| **UX / UI**       | Add **real-time CSV validation** and inline error feedback | Improve user experience when uploading malformed or incomplete CSVs. |
| **CI/CD**         | Extend **GitHub Actions** workflow                         | Automate multi-stage builds, test execution, and Docker image publishing. |
| **Scalability**   | Adopt **Laravel ORM (Eloquent)** with database migrations  | Simplify schema evolution and enable large-scale data relationships. |
| **Observability** | Add **structured logging** and request tracing             | Facilitate debugging and performance monitoring across services. |

------

## 🧾 License

This project is for **AskNicely Engineer Technical Test** evaluation only.
 All rights reserved © 2025 Derek Liu.
