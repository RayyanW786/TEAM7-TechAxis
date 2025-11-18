# Project Setup Guide

## 1. Prerequisites

You will need:

- **Git**
- **PHP 8.2+**
- **Composer** – https://getcomposer.org/
- **PostgreSQL 16+**  - https://www.enterprisedb.com/downloads/postgres-postgresql-downloads

> This guide assumes **Windows**. On macOS/Linux, the general steps are the same but commands/tools may differ slightly.

After you have cloned or pulled the repository, open a terminal in the project root folder.

---

## 2. Configure PHP for PostgreSQL

Laravel talks to PostgreSQL through the **PDO pgsql** driver.  
You must make sure the `pdo_pgsql` and `pgsql` extensions are enabled in your `php.ini`.

### 2.1 Find the active `php.ini`

In a terminal / PowerShell:

```bash
php --ini
````

Look for the line:

```text
Loaded Configuration File: C:\path\to\php.ini
```

This is the `php.ini` file you need to edit.

### 2.2 Enable the PostgreSQL extensions

1. Open the `php.ini` file (from the path above) in a text editor.
In visual studio code you should be able to `control + click` to open the file 

2. Search inside the file for these lines:
    (You can do this using control + F in visual studio code and prompting `pgsql` into the search bar)
   ```ini
   ;extension=pdo_pgsql
   ;extension=pgsql
   ```

3. **Uncomment both** by removing the leading `;`:

   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```

4. Save the file.

5. Close and reopen your terminal so PHP reloads the configuration.
(In visual studio code you may need to do `control + shift + p` -> `Developer: Reload Window` or simply close the whole app)

### 2.3 Verify the extensions are loaded

In a new terminal:

```bash
php -m | findstr pgsql
```

You should see something like:

```text
pdo_pgsql
pgsql
```

If you don’t see them, re-check Step 2.2.

---

## 3. PostgreSQL: Create Role and Database

We use a dedicated PostgreSQL **role** (login user) and **database** for this project.

1. Open **SQL Shell (psql)** or a terminal and connect as the PostgreSQL superuser (usually `postgres`):

   ```bash
   psql -U postgres
   ```

2. Run the following SQL (you can change the password but make sure to update your `.env` file):

   ```sql
   -- Create an application role (login user)
   CREATE ROLE tech_axis WITH LOGIN PASSWORD 'tech_axis_password';

   -- Create the database owned by that role
   CREATE DATABASE tech_axis OWNER tech_axis;
   ```

3. Exit psql:

   ```sql
   \q
   ```

### 3.1 Test the new role

From a terminal:

```bash
psql -U tech_axis -d tech_axis -h 127.0.0.1
```

If you get a prompt like:

```text
tech_axis=>
```

then the role and database are set up correctly.

### 3.2 Apply the database schema

From the **project root** (where `schema.sql` lives):

```bash
psql -U tech_axis -d tech_axis -f schema.sql
```
This creates all tables, functions, triggers, and views defined in `schema.sql`.

```bash
cd src
php artisan migrate
```

---

## 4. Laravel Setup

All Laravel files are inside `src/`.

### 4.1 Install PHP dependencies

From the project root:

```bash
cd src
composer install
```

### 4.2 Create `.env` and app key

In `src/`:

```bash
copy .env.example .env   # Windows
# or:
cp .env.example .env     # macOS / Linux
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

### 4.3 Configure the database connection

Open `src/.env` in a text editor and update the database section:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=tech_axis
DB_USERNAME=tech_axis
DB_PASSWORD=tech_axis_password
```

These values must match what you used in **Section 3** when creating the role and database.

---

## 5. Run Database Migrations

From `src/`:

```bash
php artisan migrate
```

This will:

* connect to PostgreSQL using the `.env` settings
* create the `migrations` table
* run all pending migrations to set up the schema

If you see:

* `could not find driver (Connection: pgsql)` → Go back to **Section 2** and ensure `pdo_pgsql` and `pgsql` are enabled.
* `could not connect to server` → Make sure PostgreSQL is running and the host/port/user/password in `.env` are correct.

---

## 6. Running the Application

### 6.1 Start the Laravel development server

From `src/`:

```bash
php artisan serve
```

The app will be available at:

* [http://127.0.0.1:8000](http://127.0.0.1:8000)


## 7. Redis

### 7.1 Install Redis in WSL (Ubuntu)

1. Open the **Ubuntu (WSL)** terminal.
2. Run:

```bash
curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg

echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/redis.list

sudo apt-get update
sudo apt-get install redis
````

3. Start Redis in the background:

```bash
redis-server --daemonize yes
```

4. Test that Redis is running:

```bash
redis-cli ping
```

Expected output:

```text
PONG
```

> When you open Ubuntu (WSL) to work on the project, run:
>
> ```bash
> redis-server --daemonize yes
> ```
>
> again if Redis is not already running.

---

### 7.2 Laravel Redis configuration

1. After cloning the repo and from the project root:

```bash
cd src
composer install
```

2. In `src/.env`, set:

```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

3. Apply config changes:

```bash
cd src
php artisan config:clear
php artisan cache:clear
```

---

## 8. Common Laravel Commands (from `src/`)

* Run migrations:

  ```bash
  php artisan migrate
  ```

* Rollback last batch of migrations:

  ```bash
  php artisan migrate:rollback
  ```

* Clear configuration cache:

  ```bash
  php artisan config:clear
  ```

* Clear application cache:

  ```bash
  php artisan cache:clear
  ```

