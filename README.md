# Bookstore

Bookstore is a Symfony 8 application for browsing books, managing user accounts, handling carts and orders, and administering catalog data through EasyAdmin.

## Main Features

- Public catalog with book listing, detail pages, and category filtering.
- User registration with email verification.
- Authentication and protected user area.
- Password reset by email.
- User profile and address management.
- Shopping cart with quantity updates, duplicate item merging, and item removal.
- Order management.
- Admin dashboard powered by EasyAdmin.
- Email delivery through Symfony Mailer with Brevo support.
- Optional local email testing with Mailpit.
- Shipping estimation based on address distance using OpenRouteService.
- Book import from Google Books API.
- Console commands for admin bootstrap and database utility tasks.

## Tech Stack

- PHP 8.4+
- Symfony 8
- MySQL 8
- Doctrine ORM and Doctrine Migrations
- Twig
- Webpack Encore
- Stimulus and Turbo
- EasyAdmin

## Requirements

Install the following tools before running the project:

- PHP 8.4 or newer
- Composer
- Node.js and npm
- MySQL 8 or newer
- A MySQL client is optional but useful for debugging
- Symfony CLI is optional but convenient for local serving

## External Services

This project can integrate with the following external services:

- Brevo: sends verification emails and password reset emails in real environments.
- OpenRouteService: geocodes addresses and calculates driving distance for shipping estimates.
- Google Books API: imports books into the catalog using a console command.
- Mailpit: optional local SMTP testing inbox for development.

## Environment Variables

The project uses environment variables from `.env` and local overrides from `.env.local`.

At minimum, review and configure these values:

```dotenv
APP_ENV=dev
APP_SECRET=change-me
DATABASE_URL="mysql://root:root@127.0.0.1:8889/booksStore?serverVersion=8.0.32&charset=utf8mb4"
MAILER_DSN=brevo+api://YOUR_BREVO_API_KEY@default
DEFAULT_URI=http://127.0.0.1:8000

GOOGLE_BOOKS_API_KEY=
ORS_API_KEY=
STORE_ADDRESS="Your bookstore address here"
```

Notes:

- For Brevo, use your own API key or SMTP credentials, for example `brevo+api://YOUR_KEY@default`.
- For local development with Mailpit, `smtp://127.0.0.1:1025` is enough.
- `ORS_API_KEY` and `STORE_ADDRESS` are required if you want shipping estimation to work.
- `GOOGLE_BOOKS_API_KEY` exists in the service configuration, but the current book import command does not require it.
- Never commit real secrets to the repository.

## Installation

Clone the project, then install PHP and frontend dependencies:

```bash
composer install
npm install
```

## Local Services

Before running the application, make sure these local services are available on your machine:

- MySQL on the host and port defined in `DATABASE_URL`
- An SMTP server only if you want local email testing instead of Brevo

You have two common options for email during development:

- Mailpit running locally on port `1025`
- Brevo configured directly through `MAILER_DSN`

## Database Setup

Create the database and run migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

If the database already exists, just run the migration command.

Example local MySQL configuration used in this project:

```dotenv
DATABASE_URL="mysql://root:root@127.0.0.1:8889/booksStore?serverVersion=8.0.32&charset=utf8mb4"
```

## Build Frontend Assets

For development:

```bash
npm run dev
```

For automatic rebuilds while coding:

```bash
npm run watch
```

For a production build:

```bash
npm run build
```

## Run the Application

You can run the app with Symfony CLI:

```bash
symfony server:start
```

Or with PHP's built-in server:

```bash
php -S 127.0.0.1:8000 -t public
```

Then open:

- Application: `http://127.0.0.1:8000`
- Mailpit inbox: `http://127.0.0.1:8025`

## Recommended First-Time Setup

After installing dependencies and starting services, this is a practical bootstrap flow:

```bash
composer install
npm install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
npm run dev
php -S 127.0.0.1:8000 -t public
```

This assumes MySQL is already running locally and matches your `DATABASE_URL`.

## Seed Data and Admin Access

Create an admin user:

```bash
php bin/console app:create-admin admin@example.com StrongPassword123 Admin User +33000000000 1990-01-01
```

Import books from Google Books:

```bash
php bin/console app:seed:books fiction 20 --lang=en
```

Useful variants:

```bash
php bin/console app:seed:books fantasy 30 --append --lang=en
php bin/console app:seed:books history 20 --purge --lang=fr
```

## Useful Console Commands

```bash
php bin/console app:create-admin <email> <password>
php bin/console app:seed:books [subject] [count] [--append] [--purge] [--lang=xx]
php bin/console app:user:delete <email>
php bin/console lint:container
php bin/console doctrine:migrations:migrate
php bin/phpunit
```

## Email Setup

### Local development

If you use Mailpit locally, configure:

```dotenv
MAILER_DSN=smtp://127.0.0.1:1025
```

Then inspect outgoing emails in `http://127.0.0.1:8025`.

If you do not use Mailpit, keep Brevo configured instead.

### Brevo

To use Brevo in a real environment, set one of the following DSNs in `.env.local` or in your server environment:

```dotenv
MAILER_DSN=brevo+api://YOUR_BREVO_API_KEY@default
# or
MAILER_DSN=brevo+smtp://USERNAME:PASSWORD@default
```

Brevo is used for flows such as:

- account verification emails
- password reset emails

## Shipping Estimation Setup

Shipping estimation depends on OpenRouteService and a valid store address.

Configure:

```dotenv
ORS_API_KEY=your_openrouteservice_key
STORE_ADDRESS="10 Rue Example, Paris, France"
```

The application then:

- geocodes the store address
- geocodes the customer address
- calculates driving distance
- converts distance into shipping price tiers

Current shipping price rules are:

- up to 5 km: 8.90 EUR
- up to 15 km: 10.99 EUR
- above 15 km: 15.90 EUR

## Main Application Areas

- `/book`: public catalog and book details
- `/register`: account creation
- `/verify/email`: email verification
- `/login`: authentication
- `/reset-password`: password reset flow
- `/cart`: shopping cart
- `/order`: order management
- `/address`: customer addresses
- `/admin`: EasyAdmin dashboard for administrators

## Testing and Validation

Run the main checks with:

```bash
php bin/console lint:container
php bin/phpunit
```

## Development Notes

- The committed `.env` file still contains a PostgreSQL example, but the active local configuration can override it through `.env.local`.
- The current local setup uses MySQL on `127.0.0.1:8889`.
- Registration requires mail delivery to complete email verification properly.
- Password reset also depends on a working mailer configuration.
- Admin access requires a user with `ROLE_ADMIN`.

## Troubleshooting

If emails are not being sent:

- check `MAILER_DSN`
- verify that Mailpit is running or that Brevo credentials are valid
- inspect the Mailpit UI on port `8025`

If shipping estimation fails:

- verify `ORS_API_KEY`
- verify `STORE_ADDRESS`
- confirm the user address is complete and valid

If assets are missing:

- run `npm install`
- run `npm run dev`

If the database connection fails:

- make sure MySQL is running
- confirm host, port, database name, user, and password in `DATABASE_URL`
- verify `DATABASE_URL`