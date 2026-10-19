<?php
require_once __DIR__ . '/config/db.php';

// Quick check of database connectivity
$dbStatus = 'Connected';
$dbColor = 'success';
$dbMessage = 'Connected to shop_db';
try {
    $tableCount = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'shop_db'")->fetchColumn();
} catch (Exception $e) {
    $dbStatus = 'Error';
    $dbColor = 'danger';
    $dbMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>StoreMyAdmin &mdash; Creative Multi-Page SPA Dashboard (shop_db)</title>
  <meta name="description" content="A creative, modern multi-page single page application dashboard for shop_db MySQL database via phpMyAdmin. Built with HTML5, Bootstrap 5, Vanilla JS, PHP, SQL queries, and Neo-modern CSS.">

  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Custom Theme CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-wrapper">

  <!-- ======================================================================
       1. SIDEBAR NAVIGATION
       ====================================================================== -->
  <aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
      <div class="brand-icon-box">
        <i class="bi bi-lightning-charge-fill"></i>
      </div>
      <div>
        <div class="brand-text-title">StoreMyAdmin</div>
        <div class="brand-text-badge"><i class="bi bi-database me-1"></i>shop_db Engine</div>
      </div>
    </div>

    <div class="sidebar-nav">
      <div class="nav-section-title">Main Navigation</div>

      <a href="#overview" class="nav-link-custom active" data-nav-route="overview">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Overview</span>
      </a>

      <a href="#products" class="nav-link-custom" data-nav-route="products">
        <i class="bi bi-box-seam-fill"></i>
        <span>Products Catalog</span>
        <span class="nav-badge bg-primary-subtle text-primary" id="sidebarProdCount">12</span>
      </a>

      <a href="#orders" class="nav-link-custom" data-nav-route="orders">
        <i class="bi bi-bag-check-fill"></i>
        <span>Orders & Sales</span>
        <span class="nav-badge bg-success-subtle text-success" id="sidebarOrderCount">16</span>
      </a>

      <a href="#customers" class="nav-link-custom" data-nav-route="customers">
        <i class="bi bi-people-fill"></i>
        <span>Customers</span>
      </a>

      <a href="#categories" class="nav-link-custom" data-nav-route="categories">
        <i class="bi bi-tags-fill"></i>
        <span>Categories</span>
      </a>

      <div class="nav-section-title">Analytics & Tools</div>

      <a href="#analytics" class="nav-link-custom" data-nav-route="analytics">
        <i class="bi bi-graph-up-arrow"></i>
        <span>Realtime Analytics</span>
      </a>

      <a href="#sql-console" class="nav-link-custom" data-nav-route="sql-console">
        <i class="bi bi-terminal-fill text-warning"></i>
        <span>SQL Query Runner</span>
        <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem;">LIVE</span>
      </a>

      <div class="nav-section-title">phpMyAdmin Direct</div>

      <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=shop_db" target="_blank" class="nav-link-custom">
        <i class="bi bi-box-arrow-up-right text-info"></i>
        <span>Open phpMyAdmin</span>
      </a>
    </div>

    <div class="sidebar-footer">
      <div class="db-pill">
        <div class="pulse-dot"></div>
        <div class="small">
          <div class="fw-bold text-white">MySQL 3306</div>
          <div class="text-muted" style="font-size: 0.72rem;">Database: <strong>shop_db</strong></div>
        </div>
      </div>
    </div>
  </aside>

  <!-- ======================================================================
       2. MAIN APPLICATION CONTENT
       ====================================================================== -->
  <main class="app-main">

    <!-- Top Navigation Header -->
    <header class="app-header">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
          <i class="bi bi-list fs-5"></i>
        </button>

        <div class="header-search d-none d-md-block">
          <i class="bi bi-search"></i>
          <input type="text" id="globalSearchInput" placeholder="Quick search products or press enter...">
        </div>
      </div>

      <div class="header-actions">
        <!-- Live status badge -->
        <div class="d-none d-sm-flex align-items-center gap-2 px-3 py-1 bg-success-subtle text-success rounded-pill border border-success-subtle small fw-semibold">
          <i class="bi bi-hdd-network-fill"></i>
          <span>shop_db active</span>
        </div>

        <!-- Quick Add Order Button -->
        <button class="btn btn-sm btn-primary-neo d-none d-sm-inline-flex" onclick="App.openCreateOrderModal()">
          <i class="bi bi-plus-lg"></i>
          <span>New Order</span>
        </button>

        <!-- Theme Toggle Button -->
        <button class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode">
          <i class="bi bi-moon-stars-fill"></i>
        </button>

        <!-- Admin Avatar Profile -->
        <div class="d-flex align-items-center gap-2 ps-2 border-start">
          <div class="avatar-initials" style="width: 36px; height: 36px; font-size: 0.8rem;">
            AD
          </div>
          <div class="d-none d-md-block">
            <div class="fw-bold lh-1" style="font-size: 0.85rem;">Shop Admin</div>
            <small class="text-muted" style="font-size: 0.725rem;">Superuser</small>
          </div>
        </div>
      </div>
    </header>

    <!-- Page Views Container (SPA Multi-Pages) -->
    <div class="page-container">

      <!-- ==================================================================
           PAGE 1: OVERVIEW / EXECUTIVE DASHBOARD
           ================================================================== -->
      <section class="page-view active" id="view-overview">
        <!-- Hero Banner -->
        <div class="hero-banner">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <span class="badge bg-white bg-opacity-25 text-white mb-2 px-3 py-1 rounded-pill">
                <i class="bi bi-stars me-1"></i> Executive Commerce Overview
              </span>
              <h2 class="display-6 fw-bold mb-2">Welcome to StoreMyAdmin Dashboard</h2>
              <p class="lead mb-0 text-white-50">
                Direct live synchronization with <code>shop_db</code> database in MySQL via phpMyAdmin. Monitor revenue trends, orders fulfillment, and inventory health in real-time.
              </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
              <button class="btn btn-light fw-bold px-4 py-2 me-2 shadow-sm rounded-pill" onclick="App.openAddProductModal()">
                <i class="bi bi-plus-circle me-1"></i> Add Product
              </button>
              <button class="btn btn-outline-light fw-bold px-4 py-2 rounded-pill" onclick="window.location.hash='#sql-console'">
                <i class="bi bi-terminal me-1"></i> SQL Console
              </button>
            </div>
          </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-4 mb-4">
          <!-- Total Revenue -->
          <div class="col-sm-6 col-xl-3">
            <div class="kpi-card kpi-indigo">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="kpi-label">Total Revenue</div>
                  <div class="kpi-value" id="kpiRevenue">$0.00</div>
                  <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small">
                    <i class="bi bi-arrow-up-short"></i> All time revenue
                  </span>
                </div>
                <div class="kpi-icon-wrap">
                  <i class="bi bi-currency-dollar"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Orders -->
          <div class="col-sm-6 col-xl-3">
            <div class="kpi-card kpi-emerald">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="kpi-label">Total Orders</div>
                  <div class="kpi-value" id="kpiOrders">0</div>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small">
                    Avg: <span id="kpiAov">$0.00</span> / order
                  </span>
                </div>
                <div class="kpi-icon-wrap">
                  <i class="bi bi-bag-check"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Customers -->
          <div class="col-sm-6 col-xl-3">
            <div class="kpi-card kpi-amber">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="kpi-label">Active Customers</div>
                  <div class="kpi-value" id="kpiCustomers">0</div>
                  <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill small">
                    <i class="bi bi-people me-1"></i> Registered
                  </span>
                </div>
                <div class="kpi-icon-wrap">
                  <i class="bi bi-person-heart"></i>
                </div>
              </div>
            </div>
          </div>

          <!-- Total Products & Low Stock -->
          <div class="col-sm-6 col-xl-3">
            <div class="kpi-card kpi-purple">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="kpi-label">Catalog Products</div>
                  <div class="kpi-value" id="kpiProducts">0</div>
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill small">
                    <i class="bi bi-exclamation-triangle me-1"></i> <span id="kpiLowStock">0</span> low stock
                  </span>
                </div>
                <div class="kpi-icon-wrap">
                  <i class="bi bi-boxes"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
          <!-- Revenue Trend Chart -->
          <div class="col-lg-8">
            <div class="card-neo h-100">
              <div class="card-header-neo">
                <div>
                  <h5 class="fw-bold mb-0">Revenue Timeline & Sales Curve</h5>
                  <small class="text-muted">Aggregated order values across timeline in shop_db</small>
                </div>
                <button class="btn btn-sm btn-outline-neo" onclick="App.loadOverview()">
                  <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
              </div>
              <div class="card-body-neo">
                <div style="height: 320px; position: relative;">
                  <canvas id="chartRevenueTrend"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Category Share Doughnut -->
          <div class="col-lg-4">
            <div class="card-neo h-100">
              <div class="card-header-neo">
                <div>
                  <h5 class="fw-bold mb-0">Sales by Category</h5>
                  <small class="text-muted">Revenue contribution share</small>
                </div>
              </div>
              <div class="card-body-neo d-flex flex-column justify-content-center">
                <div style="height: 320px; position: relative;">
                  <canvas id="chartCategoryShare"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Breakdown & Low Stock Alert Widgets -->
        <div class="row g-4">
          <!-- Recent Orders Table -->
          <div class="col-lg-8">
            <div class="card-neo">
              <div class="card-header-neo">
                <div class="d-flex align-items-center gap-2">
                  <h5 class="fw-bold mb-0">Recent Orders</h5>
                  <span class="badge bg-primary-subtle text-primary border rounded-pill">Latest 5</span>
                </div>
                <a href="#orders" class="btn btn-sm btn-outline-neo">
                  View All Orders <i class="bi bi-arrow-right"></i>
                </a>
              </div>
              <div class="table-responsive">
                <table class="table-neo">
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Customer</th>
                      <th>Date</th>
                      <th>Items</th>
                      <th>Total</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="recentOrdersTableBody">
                    <tr><td colspan="7" class="text-center py-4 text-muted">Loading orders...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Low Stock & Order Status -->
          <div class="col-lg-4">
            <div class="card-neo mb-4">
              <div class="card-header-neo">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                  <h5 class="fw-bold mb-0">Inventory Alerts</h5>
                </div>
                <a href="#products" class="btn btn-sm btn-link text-decoration-none">Catalog</a>
              </div>
              <div class="card-body-neo p-3" id="lowStockContainer">
                <div class="text-muted text-center py-3">Checking inventory...</div>
              </div>
            </div>

            <div class="card-neo">
              <div class="card-header-neo">
                <h5 class="fw-bold mb-0">Orders by Status</h5>
              </div>
              <div class="card-body-neo">
                <div style="height: 180px; position: relative;">
                  <canvas id="chartOrderStatus"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           PAGE 2: PRODUCTS CATALOG VIEW
           ================================================================== -->
      <section class="page-view" id="view-products">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h3 class="fw-bold mb-1">Products Catalog & Stock Management</h3>
            <p class="text-muted mb-0">
              Manage inventory, prices, categories, and real-time sales metrics for each product.
            </p>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-outline-neo" onclick="App.exportProductsCSV()">
              <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
            </button>
            <button class="btn btn-primary-neo" onclick="App.openAddProductModal()">
              <i class="bi bi-plus-lg me-1"></i> Add Product
            </button>
          </div>
        </div>

        <!-- Filters Bar -->
        <div class="card-neo mb-4">
          <div class="card-body-neo py-3">
            <div class="row g-3 align-items-center">
              <div class="col-md-5">
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control border-start-0" id="searchProductInput" placeholder="Search product name or description...">
                </div>
              </div>
              <div class="col-md-3">
                <select class="form-select" id="filterCategory">
                  <option value="">All Categories</option>
                </select>
              </div>
              <div class="col-md-2">
                <select class="form-select" id="filterStock">
                  <option value="all">All Stock Status</option>
                  <option value="in">In Stock (> 30)</option>
                  <option value="low">Low Stock (1-30)</option>
                  <option value="out">Out of Stock (0)</option>
                </select>
              </div>
              <div class="col-md-2 text-end">
                <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill" id="productsCountBadge">
                  Loading products...
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Products Table -->
        <div class="card-neo">
          <div class="table-responsive">
            <table class="table-neo">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Product Name</th>
                  <th>Category</th>
                  <th>Unit Price</th>
                  <th>Stock Status</th>
                  <th>Status</th>
                  <th>Sales (Units / Total)</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="productsTableBody">
                <tr><td colspan="8" class="text-center py-5 text-muted">Loading products...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           PAGE 3: ORDERS MANAGEMENT VIEW
           ================================================================== -->
      <section class="page-view" id="view-orders">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h3 class="fw-bold mb-1">Orders & Transactions</h3>
            <p class="text-muted mb-0">Track order lifecycle, line items, customer invoices, and delivery fulfillment.</p>
          </div>
          <button class="btn btn-primary-neo" onclick="App.openCreateOrderModal()">
            <i class="bi bi-cart-plus me-1"></i> Create New Order
          </button>
        </div>

        <!-- Filter Bar -->
        <div class="card-neo mb-4">
          <div class="card-body-neo py-3">
            <div class="row g-3 align-items-center">
              <div class="col-md-6">
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control border-start-0" id="searchOrderInput" placeholder="Search by customer name, email, or order ID...">
                </div>
              </div>
              <div class="col-md-4">
                <select class="form-select" id="filterOrderStatus">
                  <option value="All">All Order Statuses</option>
                  <option value="Pending">Pending</option>
                  <option value="Processing">Processing</option>
                  <option value="Shipped">Shipped</option>
                  <option value="Delivered">Delivered</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
              </div>
              <div class="col-md-2 text-end">
                <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill" id="ordersCountBadge">
                  Loading orders...
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Orders Table -->
        <div class="card-neo">
          <div class="table-responsive">
            <table class="table-neo">
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th>Items & Units</th>
                  <th>Total Amount</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="ordersTableBody">
                <tr><td colspan="7" class="text-center py-5 text-muted">Loading orders...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           PAGE 4: CUSTOMERS DIRECTORY VIEW
           ================================================================== -->
      <section class="page-view" id="view-customers">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h3 class="fw-bold mb-1">Customers Directory</h3>
            <p class="text-muted mb-0">Customer lifetime values, purchase orders history, and contact directory.</p>
          </div>
          <button class="btn btn-primary-neo" onclick="App.openAddCustomerModal()">
            <i class="bi bi-person-plus me-1"></i> Add Customer
          </button>
        </div>

        <!-- Filters Bar -->
        <div class="card-neo mb-4">
          <div class="card-body-neo py-3">
            <div class="row g-3 align-items-center">
              <div class="col-md-7">
                <div class="input-group">
                  <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
                  <input type="text" class="form-control border-start-0" id="searchCustomerInput" placeholder="Search customer by name, email, phone...">
                </div>
              </div>
              <div class="col-md-3">
                <input type="text" class="form-control" id="filterCustomerCity" placeholder="Filter by City...">
              </div>
              <div class="col-md-2 text-end">
                <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill" id="customersCountBadge">
                  Loading customers...
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Customers Table -->
        <div class="card-neo">
          <div class="table-responsive">
            <table class="table-neo">
              <thead>
                <tr>
                  <th>Customer</th>
                  <th>Contact Info</th>
                  <th>Location</th>
                  <th>Total Orders</th>
                  <th>Lifetime Spend</th>
                  <th>Last Order</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="customersTableBody">
                <tr><td colspan="7" class="text-center py-5 text-muted">Loading customers...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           PAGE 5: CATEGORIES VIEW
           ================================================================== -->
      <section class="page-view" id="view-categories">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h3 class="fw-bold mb-1">Product Categories & Inventory Valuation</h3>
            <p class="text-muted mb-0">Organize merchandise taxonomies and inspect category-level sales performance.</p>
          </div>
          <button class="btn btn-primary-neo" onclick="App.openAddCategoryModal()">
            <i class="bi bi-folder-plus me-1"></i> Add Category
          </button>
        </div>

        <div class="row" id="categoriesGrid">
          <div class="col-12 text-center py-5 text-muted">Loading categories...</div>
        </div>
      </section>

      <!-- ==================================================================
           PAGE 6: REALTIME ANALYTICS VIEW
           ================================================================== -->
      <section class="page-view" id="view-analytics">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
          <div>
            <h3 class="fw-bold mb-1">Deep Business Analytics & Leaderboards</h3>
            <p class="text-muted mb-0">Aggregated metric computations from <code>shop_db</code> database queries.</p>
          </div>
          <button class="btn btn-outline-neo" onclick="App.loadAnalytics()">
            <i class="bi bi-arrow-clockwise me-1"></i> Refresh Metrics
          </button>
        </div>

        <!-- Top Selling Products Leaderboard -->
        <div class="card-neo mb-4">
          <div class="card-header-neo">
            <div>
              <h5 class="fw-bold mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i> Top Selling Products Leaderboard</h5>
              <small class="text-muted">Ranked by total sales revenue and quantity fulfilled</small>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table-neo">
              <thead>
                <tr>
                  <th>Rank</th>
                  <th>Product</th>
                  <th>Unit Price</th>
                  <th>Quantity Sold</th>
                  <th>Total Revenue</th>
                  <th>Revenue Contribution</th>
                </tr>
              </thead>
              <tbody id="analyticsTopProducts">
                <tr><td colspan="6" class="text-center py-4 text-muted">Loading leaderboard...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Category Performance Matrix -->
        <div class="card-neo">
          <div class="card-header-neo">
            <h5 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps text-primary me-2"></i> Category Revenue Distribution</h5>
          </div>
          <div class="table-responsive">
            <table class="table-neo">
              <thead>
                <tr>
                  <th>Category Name</th>
                  <th>Catalog Products</th>
                  <th>Units Sold</th>
                  <th>Total Revenue ($)</th>
                </tr>
              </thead>
              <tbody id="analyticsCategoryTable">
                <tr><td colspan="4" class="text-center py-4 text-muted">Loading categories...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ==================================================================
           PAGE 7: INTERACTIVE SQL QUERY RUNNER & PHPMYADMIN CONSOLE
           ================================================================== -->
      <section class="page-view" id="view-sql-console">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
          <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-terminal-fill text-warning me-2"></i> SQL Query Runner for shop_db</h3>
            <p class="text-muted mb-0">
              Run raw SQL queries, inspect table structures, and analyze execution times directly against MySQL / phpMyAdmin.
            </p>
          </div>
          <div class="d-flex gap-2">
            <a href="http://localhost/phpmyadmin/index.php?route=/database/structure&db=shop_db" target="_blank" class="btn btn-outline-neo">
              <i class="bi bi-box-arrow-up-right me-1"></i> Open in phpMyAdmin
            </a>
            <button class="btn btn-outline-neo" onclick="App.exportSqlResultCSV()">
              <i class="bi bi-download me-1"></i> Export Query CSV
            </button>
          </div>
        </div>

        <!-- Presets Query Chips -->
        <div class="card-neo mb-3">
          <div class="card-body-neo py-2">
            <div class="small fw-bold text-muted mb-2"><i class="bi bi-bookmark-star-fill text-warning me-1"></i> Click to run preset SQL queries:</div>
            <div class="d-flex flex-wrap gap-2" id="sqlPresetChips">
              <span class="text-muted small">Loading preset queries...</span>
            </div>
          </div>
        </div>

        <!-- Query Editor Card -->
        <div class="card-neo mb-4">
          <div class="card-body-neo">
            <label for="sqlQueryInput" class="form-label fw-bold d-flex justify-content-between align-items-center">
              <span>SQL Query Editor:</span>
              <span class="badge bg-secondary-subtle text-secondary border font-monospace">shop_db</span>
            </label>
            <textarea class="sql-editor-box mb-3" id="sqlQueryInput" spellcheck="false">SELECT p.product_id, p.product_name, c.category_name, p.price, p.stock_quantity, COALESCE(SUM(oi.quantity), 0) AS total_sold, ROUND(COALESCE(SUM(oi.quantity * oi.unit_price), 0), 2) AS total_revenue
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
LEFT JOIN order_items oi ON p.product_id = oi.product_id
GROUP BY p.product_id
ORDER BY total_revenue DESC;</textarea>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div class="d-flex align-items-center gap-3">
                <button class="btn btn-primary-neo" onclick="App.runSqlQuery()">
                  <i class="bi bi-play-fill fs-5"></i> Execute Query
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('sqlQueryInput').value=''">
                  Clear
                </button>
              </div>

              <!-- Execution Metrics -->
              <div class="d-flex align-items-center gap-3 font-monospace small">
                <div>Execution Time: <strong class="text-primary" id="sqlMetricTime">0.00 ms</strong></div>
                <div>Returned: <strong class="text-success" id="sqlMetricRows">0 rows</strong></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Results Table Card -->
        <div class="card-neo">
          <div class="card-header-neo">
            <h5 class="fw-bold mb-0">Query Result Set</h5>
          </div>
          <div class="card-body-neo p-0">
            <div id="sqlRawMessage" class="alert font-monospace m-3" style="display: none;"></div>
            <div class="table-responsive" style="max-height: 480px;">
              <table class="table-neo">
                <thead id="sqlResultHeader">
                  <tr><th>Run a query above to view results</th></tr>
                </thead>
                <tbody id="sqlResultBody">
                  <tr><td class="text-center py-4 text-muted">No query executed yet. Click "Execute Query" or choose a preset.</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

    </div><!-- /page-container -->

  </main>
</div><!-- /app-wrapper -->

<!-- ==========================================================================
     MODALS SECTION
     ========================================================================== -->

<!-- 1. Add / Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="productForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="productModalTitle">Add New Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="productIdInput">
          
          <div class="mb-3">
            <label class="form-label fw-bold">Product Name *</label>
            <input type="text" class="form-control" id="productNameInput" required placeholder="e.g. Ergonomic Keyboard">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Category *</label>
              <select class="form-select" id="productCategoryInput" required>
                <option value="">Select Category</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Price ($) *</label>
              <input type="number" step="0.01" class="form-control" id="productPriceInput" min="0" required placeholder="0.00">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Stock Quantity *</label>
              <input type="number" class="form-control" id="productStockInput" min="0" required placeholder="0">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Status *</label>
              <select class="form-select" id="productActiveInput">
                <option value="1">Active (Visible)</option>
                <option value="0">Inactive (Hidden)</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea class="form-control" id="productDescInput" rows="3" placeholder="Detailed product specifications or description..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-neo">Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Create Order Modal with Dynamic Line-Item rows -->
<div class="modal fade" id="createOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="createOrderForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Create New Order</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label fw-bold">Customer *</label>
              <select class="form-select" id="newOrderCustomer" required>
                <option value="">Select Customer...</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Order Date *</label>
              <input type="date" class="form-control" id="newOrderDate" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold">Initial Status</label>
              <select class="form-select" id="newOrderStatus">
                <option value="Pending">Pending</option>
                <option value="Processing">Processing</option>
                <option value="Shipped">Shipped</option>
                <option value="Delivered">Delivered</option>
              </select>
            </div>
          </div>

          <!-- Line items header -->
          <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold">Order Items (Products & Quantity)</span>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="App.addOrderItemRow()">
              <i class="bi bi-plus-lg"></i> Add Item
            </button>
          </div>

          <!-- Dynamic Items Container -->
          <div id="orderItemsRows" class="mb-4">
            <!-- populated dynamically by JS -->
          </div>

          <!-- Total Calculation Banner -->
          <div class="p-3 rounded bg-light border d-flex justify-content-between align-items-center">
            <span class="fw-bold">Calculated Order Total:</span>
            <span class="fs-4 fw-bold text-success" id="newOrderTotalDisplay">$0.00</span>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-neo">Submit Order</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. View Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-bold" id="modalOrderTitle">Order Details</h5>
          <small class="text-muted">Order Date: <span id="modalOrderDate"></span></small>
        </div>
        <div id="modalOrderStatus"></div>
      </div>
      <div class="modal-body p-4">
        <!-- Customer Info Card -->
        <div class="card-neo mb-4 p-3 bg-light">
          <div class="row g-2">
            <div class="col-sm-6">
              <small class="text-muted d-block">Customer Name</small>
              <strong id="modalCustName">-</strong>
            </div>
            <div class="col-sm-6">
              <small class="text-muted d-block">Email Address</small>
              <span id="modalCustEmail">-</span>
            </div>
            <div class="col-sm-6">
              <small class="text-muted d-block">Phone</small>
              <span id="modalCustPhone">-</span>
            </div>
            <div class="col-sm-6">
              <small class="text-muted d-block">Delivery Location</small>
              <span id="modalCustLocation">-</span>
            </div>
          </div>
        </div>

        <!-- Items Table -->
        <h6 class="fw-bold mb-3">Order Items Breakdown</h6>
        <div class="table-responsive mb-4">
          <table class="table-neo">
            <thead>
              <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th class="text-end">Line Total</th>
              </tr>
            </thead>
            <tbody id="modalOrderItemsBody">
            </tbody>
          </table>
        </div>

        <!-- Total Summary -->
        <div class="d-flex justify-content-between align-items-center border-top pt-3">
          <div>Total Units: <strong id="modalOrderUnits">0</strong></div>
          <div class="fs-4 fw-bold text-success">Total Amount: <span id="modalOrderTotal">$0.00</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- 4. Update Order Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form id="updateStatusForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Update Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="statusOrderId">
          <label class="form-label fw-bold">Select New Status</label>
          <select class="form-select" id="statusSelectInput">
            <option value="Pending">Pending</option>
            <option value="Processing">Processing</option>
            <option value="Shipped">Shipped</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-neo">Save Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 5. Add / Edit Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="customerForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="customerModalTitle">Add New Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="customerIdInput">
          
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold">First Name *</label>
              <input type="text" class="form-control" id="custFirstName" required placeholder="John">
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Last Name *</label>
              <input type="text" class="form-control" id="custLastName" required placeholder="Doe">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Email Address *</label>
            <input type="email" class="form-control" id="custEmail" required placeholder="john.doe@example.com">
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Phone Number</label>
            <input type="text" class="form-control" id="custPhone" placeholder="e.g. +6012-3456789">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label fw-bold">City</label>
              <input type="text" class="form-control" id="custCity" placeholder="Kuala Lumpur">
            </div>
            <div class="col-6">
              <label class="form-label fw-bold">Country</label>
              <input type="text" class="form-control" id="custCountry" value="Malaysia" placeholder="Malaysia">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-neo">Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 6. Add / Edit Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="categoryForm">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="categoryModalTitle">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <input type="hidden" id="categoryIdInput">
          
          <div class="mb-3">
            <label class="form-label fw-bold">Category Name *</label>
            <input type="text" class="form-control" id="catNameInput" required placeholder="e.g. Accessories">
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea class="form-control" id="catDescInput" rows="3" placeholder="Category summary or notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary-neo">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Chart.js 4.4 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<!-- Application Master JS -->
<script src="assets/js/app.js"></script>

</body>
</html>
