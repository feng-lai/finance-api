# Medical equipment networking

This project is a hospital-oriented networking platform designed to unify medical equipment status monitoring, fault reporting, maintenance scheduling, and statistical analysis. Built on **ThinkPHP 5.1.39 LTS**, it leverages a robust MVC structure with extensive support for database interactions, modular extensions, and secure RESTful routing.

## 🌐 Overview

Modern hospitals manage hundreds or even thousands of medical devices. Ensuring their operational status, logging maintenance activities, and planning upgrades can be tedious without a centralized system. This project addresses that gap by offering a lightweight yet powerful **Medical Equipment Networking Platform**, featuring:

- Real-time status display of all registered devices
- Intelligent alert and fault logging
- Equipment maintenance lifecycle management
- Detailed reporting and exportable statistics
- Multi-role access control and user authentication

## 🚀 Getting Started

This system is built with **ThinkPHP 5.1.x**. Make sure PHP ≥ 7.1 is installed.

### 1. Clone the project

```bash
git clone https://github.com/feng-lai/Medical_equipment_networking.git
cd Medical_equipment_networking
```

### 2. Configure your environment

Copy `.env.example` to `.env` or configure database in `config/database.php`.

```php
// Example snippet
return [
  'type'     => 'mysql',
  'hostname' => '127.0.0.1',
  'database' => 'hospital_equipment',
  'username' => 'root',
  'password' => 'your_password',
  ...
];
```

### 3. Run with built-in CLI server (for development)

```bash
php think run
```

Or set up with Apache/Nginx pointing to `/public` directory.

### 4. Access

Visit `http://localhost:8000` in your browser.

## 🧭 Usage Example

The system entry point for command-line operations is `start.php`:

```php
#!/usr/bin/env php
<?php

namespace think;

require __DIR__ . '/thinkphp/base.php';

// Initialize the application
Container::get('app')->path(__DIR__ . '/application/')->initialize();

// Initialize console environment
Console::init();
```

This allows you to run scheduled maintenance commands or database tasks from the terminal.

## 🧱 Project Structure

```
├── application/          # Core MVC application
│   ├── controller/       # Business logic controllers
│   ├── model/            # ORM models for equipment, users, logs
│   ├── view/             # HTML templates (Think template engine)
├── public/               # Web root
│   └── index.php         # Front controller
├── config/               # System configuration files
├── thinkphp/             # Framework core
├── start.php             # CLI entry point
├── composer.json         # Dependencies
```

## 🔐 Features

- **Device Registry**: Add/remove/update hospital devices
- **Fault Reporting**: Automatically records and notifies device malfunctions
- **Maintenance Management**: Tracks repair status and next service due dates
- **Reports Dashboard**: Export Excel/PDF reports; charts powered by ECharts
- **User System**: Admin/staff login, operation logs, permission control
- **Responsive UI**: Mobile-optimized for on-site technicians

## 📦 Built With

- [ThinkPHP 5.1.39 LTS](https://thinkphp.cn/)
- PHP 7.1+
- MySQL / MariaDB
- HTML + CSS + jQuery
- Apache / Nginx

## 🛠️ Framework Version Highlights

**ThinkPHP 5.1.39 LTS** brings major performance and functionality improvements over previous releases, including:

- Enhanced ORM: New `getWhere` support for composite keys
- Improved Redis and Memcached drivers
- Extended support for JSON fields and custom validators
- Support for advanced queries (e.g., `hasWhere`, `withCount`)
- Better compatibility with PHP 7.4+
- Cleaner logging, caching, and routing mechanisms

> To see the full changelog, refer to `docs/CHANGELOG_THINKPHP_5.1.md` or the [official release notes](https://www.thinkphp.cn/topic/69944.html).

## 🧪 Future Improvements

We are considering the following enhancements:

- RESTful API endpoints for mobile apps
- Integration with hospital ERP systems
- Multi-language support (EN/CH)
- QR code labels for device tracking

## 🧑‍💻 Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss your ideas.

## 📄 License

Distributed under the Apache 2.0 License.

---

> Project maintained by Feng-Lai Lab. Last updated: July 2025.
