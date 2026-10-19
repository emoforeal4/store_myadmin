# StoreMyAdmin &mdash; Creative Multi-Page SPA Dashboard (shop_db)

A modern, responsive, multi-page single page application (SPA) dashboard connected directly to the MySQL database `shop_db` via **phpMyAdmin** (`http://localhost/phpmyadmin/`).

Built with:
1. **HTML5**: Semantic tags, accessible modals, responsive layouts.
2. **Bootstrap 5.3 & Bootstrap Icons**: Responsive grid, card components, pills, badges, interactive modals.
3. **JavaScript (Vanilla ES6+) & Chart.js 4.4**: Hash router for seamless multi-page transitions, interactive charts, AJAX CRUD requests, live search, debouncing, dynamic form rows, and CSV exports.
4. **PHP (PDO)**: Secure REST API architecture with parameterized prepared statements, transactions, and error handling.
5. **SQL Queries**: Comprehensive relational queries (JOINs, aggregates, GROUP BY, subqueries, and a live SQL console runner with presets).
6. **CSS (Bespoke Neo-Modern Design)**: Custom glassmorphism, curated dark/light mode toggle with persistence, Google Fonts (`Outfit` & `Plus Jakarta Sans`), glowing badges, animated counters, and polished scrollbars.

---

## 🚀 Live Access URLs
- **StoreMyAdmin Dashboard**: [http://localhost/store_myadmin/](http://localhost/store_myadmin/)
- **phpMyAdmin (shop_db)**: [http://localhost/phpmyadmin/index.php?route=/database/structure&db=shop_db](http://localhost/phpmyadmin/index.php?route=/database/structure&db=shop_db)

---

## 🌟 Multi-Page SPA Views Included
1. **Executive Overview (`#overview`)**:
   - Live animated KPI cards (Total Revenue, Total Orders, Active Customers, Catalog Products).
   - Interactive Chart.js charts:
     - Revenue Trend Timeline (smooth bezier curve area chart).
     - Sales by Category (doughnut chart).
     - Orders by Status (bar chart).
   - Recent 5 Orders table with status badges and quick view buttons.
   - Low Stock Inventory Alert widget.
2. **Products Catalog & Stock Management (`#products`)**:
   - Filter by Category, Stock status (In Stock, Low Stock, Out of Stock), and active status.
   - Real-time search by product name and description.
   - Add Product Modal with category selector and input validation.
   - Edit Product Modal with current values pre-filled.
   - Delete Product with foreign-key constraint protection.
   - Export Products to CSV.
3. **Orders & Transactions (`#orders`)**:
   - Filter by Status (Pending, Processing, Shipped, Delivered, Cancelled) and search by customer/order.
   - View Order Details modal showing customer address, line items, unit prices, and line totals.
   - Quick Status update modal.
   - **Create New Order Modal**: Dynamic line-item builder with customer selection, product pickers, quantity controls, and live calculated order totals.
4. **Customers Directory (`#customers`)**:
   - Customer cards/table with generated avatar initials.
   - Contact details (email, phone, city, country).
   - Aggregated metrics: total orders placed, lifetime spend, and last order date.
   - Add & Edit Customer modals with email uniqueness verification.
5. **Categories & Inventory Valuation (`#categories`)**:
   - Interactive category cards with product count, stock unit summation, and total inventory value.
   - Add & Edit Category modals.
6. **Realtime Analytics (`#analytics`)**:
   - Top Selling Products Leaderboard with visual sales share percentage bars.
   - Category performance breakdown matrix.
7. **Interactive SQL Query Runner (`#sql-console`)**:
   - Execute raw SQL queries directly against `shop_db`.
   - Clickable presets:
     - *Top 5 Best Selling Products by Revenue*
     - *Customer Lifetime Value & Total Orders*
     - *Category Sales & Inventory Health*
     - *Recent Orders with Full Customer Details*
     - *Low Stock Alert (< 50 items)*
     - *Database Tables & Row Counts*
   - Execution performance metrics (time in milliseconds and rows affected).
   - Dynamic table renderer adapting to any SELECT query columns.
   - Export query results to CSV.
   - Direct link to phpMyAdmin.

---

## 📁 Directory Structure
```
store_myadmin/
├── api/
│   ├── categories.php   # CRUD & inventory aggregation API
│   ├── customers.php    # Customer metrics & CRUD API
│   ├── orders.php       # Transactional order creation & status API
│   ├── products.php     # Search, filter, CRUD product API
│   ├── query.php        # Interactive SQL runner & presets API
│   └── stats.php        # Executive dashboard analytics & charts API
├── assets/
│   ├── css/
│   │   └── style.css    # Neo-modern glassmorphic stylesheet & dark/light theme
│   └── js/
│       └── app.js       # Client-side router, Chart.js logic, AJAX CRUD, toast notifications
├── config/
│   └── db.php           # PDO MySQL connection to shop_db (127.0.0.1:3306)
├── index.php            # Master single-page multi-view application
└── README.md            # Documentation and user guide
```
