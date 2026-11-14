# HELP2U

**Students helping students.** HELP2U is a peer support platform for HELP University. Students post a support request (academic, technology, new student or general help), get matched with student volunteers who have the right skills and availability, and book a one-hour session face-to-face or online. After the session, the student rates it.

Built for **BIT216 Software Engineering Principles** (Group 4) as a solution to the UniHELP case study.

---

## Team and use cases

| Member | Use case | Branch |
| --- | --- | --- |
| Tan Yen Tze | UC1: Manage Student & Volunteer Profile | `tan_yen_tze` |
| Kaung Myat Si Thu | UC2: Support Request & Volunteer Matching | `kaung_myat_si_thu` |
| Chan Zi Hao (team lead) | UC3: Manage Support Sessions & Feedback | `chan_zi_hao` |

## Features

**UC1: Accounts and profiles**
- Registration restricted to `@helplive.edu.my` emails, activated with a 6-digit email code
- Login with two-factor authentication (emailed code) and a limit on failed password attempts
- Forgot password with a reset code
- Student profile, plus an optional volunteer profile: support categories, skills, support modes and weekly availability

**UC2: Requests and matching**
- Create a support request with category, description, preferred time and mode
- Volunteer search filtered by the request's category, with mode and day filters, ranked by availability on the preferred day, whether the volunteer is free to take a request, and average rating, with the top result marked "Best Match"
- Send a request to a volunteer; the volunteer accepts or declines it
- Requests not answered within 48 hours expire automatically
- In-app notifications with an unread pop-up after login

**UC3: Sessions and feedback**
- Book a one-hour session from the volunteer's available slots (double-booking is prevented)
- Reschedule or cancel a session; the other participant is notified
- The volunteer marks a session as completed once it has started
- The student rates the session (1–5 stars) with an optional comment
- Session and feedback history

## Tech stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13 (PHP 8.4) |
| Frontend | Blade templates, Tailwind CSS 4, Vite |
| Database | SQLite (via Laravel migrations and seeders) |
| Testing | Pest (feature and integration tests) |
| CI | GitHub Actions: runs the full test suite on every push and pull request |

## Project structure

Laravel keeps the frontend and backend in one project, so the code follows Laravel's standard layout instead of separate `frontend/` and `backend/` folders:

```
HELP2U/
├── app/                  # Backend: controllers, models, enums, notifications, services
├── resources/views/      # Frontend: Blade pages and components
├── routes/               # web.php (pages) and console.php (scheduled jobs)
├── database/
│   ├── migrations/       # Database schema
│   └── seeders/          # Categories, sample volunteers and demo accounts
├── tests/Feature/        # Automated tests for all three use cases
├── documentation/            # Project plan, requirements and design documents
├── .github/workflows/    # CI pipeline
└── README.md
```

## Getting started

### Requirements
- PHP 8.4 with the `pdo_sqlite` extension
- Composer
- Node.js 22 and npm

### Setup

```bash
git clone https://github.com/Rexchan06/HELP2U.git
cd HELP2U

composer install
npm install
npm run build

cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate

php artisan migrate:fresh --seed                 # schema, categories and sample volunteers
php artisan db:seed --class=DemoSeeder           # demo accounts below

php artisan serve
```

Open http://localhost:8000. If `migrate` asks to create the SQLite database file, answer **yes**.

### Email codes during development
`.env.example` sets `MAIL_MAILER=log`, so no real emails are sent. Verification, login and password reset codes are written to `storage/logs/laravel.log`. Search for the latest 6-digit code there.

### Sending real emails (optional)
To deliver codes to real inboxes, set these in your local `.env` using a Gmail account with an [app password](https://support.google.com/accounts/answer/185833), then run `php artisan config:clear`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-account@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_ADDRESS="your-account@gmail.com"
MAIL_FROM_NAME="HELP2U"
```

Never commit real credentials. `.env` is git-ignored for this reason. With SMTP on, the demo accounts below won't receive their codes because their addresses aren't real inboxes, so switch back to `MAIL_MAILER=log` to use them.

### Request expiry job (optional)
Requests also expire when someone opens the relevant pages. To run the hourly background check as well:

```bash
php artisan schedule:work
```

## Demo accounts

All demo accounts use the password **`Demo1234`**. Login still asks for the emailed 2FA code (see above).

| Name | Email | Role | Starting state |
| --- | --- | --- | --- |
| Alex Tan | alex.tan@helplive.edu.my | Student | Request accepted by Daniel, ready to book a session |
| Mei Ling | mei.ling@helplive.edu.my | Student | Session already booked with Daniel |
| Daniel Wong | daniel.wong@helplive.edu.my | Volunteer | Programming help, Mon–Fri 9am–5pm |
| Priya Nair | priya.nair@helplive.edu.my | Volunteer | Campus orientation, Tue/Thu 10am–1pm |

To reset everything to this state, run the two seed commands from Setup again.

## Running the tests

```bash
php artisan test
```

On Windows, if `php artisan test` fails with a temporary file "Permission denied" error, run Pest directly:

```bash
vendor\bin\pest
```

Tests use an in-memory database, so they never touch your local data.

## Development workflow

- `master` holds the integrated, working version of the app.
- Each member developed their use case on their own branch (see the team table) and kept it up to date with `master`.
- Work was merged through **pull requests** using merge commits, so each member's commit history is kept.
- Every push and pull request runs the test suite on GitHub Actions; pull requests are merged only when CI passes.
- Fixes found during integration testing went through short-lived `hotfix/...` branches and their own pull requests.

## Documentation

Project documents are in [`documentation/`](documentation/):
- Assignment 1 Task 1: Project Plan
- Assignment 1 Task 2: Requirements Specification and Design
- Iteration 1 presentation
- UniHELP case study