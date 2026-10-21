# Racik Tea — local development

## Start

From this directory:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000.

The app uses a local MySQL database named `raciktea` by default. Set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env` for your MySQL server, then initialize the schema with `php artisan migrate`. The catalog starts empty and is populated through the authenticated dashboard.

Product photos can be uploaded as JPEG, PNG, or WebP files up to 5 MB. Run `php artisan storage:link` once after deployment so uploaded photos are served from the public storage disk.

## Administrator account

An administrator account is provisioned locally. To create or reset an administrator interactively later, enter a password at the hidden prompt (minimum 12 characters):

```powershell
php artisan admin:create admin@example.com "Store Admin"
```

After setup, open http://127.0.0.1:8000/admin/login. The dashboard and all management actions require authentication. Do not use demo credentials or commit passwords.

The dashboard lets the administrator change the WhatsApp number and delivery fee, and create, edit, remove, or mark menu items unavailable. Menu options and their additional prices are entered as JSON; the server validates option selections and calculates the order total using database prices. Add actual shop details before accepting live orders.
