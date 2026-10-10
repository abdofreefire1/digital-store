# Installation and deployment

1. Create a MySQL database/user in your hosting control panel.
2. Import `sql/schema.sql` using phpMyAdmin or the MySQL CLI.
3. Copy `includes/config.example.php` to `includes/config.php`. Set database variables and a long random app key via environment configuration.
4. Ensure the private proof directory is outside the public document root and writable by PHP. Do not expose uploaded payment images through a public URL.
5. Enforce HTTPS before accepting customer information. Set PHP to hide errors from public responses and log errors privately.
6. Create the first administrator from a trusted CLI environment; never ship a default username/password. Run `php bin/create-admin.php your_admin_name` in an interactive terminal. The script asks for the password without echoing it and stores only a `password_hash()` hash. On a non-interactive host, set `CPDZ_ADMIN_PASSWORD` in the process environment, run the command, and remove that environment value immediately afterward. Never put the password in source code or a committed file.
7. Configure service duration, support contact, and verified payment instructions in the admin interface before launch.
8. Configure a scheduled job for exchange-rate updates only after choosing and testing a trusted API. If no verified rate is available, hide the estimate rather than inventing one.
9. Test on staging: order creation, tracking-token privacy, proof upload validation, admin review, payment confirmation, and rejection flows.
10. Back up the database and private proof directory securely; publish a privacy and refund policy before taking orders.

Do not claim Canva affiliation unless formally authorized. Do not ask customers for their Canva password or one-time authentication codes. A screenshot is not proof that funds have arrived.
