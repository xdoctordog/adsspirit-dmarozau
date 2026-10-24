# MarketHub Webhook Integration (Laravel)

A Laravel-based service designed to process incoming webhooks from the MarketHub marketplace, store orders, manage status histories, queue email notifications, and provide a read-only API for the store's admin panel.

## 🖥️ Environment & Prerequisites

This project was developed and tested in the following environment:
*   **OS:** Windows 11 Pro
*   **WSL:** WSL 2 with Ubuntu 24.04 LTS
*   **Containerization:** Docker Desktop (with WSL 2 integration enabled)

Before running the project, ensure you have:
1.  **WSL 2** installed on your Windows machine.
2.  **Ubuntu 24.04** (or a similar Linux distribution) configured as your WSL 2 environment.
3.  **Docker Desktop** installed and running, with WSL 2 integration enabled for your Ubuntu distribution (`Settings -> Resources -> WSL Integration`).
4.  **Git** installed.

---

## 🚀 Getting Started (From Scratch)

Follow these exact steps to get the project running locally. **Important:** All subsequent commands must be executed from inside your WSL terminal (e.g., Ubuntu), not from Windows PowerShell or CMD.

**1. Open your WSL terminal and navigate to your working directory:**
Open your WSL 2 terminal and move to the folder where you want to store the project (e.g., your home directory).

**2. Clone the repository:**
```bash
git clone git@github.com:xdoctordog/adsspirit-dmarozau.git
cd adsspirit-dmarozau
```

**3. Prepare the environment and dependencies:**
Since the `vendor` directory is not tracked in Git, install the PHP dependencies first:
```bash
composer install
cp .env.example .env
```

**4. Start the Docker environment:**
```bash
./vendor/bin/sail up -d
```
*(If this command hangs or freezes, please refer to the **Troubleshooting** section at the bottom).*

**5. Generate application key and run migrations with seeders:**
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
```

**6. Start the Queue Worker:**
To process outgoing emails in the background, you need a running worker. Open a **new WSL terminal tab/window** (keeping the previous one open is fine, but it's not strictly necessary) and run:
```bash
./vendor/bin/sail artisan queue:work
```

---

## 🌐 Ports & Addresses

Once all containers are up and running, the services are available at:

*   **Application (API):** [http://localhost](http://localhost)
*   **Mailpit Web Interface (Email testing):** [http://localhost:8025](http://localhost:8025)
*   **Database (MySQL):** `127.0.0.1:3306`
    *(Note: Use a database client like HeidiSQL or DBeaver to connect here, not a web browser. Credentials are in your `.env` file).*

---

## 🧪 Testing

### Automated Tests
You can run the test suite using the following commands:

```bash
# Run Webhook feature tests
./vendor/bin/sail test tests/Feature/Webhooks/

# Run Admin API feature tests
./vendor/bin/sail test --filter=OrderApiTest

# Run all tests
./vendor/bin/sail test
```

### Manual Testing (Webhooks & API)
The repository includes several bash scripts to simulate incoming webhooks from MarketHub.

Make them executable first:
```bash
chmod +x bash/*
```

Then run them to simulate different order lifecycle events:
```bash
# Create a new order
./bash/send_webhook_create_order.sh

# Mark order as paid
./bash/send_webhook_create_paid_order.sh

# Mark order as shipped
./bash/send_webhook_create_shiped_order.sh

# Cancel an order
./bash/send_webhook_create_cancelled_order.sh

# Test concurrent webhook handling
./bash/test_concurrent_webhooks.sh
```
*After running these scripts, you can check the dispatched emails in the Mailpit interface.*

### API Endpoints for Admin
You can view the generated orders using the API endpoints:
*   **List Orders:** `GET http://localhost/api/orders`
*   **Filter by Status:** `GET http://localhost/api/orders?status=paid`
*   **Single Order Details:** `GET http://localhost/api/orders/{id}`

---

## 🛠️ Troubleshooting

### `./vendor/bin/sail up -d` hangs or gets stuck
If the command freezes with no output and does not start the containers, this is a known issue with Docker Desktop and WSL 2 integration.

To resolve it, restart the WSL subsystem:
1. Open **Windows PowerShell** (or Command Prompt) as Administrator.
2. Run the following command:
   ```powershell
   wsl --shutdown
   ```
3. Re-open your WSL terminal, navigate to the project folder, and run `./vendor/bin/sail up -d` again.
4. **Important:** Restarting WSL kills all background processes. You must start the Redis queue worker again in a separate terminal window to resume email processing:
   ```bash
   ./vendor/bin/sail artisan queue:work
   ```

### Language / Translation issues
If you change the `APP_LOCALE` in `.env` or update email templates, but the changes are not reflected, you need to clear the application cache. **After clearing the cache, you must restart Redis and the queue worker to apply the new configurations.**

1. Clear Laravel cached configuration and views:
   ```bash
   ./vendor/bin/sail artisan config:clear
   ./vendor/bin/sail artisan view:clear
   ./vendor/bin/sail artisan optimize:clear
   ```
2. Restart the queue worker in your terminal:
   ```bash
   ./vendor/bin/sail artisan queue:work
   ```

---
*Note: The original task description is located in the `task/` directory and was left untouched.*
