# Canva Pro DZ

Arabic/French PHP 8.3 + MySQL application for handling service requests, manual activation checks, and manual payment-proof review.

## Status
Initial implementation scaffold. Payment is never auto-confirmed from an uploaded image. No real Binance wallet or payment credentials are bundled.

## Requirements
- PHP 8.3 with PDO MySQL, fileinfo, sessions
- MySQL 8 / MariaDB
- HTTPS in production

## Install
1. Create a MySQL database and import `sql/schema.sql`.
2. Copy `includes/config.example.php` to `includes/config.php`, then set environment-specific DB settings outside public source control.
3. Configure the web server document root to this directory's public root (or apply the included .htaccess guidance).
4. Create the first admin using the CLI command described in `docs/INSTALL.md`; never use a hard-coded default password.
5. Set the real payment instructions and service duration in the admin settings only after verifying you are authorized to provide the advertised service.
6. Test order creation and proof upload on staging before accepting real customers.

## Important
This app does not claim Canva affiliation. Only use activation/invitation methods you are authorized to use and accurately disclose duration, eligibility, and refund terms. FX conversion is an estimate and is not a payment quote.
