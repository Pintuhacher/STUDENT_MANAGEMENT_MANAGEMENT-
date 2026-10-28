# EduDash - Student Management System (PHP & MySQL)

EduDash is a PHP 8.2 and MySQL student management application with admin, teacher, and student portals.

## Deploy on Render

This repository includes a Dockerfile and `render.yaml` blueprint. In Render, create or update the Web Service from this repository and choose **Docker** (or apply the Blueprint). The container listens on port `10000` and serves the login portal at `/`.

Before the service can log in, configure these environment variables under **Render Dashboard → your Web Service → Environment**:

| Variable | Value |
|---|---|
| `DB_HOST` | Your MySQL server hostname |
| `DB_NAME` | Your database name |
| `DB_USER` | Your database username |
| `DB_PASS` | Your database password (secret) |
| `DB_PORT` | `3306`, unless your provider specifies another port |

The database must allow incoming connections from Render. Import `schema.sql` before using the app. **Warning:** this script drops and recreates its tables; only import it into a new/empty database unless you intend to erase the existing data. Render's filesystem is ephemeral, so uploaded files stored by the app may not persist across deploys/restarts; use external object storage for durable uploads.

## Security notice

Never commit database credentials. The credentials previously present in this repository must be treated as compromised: rotate/change the MySQL password with the database provider, then set the replacement as `DB_PASS` in Render. Remove any old values from the Render service environment after rotation.

## Local development

Run the PHP application with a PHP/Apache server and provide the same `DB_*` environment variables. Import `schema.sql` into a MySQL-compatible database first, taking care not to overwrite data you need to keep.
