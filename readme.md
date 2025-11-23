# Production PHP Architecture (Deebuk Platform)

A robust, scalable, and secure web application architecture built with **Pure PHP** and **Pure CSS**. designed for high performance and maintainability without the overhead of heavy frameworks.

## 🚀 Key Features

- **Backend:** Pure PHP 8.x with `mysqli` (Procedural pattern with secure wrapper).
- **Database:** Secure connection handling, Environment variables (`.env`), and Prepared Statements.
- **Frontend:** Modular CSS Architecture (Design Tokens, Typography, Strict Breakpoints).
- **JavaScript:** Organized Module Pattern (IIFE) with jQuery fallback support.
- **Structure:** PSR-4 Autoloading via Composer and clear separation of concerns (`src/`, `public/`, `config/`).
- **Security:** Error suppression in production, XSS protection helpers, and secure assets management.

## 📂 Project Structure

```text
root/
├── assets/                 # Compiled assets (CSS, JS, Fonts, Images)
│   ├── css/
│   │   ├── abstracts/      # Design tokens & Typography
│   │   └── main.css        # Main stylesheet
│   └── js/                 # Application logic (Module pattern)
├── config/                 # Database & System configurations
├── public/                 # Web root (Entry point)
│   ├── components/         # HTML Partials (Head, Navbar, Scripts)
│   └── index.php           # Main entry file
├── src/                    # Backend Logic (PSR-4 Autoloaded)
│   ├── Services/           # Business Logic (Auth, etc.)
│   └── Utils/              # Helper functions
├── vendor/                 # Composer dependencies
├── .env                    # Environment variables (Git ignored)
└── composer.json           # Dependency definitions