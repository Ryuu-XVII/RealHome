# RealHome - Real Estate Listing & Portal Platform

RealHome is a PHP and MySQL real estate portal designed to help clients search, calculate mortgage costs, and wishlist properties, while enabling partner agents to manage their listings through a secure back-office dashboard.

The portal features a modern, responsive user interface with glassmorphic cards, transition animations, Leaflet.js neighborhood proximity mapping, and interactive calculators.

---

## 🚀 Features

### 1. Client Search & Filtering
* **Advanced Multi-Criteria Filters**: Filter properties by listing type (Sale vs. Rent), category (House, Apartment, Townhouse, Commercial), area/address, minimum beds, minimum baths, and custom price brackets.
* **Instant Loading Feedback**: Skeleton screens show during search/filter operations to improve visual response.

### 2. Smart Hybrid Wishlist System
* **Offline Local Saving**: Guests can save properties to a local wishlist (`localStorage`) immediately.
* **Automatic Cloud Sync**: Once a client logs in or signs up, their local offline wishlist automatically merges and syncs into their MySQL database profile.
* **Background Toggles**: Wishlist items are dynamically added or removed via AJAX calls (`wishlist_ajax.php`) without causing full page reloads.

### 3. Mortgage & Duty Calculator
* **Repayment Calculator**: An interactive widget on the property detail page that automatically loads the listing price.
* **Adjustable Inputs**: Allows adjusting deposit percentages (defaulting to 10%), prime lending rates (defaulting to 11.75%), and loan terms (10 to 30 years).
* **Detailed Repayment Outputs**: Instantly calculates monthly repayment totals, total interest, minimum monthly salary requirements, and estimates South African SARS transfer duties.

### 4. Interactive Proximity Mapping
* **Leaflet.js Map Integration**: Loads an open-source visual neighborhood map pinning the property address.
* **Radial Safe Zones**: Renders a highlighted circular proximity overlay around the neighborhood address.

### 5. Agent Back-Office Dashboard
* **Dynamic Analytics**: Dashboard tracks listing counts, mock views, and client enquiries.
* **Seamless Listing CRUD**: Create, edit, and delete properties with drag-and-drop file upload fields.
* **Asset Manager**: Overhauled image grid manager allowing direct deletion of individual uploaded photos.

---

## 🛠️ Technology Stack
* **Backend**: PHP 8.x (using secure, parameterized MySQLi statements)
* **Frontend**: HTML5, Vanilla JavaScript, CSS3
* **Database**: MySQL (relational schema with constraints and cascades)
* **Mapping API**: Leaflet.js (zero-API key mapping)

---

## ⚙️ Installation & Local Setup

### Prerequisites
* A local WAMP/LAMP stack (e.g. **Laragon**, **XAMPP**, or **WampServer**).
* PHP 8.x (ensure the `mysqli` and `pdo_mysql` extensions are enabled in your active `php.ini`).
* MySQL server.

### Setup Instructions

1. **Import Database**:
   * Open your database management tool (HeidiSQL, phpMyAdmin, or Adminer).
   * Create a new database named `ryuuxvii_Real_Home`.
   * Import the SQL schema and seed data from [ryuuxvii_Real_Home.sql](./ryuuxvii_Real_Home.sql).

2. **Configure Database Connection**:
   * Open [db_connection.php](./db_connection.php).
   * The connection script contains automatic local development fallbacks. By default, it will attempt to connect to localhost MySQL using `root` and an empty password. Adjust the configuration if you are running custom database credentials.

3. **Start Local Web Server**:
   * If using Laragon, click **"Start All"** to boot Apache and MySQL.
   * Alternatively, launch PHP's built-in development server in the root of the project:
     ```bash
     php -S localhost:8000
     ```

4. **Visit the Portal**:
   * Open your browser and navigate to: **`http://localhost:8000`**

---

## 📂 Project Directory Structure

```
├── js/
│   └── app.js             # Main client-side scripts, wishlist sync, calculators, map
├── uploads/               # User uploaded listing and avatar images (Git ignored)
├── images/                # Local graphic assets and visual backups (Git ignored)
├── index.php              # Public homepage with hero search and featured list
├── listing.php            # Search listings and filtering dashboard
├── property_details.php   # Gallery, Leaflet map, mortgage calculator, enquiries
├── agents.php             # Agent profiles grid with active listings count
├── contact.php            # Direct email message script
├── profile.php            # Agent dashboard CRUD panel and list creation
├── edit_property.php      # Edit listing properties and manage active photos
├── wishlist_ajax.php      # Secure AJAX endpoint for database wishlist actions
├── login.html             # Multi-role segmented authentication gateway
├── register.html          # Dynamic account sign-up form
└── db_connection.php      # Resilient MySQL connection script with local fallback
```

---

## 🔐 Credentials for Testing

* **Client Profile (Client Login)**:
  * Username: `client_john`
  * Password: `password123`

* **Partner Agent Profile (Agent Login)**:
  * Username: `Adnaan`
  * Password: `admin`
