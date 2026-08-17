# Stripe Testing Dashboard (TestKit)

A Core PHP dashboard and resource management interface for Stripe testing built directly with `stripe/stripe-php` and Tailwind CSS CDN.

## Features

- **Dashboard (`index.php`)**: Live available and pending Stripe balances, metric cards, and recent transactions preview.
- **Customers (`customers.php`)**: Live customer accounts with cursor pagination (`starting_after`, `ending_before`), limit selector (10, 25, 50, 100), and search.
- **Customer Details (`customer-detail.php`)**: Customer profile, saved payment cards, active subscriptions, and charge history.
- **Transactions (`transactions.php`)**: Live charges with cursor pagination, gross/refund amounts, status badges, payment method details, and customer filters.
- **Subscriptions (`subscriptions.php`)**: Live recurring subscriptions with status filters (All, Active, Trialing, Past Due, Canceled), plan pricing, and period dates.
- **Key Vault & Connect (`connect.php`)**: Connect Stripe keys with live balance verification. Keys are stored in PHP sessions encrypted using **AES-256-CBC** (no plaintext keys in session or code).
- **Authentication (`login.php` / `logout.php`)**: Admin authentication with brute force mitigation (`sleep(2)` verification delay).

## Setup & Installation

1. Clone the repository and install Composer dependencies:
   ```bash
   composer install
   ```

2. Copy the environment template:
   ```bash
   cp .env.example .env
   ```

3. Set your admin credentials in `.env`:
   ```env
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD=your_secure_password
   ```

4. Start the built-in PHP server:
   ```bash
   php -S localhost:8000
   ```

5. Open `http://localhost:8000` in your browser, log in, and connect your Stripe Secret Key (`sk_test_...`) via **Stripe Keys** (`connect.php`).
