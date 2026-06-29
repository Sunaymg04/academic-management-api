# Academic Appointment Management API

##  About the Project

This repository contains the backend REST API of a web-based academic management system developed as part of a diploma thesis.

The system supports the management of academic roles such as Year Lead Professors (PPA) and Teaching Assistant Students (AA) at Universidad Central “Marta Abreu” de Las Villas (UCLV).

It centralizes information, reduces manual administrative work, improves traceability, and supports the generation and management of official academic resolutions.

---

##  Purpose

The main goal of this system is to optimize the academic appointment process by:

* Standardizing data collection
* Reducing errors in manual workflows
* Improving document generation efficiency
* Enabling historical tracking of appointments
* Supporting institutional decision-making

---

##  Main Features

*  Authentication and role-based access
*  User management
*  Management of Year Lead Professors (PPA)
*  Management of Teaching Assistant Students (AA)
*  Appointment, ratification, and removal workflows
*  Generation and management of official documents
*  Historical records and traceability
*  Search and filtering functionality
*  Audit logging of system actions

---

##  User Roles

* Department Head
* Teaching Vice Dean
* Dean

Each role has specific permissions aligned with institutional processes.

---

##  Technologies Used

* PHP 8+
* Laravel 11
* REST API architecture
* MySQL (InnoDB)
* Laravel Eloquent ORM
* Laravel Sanctum (Authentication)
* Database migrations and seeders

---

##  System Architecture

This API is part of a client-server architecture:

* **Backend:** Laravel REST API (this repository)
* **Frontend:** Vue.js application
* **Database:** MySQL

The API handles business logic, validation, authentication, and data persistence, while the frontend consumes the services.

---

##  Project Structure

```txt
app/
routes/
database/
config/
storage/
```

---

##  API Overview

### Authentication

* POST `/api/login`
* POST `/api/logout`
* GET `/api/user`

---

### PPA Management

* GET `/api/ppa`
* POST `/api/ppa`
* PUT `/api/ppa/{id}`
* DELETE `/api/ppa/{id}`

---

### AA Management

* GET `/api/aa`
* POST `/api/aa`
* PUT `/api/aa/{id}`
* DELETE `/api/aa/{id}`

---

### Documents

* GET `/api/documents`
* POST `/api/documents/generate`
* GET `/api/documents/{id}`

---

### Search & History

* GET `/api/search`
* GET `/api/history`

---

##  Access Control

* Public access: none
* Authenticated users: access based on role
* Role-based permissions enforced at backend level

---

##  Installation

For a complete step-by-step setup guide for Windows, macOS, and Linux, see:

```txt
MANUAL_INSTALACION.md
```

Clone the repository:

```bash
git clone https://github.com/dayanarojasdrp/api-laravel.git
cd api-laravel
```

Install dependencies:

```bash
composer install
```

Copy environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure database in `.env`, then run:

```bash
php artisan migrate
```

(Optional) Seed database:

```bash
php artisan db:seed
```

Start the server:

```bash
php artisan serve
```

---

##  Environment Variables

Configure at least:

* DB_DATABASE
* DB_USERNAME
* DB_PASSWORD
* APP_URL

---

##  Testing & Validation

The system was validated using:

* Functional testing (black-box testing)
* Test data generated through seeders
* Real workflow simulation based on institutional processes

---

##  Future Improvements

* Integration with institutional authentication systems
* Advanced reporting and analytics
* Improved document generation formats
* API performance optimization
* Automated testing coverage
* Deployment configuration

---

##  Thesis Context

This API is part of a diploma thesis focused on improving academic management processes at UCLV.

The system addresses issues such as:

* Information dispersion
* Lack of traceability
* Manual document workflows
* Inefficient administrative processes

---

##  Author

Developed by Dayana Rojas & Suany Daniela Medina Guevara
## My Contribution

I worked on the **Study Plan Modification Management** module, developed as part of my Informatic Engineering diploma thesis at the Universidad Central “Marta Abreu” de Las Villas.

My contribution focused on the analysis, design, implementation and validation of the workflows related to the modification of academic study plans. This included the management of study plan information, modification requests, version tracking, approval and rejection processes, and the integration of academic data used by the system.

Main contributions:

* Implemented functionalities related to the creation, editing, submission and review of study plan modification requests.
* Integrated the module with academic entities such as faculties, departments, careers, curricula, subjects and study plans.
* Developed user workflows for different roles involved in the modification process.
* Supported the approval and rejection process for modification requests.
* Worked on the traceability of changes and request status updates.
* Integrated the frontend with the shared Laravel backend API and MySQL database.
* Participated in functional testing to validate the main system workflows.
* Documented the system behavior as part of the diploma thesis and user manual.
## Study Plan Modification Management Module

In addition to the general academic management functionalities, this backend also supports the **Study Plan Modification Management** module, developed as part of a Computer Engineering diploma thesis at the Universidad Central “Marta Abreu” de Las Villas.

This module uses the shared academic data managed by the backend, including universities, faculties, departments, careers, curricula, subjects and study plans. Based on this information, the system allows the management of modification requests related to academic study plans, supporting institutional workflows where different users and roles participate in the review and validation process.

The module is focused on improving the organization, traceability and control of study plan modification processes by integrating them into the same academic management ecosystem.

