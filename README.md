# Wholesale Outreach Email Platform

A comprehensive B2B wholesale outreach CRM with AI-powered email generation, controlled sending, and full analytics.

## Features

- **Vendor Management** — CRUD, CSV import, duplicate detection, priority/status tracking
- **AI Email Generation** — Kimi AI (Moonshot) integration for personalized outreach emails
- **Email Approval Workflow** — Draft → Approve → Queue → Send (no automatic mass sending)
- **Controlled Sending** — Rate limits, delays, sending schedule, pause/resume, test mode
- **Campaign Management** — Group vendors, track progress per campaign
- **Analytics Dashboard** — Charts, stats, AI usage tracking, cost estimation
- **Security** — Role-based access (Admin/Manager/Staff/Viewer), audit logs, encrypted SMTP credentials
- **Suppression List** — Opt-out handling, bounce suppression
- **Email Templates** — Reusable templates with variable substitution

## Tech Stack

- **Backend**: PHP 8.3+, Laravel 12+, MySQL 8+
- **Frontend**: Blade + Livewire, Tailwind CSS, Alpine.js, Chart.js
- **AI**: Kimi AI (Moonshot) API
- **Packages**: Spatie Permission, Spatie Activitylog, Maatwebsite Excel

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd wholesale-email-app

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your .env file:
#   - Set DB_CONNECTION=mysql and database credentials
#   - Set KIMI_API_KEY for AI features

# Run migrations and seed
php artisan migrate
php artisan db:seed

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

## Default Login

- **Email**: admin@wholesale.com
- **Password**: password

## Queue & Scheduler

For email sending to work, run the queue worker:

```bash
php artisan queue:work --tries=1 --timeout=120
```

For scheduled processing, add to your crontab:

```bash
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```

## Key Principles

- **Never automatically send emails** — explicit user selection and approval required
- **Duplicate prevention** — system checks before sending to avoid double-contacting
- **Suppression respected** — opted-out vendors are never contacted
- **Rate limited** — daily/hourly limits with configurable delays
- **Test mode** — redirect all emails to a test address for safe testing
- **Audit logged** — all actions tracked with user, IP, and timestamp

## Configuration

### SMTP Settings
Configure via Settings → SMTP in the UI. Credentials are encrypted in the database.

### AI Configuration
Configure via Settings → AI Configuration. Set `KIMI_API_KEY` in `.env` or via the UI.

### Sending Limits
Configure via Settings → Sending Limits:
- Daily/hourly email limits
- Delay between emails (random or fixed)
- Sending schedule (days of week, start/end times)
- Emergency pause/resume/cancel

## License

Proprietary. All rights reserved.
