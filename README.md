# Bookstore

A Symfony-based online bookstore project built with PHP.  
This project includes user authentication, address management, book import from the Google Books API, cart and order features, and transactional emails with Brevo. 

You can access the demo at [https://bookstore.edusilvaramos.com/login](https://bookstore.edusilvaramos.com/login)


## Features

- User registration and login
- Password reset
- Address management
- Book listing and details
- Book import from Google Books API
- Shopping cart
- Orders
- Transactional emails with Brevo
- Store distance/shipping logic with OpenRouteService

## Tech Stack

- PHP 8.4+
- Symfony 8.0
- MySQL
- Doctrine ORM
- Twig
- Symfony Mailer
- Brevo
- Google Books API

## Requirements

- PHP 8.4+
- Composer
- Symfony CLI
- MySQL
- Node.js and npm

## Installation

Clone the repository:

```bash
git clone https://github.com/edusilvaramos/bookstore.git
cd bookstore
````

Install PHP dependencies:

```bash
composer install
```

Install front-end dependencies:

```bash
npm install
```

Build assets:

```bash
npm run build
```

Or for development:

```bash
npm run watch
```

## Environment Configuration

Create a `.env.local` file in the project root and add your local configuration:

```env
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=your_app_secret

DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:8889/booksStore?serverVersion=8.0.32&charset=utf8mb4"

MAILER_DSN=brevo+api://YOUR_BREVO_API_KEY@default

ORS_API_KEY=YOUR_OPENROUTESERVICE_API_KEY
STORE_ADDRESS="11 Quai François Mauriac, 75013 Paris, France"
```
ps: the STORE_ADDRESS is used for calculating distances to customers for shipping purposes.

## Database Setup

Create the database:

```bash
php bin/console doctrine:database:create
```

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

## Useful Commands

Create an admin user:

```bash
php bin/console app:create-admin
```

Import books:

```bash
php bin/console app:seed:books
```

Run a SQL query:

```bash
php bin/console dbal:run-sql "SELECT * FROM user"
```

Start the local server:

```bash
symfony server:start
```

## Development Notes

- This project uses **Brevo** for transactional emails.
- Configure the `MAILER_DSN` in your local environment before testing email features.
- This project uses the **Google Books API** to import books into the catalog.
- This project uses **OpenRouteService** to calculate distance/shipping logic from the store address.
- Store address is configured through the `STORE_ADDRESS` environment variable.
