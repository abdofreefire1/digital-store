# Manual acceptance tests

- [ ] Import `sql/schema.sql` in a clean test database.
- [ ] Missing `includes/config.php` shows a setup error, not a stack trace.
- [ ] Arabic landing page renders right-to-left on mobile and desktop.
- [ ] Language toggle switches page text to French and direction to left-to-right.
- [ ] Invalid email, missing consent, invalid payment method, and malformed input do not create orders.
- [ ] Valid order generates a random one-time tracking token; only its hash is stored.
- [ ] Public order code alone cannot expose an order.
- [ ] A customer cannot confirm activation unless status is ACTIVATION_SENT.
- [ ] Proof upload rejects files over 4 MB, invalid MIME types, and failed uploads.
- [ ] Uploaded proof is stored outside the public web root with a random storage name.
- [ ] Uploading proof never marks an order PAID.
- [ ] Admin login rejects invalid credentials and applies session ID regeneration on success.
- [ ] CSRF-protected forms reject invalid tokens.
- [ ] Admin cannot mark an order paid without independently recording a verified payment reference.
- [ ] Admin setting changes are logged.
- [ ] No wallet address, secret, API key, or default administrator password is committed.
