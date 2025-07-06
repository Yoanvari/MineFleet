# 🚛 MineFleet – Vehicle Reservation & Monitoring System

MineFleet is a web-based application designed for managing and monitoring the usage of company vehicles, especially in a mining company setting. It supports vehicle reservations, approval workflows, fuel tracking, service records, and reporting.

---

## 📦 Tech Stack

| Component    | Version        |
| ------------ | -------------- |
| PHP          | 8.2+           |
| Laravel      | 12.x           |
| MySQL        | 8.0+           |
| Node.js      | 22+ (for Vite) |
| Tailwind CSS | 4.x            |
| Laravel Volt | ✅ Yes         |

---

## 👤 Default Users

> You can use the following test accounts to login:

| Role     | Email                     | Password      |
| -------- | ------------------------- | ------------- |
| Admin    | `admin1@minefleet.com`    | `password123` |
| Approver | `approver1@minefleet.com` | `password123` |
| Approver | `approver2@minefleet.com` | `password123` |
| Approver | `approver3@minefleet.com` | `password123` |

---

## 📚 Features Overview

-   🔐 Authentication (Login/Register/Logout)
-   🧑 Role-based Access (Admin & Approver)
-   🧾 Vehicle Reservation (with vehicle, driver, destination, date)
-   ✅ Multi-level Approval Flow (min. 2 levels)
<!-- -   ⛽ Fuel Tracking (Fuel Logs)
-   🛠️ Vehicle Service Management (Service Records)
-   📊 Dashboard with usage statistics
-   📁 Exportable Reports (Excel) -->

---

## 🚀 Installation Guide

1.  **Clone the project**
    ```bash
    git clone https://github.com/Yoanvari/MineFleet.git
    cd minefleet
    ```
2.  **Install dependencies**
    ```bash
    composer install
    npm install && npm run build
    ```
3.  **Environment setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4.  **Database**
    -   Create a MySQL database, e.g. minefleet
    -   Update .env accordingly
    ```bash
    DB_DATABASE=minefleet
    DB_USERNAME=root
    DB_PASSWORD=
    ```
5.  **Run migrations and seeders**
    ```bash
    php artisan migrate --seed
    ```
6.  **Serve the app**
    ```bash
    composer run dev
    ```
