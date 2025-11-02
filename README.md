# Medieval Portfolio APIs

A lightweight PHP project that provides a small admin dashboard and tracking API for a portfolio website. This repository contains:

- `index.php` — simple admin login page
- `dashboard/` — admin dashboard, assets, and included helpers
- `api/` — API endpoints (tracking, stats) and middleware
- `logs/` — application logs (created at runtime)
- `.env.example` / `.env` — environment configuration

This README explains how to install, configure, run, and test the project locally (WAMP on Windows was used during development).

## Features

- Visitor tracking API (`api/track.php`) with API key protection and CORS
- Persistent visitor storage (`visitors` table) and aggregated daily stats (`stats` table)
- Dashboard with simple login (admin user auto-created on first run)
- CSRF protection for the dashboard login
- Session manager with remember-me support
- Basic rate limiting and request logging for the API
- Security headers and input sanitization

## Requirements

- PHP 7.4+ with PDO MySQL
- MySQL (or MariaDB)
- WAMP / XAMPP or another local webserver on Windows

## Quick start (Windows, WAMP)

1. Copy the project to your WAMP `www` directory, for example:
   - `C:\wamp64\www\medival-portfolio-apis`
2. Copy `.env.example` to `.env` and edit values as needed. The project comes with a `.env` that is useful for local development; change the secrets before production.

   Important settings in `.env`:
   - `APP_ENV` — `local` or `production`
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
   - `API_KEY` — used by the tracking API (replace in production)
   - `SECRET_KEY` — used for session-related features

3. Start WAMP and ensure Apache + MySQL are running.
4. Visit the site at:

   <http://localhost/medival-portfolio-apis/>

   The default admin credentials (created on first run) are controlled by the `.env` values. If you kept the example defaults, the username is `admin` and the password is `password123`. Change this immediately in production.

## Configuration

- `dashboard/config.php` loads environment variables via `dashboard/inc/config_loader.php` and defines application constants.
- `dashboard/inc/config_loader.php` reads `.env` from the project root. Ensure `.env` exists and is readable by PHP.
- Logs are written to the `logs/` directory; ensure it is writable by the web server.

## Database setup

On first run the app will try to create the required tables automatically:

- `users` — admin users
- `visitors` — raw visitor records (created by `api/track.php` if missing)
- `stats` — daily aggregated stats (created by `api/stats_manager.php`)
- `remember_tokens` — if remember-me is used (created by session manager on first insertion)

If you prefer to create tables manually, here are example SQL statements:

Create `visitors` (as used by the project):

```sql
CREATE TABLE IF NOT EXISTS visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  agent VARCHAR(255) NOT NULL,
  page VARCHAR(255) NOT NULL,
  referrer VARCHAR(255) NOT NULL,
  time DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Create `stats` (daily aggregation):

```sql
CREATE TABLE IF NOT EXISTS stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  date DATE NOT NULL,
  page_url VARCHAR(255) NOT NULL,
  visits INT DEFAULT 0,
  unique_visits INT DEFAULT 0,
  referrer_source VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `date_page_referrer` (`date`, `page_url`, `referrer_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Adjust the schema to your needs before deploying to production.

## API: Tracking endpoint

- Endpoint: `POST /api/track.php`
- Headers:
  - `Content-Type: application/json`
  - `X-API-Key: <your-api-key>` (must match `API_KEY` in `.env`)
- Body (JSON):
  - `page` — required (string)

Response examples:

- Success (HTTP 200):

```json
{ "status": "success", "message": "Visitor tracked and stats updated", "logged": { /* visitor object */ } }
```

- Error (HTTP 400/401/429/500):

```json
{ "status": "error", "message": "Description of the error" }
```

### Browser console test snippet

Open your browser dev tools and paste this snippet into the Console. Update the URL and API key if required.

```javascript
async function testTracking() {
  try {
    const res = await fetch('http://localhost/medival-portfolio-apis/api/track.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-Key': 'MEDIEVAL_API_KEY_12345' // change to your API key in .env
      },
      body: JSON.stringify({ page: '/test-page' })
    });
    const json = await res.json();
    console.log('Tracking response:', json);
  } catch (err) {
    console.error('Tracking error:', err);
  }
}

// Run the test
// testTracking();
```

Or a quick curl example from your terminal (PowerShell on Windows):

```powershell
curl -X POST "http://localhost/medival-portfolio-apis/api/track.php" -H "Content-Type: application/json" -H "X-API-Key: MEDIEVAL_API_KEY_12345" -d '{"page":"/test"}'
```

## Dashboard (admin)

- Login page: `index.php` in the project root
- After login you are redirected to `dashboard/index.php`
- Default admin user is created automatically when the `users` table is initialized. Change the password immediately.

## Logs

- Application logs are written to the `logs/` folder (one file per day). Use them to debug errors and monitor API requests.

## Security notes

- Always run the site over HTTPS in production.
- Replace default secrets in `.env` (`API_KEY`, `SECRET_KEY`, admin password).
- Set `APP_ENV=production` in `.env` for stricter behavior (some debug logs are disabled in production mode).
- The dashboard uses CSRF tokens and security headers. Keep these features enabled.
- Monitor `logs/` for any suspicious activity (failed logins, rate-limit warnings, invalid API key attempts).

## Troubleshooting

- If the app cannot connect to the database, check `.env` DB credentials and that MySQL is running.
- If tables are not created automatically, ensure the database user has `CREATE` privileges.
- Ensure `logs/` is writable by the web server user.

## Useful SQL queries

Get today's stats:

```sql
SELECT * FROM stats WHERE date = CURRENT_DATE;
```

Get most visited pages:

```sql
SELECT page_url, SUM(visits) AS total_visits
FROM stats
GROUP BY page_url
ORDER BY total_visits DESC
LIMIT 20;
```

Get visits by referrer:

```sql
SELECT referrer_source, SUM(visits) AS total_visits
FROM stats
GROUP BY referrer_source
ORDER BY total_visits DESC;
```
