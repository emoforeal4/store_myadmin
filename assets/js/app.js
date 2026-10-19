/**
 * StoreMyAdmin - Single Page Application Multi-Pages Dashboard Logic
 * Powered by Bootstrap 5, Chart.js, Vanilla JS & REST API (PHP + MySQL shop_db)
 */

const App = {
  state: {
    currentRoute: 'overview',
    stats: null,
    products: [],
    categories: [],
    orders: [],
    customers: [],
    theme: localStorage.getItem('theme') || 'light',
    charts: {}
  },

  api: {
    base: 'api/',
    async get(endpoint, params = {}) {
      const url = new URL(this.base + endpoint, window.location.href);
      Object.keys(params).forEach(k => {
        if (params[k] !== undefined && params[k] !== null && params[k] !== '') {
          url.searchParams.append(k, params[k]);
        }
      });
      const res = await fetch(url.toString());
      return res.json();
    },
    async post(endpoint, data = {}) {
      const res = await fetch(this.base + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      return res.json();
    },
    async put(endpoint, data = {}) {
      const res = await fetch(this.base + endpoint, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      return res.json();
    },
    async delete(endpoint, params = {}) {
      const url = new URL(this.base + endpoint, window.location.href);
      Object.keys(params).forEach(k => url.searchParams.append(k, params[k]));
      const res = await fetch(url.toString(), {
        method: 'DELETE'
      });
      return res.json();
    }
  },

  init() {
    this.initTheme();
    this.initRouter();
    this.initGlobalEvents();
    this.loadCommonData();
    this.navigateTo(window.location.hash.replace('#', '') || 'overview');
  },

  // --------------------------------------------------------------------------
  // Theme Toggle (Dark / Light)
  // --------------------------------------------------------------------------
  initTheme() {
    document.documentElement.setAttribute('data-theme', this.state.theme);
    this.updateThemeButton();
    const btn = document.getElementById('themeToggleBtn');
    if (btn) {
      btn.addEventListener('click', () => {
        this.state.theme = this.state.theme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', this.state.theme);
        localStorage.setItem('theme', this.state.theme);
        this.updateThemeButton();
        this.recreateCharts();
      });
    }
  },

  updateThemeButton() {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    if (this.state.theme === 'dark') {
      btn.innerHTML = '<i class="bi bi-sun-fill text-warning"></i>';
      btn.title = "Switch to Light Mode";
    } else {
      btn.innerHTML = '<i class="bi bi-moon-stars-fill text-indigo"></i>';
      btn.title = "Switch to Dark Mode";
    }
  },

  // --------------------------------------------------------------------------
  // SPA Client Router
  // --------------------------------------------------------------------------
  initRouter() {
    window.addEventListener('hashchange', () => {
      const route = window.location.hash.replace('#', '') || 'overview';
      this.navigateTo(route);
    });

    document.querySelectorAll('[data-nav-route]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const route = link.getAttribute('data-nav-route');
        window.location.hash = '#' + route;
      });
    });

    // Mobile sidebar toggle
    const toggleSidebar = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('appSidebar');
    if (toggleSidebar && sidebar) {
      toggleSidebar.addEventListener('click', () => {
        sidebar.classList.toggle('show');
      });
      // Close when clicking outside on mobile
      document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && !sidebar.contains(e.target) && !toggleSidebar.contains(e.target)) {
          sidebar.classList.remove('show');
        }
      });
    }
  },

  navigateTo(route) {
    const validRoutes = ['overview', 'products', 'orders', 'customers', 'categories', 'analytics', 'sql-console'];
    if (!validRoutes.includes(route)) {
      route = 'overview';
    }
    this.state.currentRoute = route;

    // Update active nav links
    document.querySelectorAll('[data-nav-route]').forEach(link => {
      if (link.getAttribute('data-nav-route') === route) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });

    // Update page views
    document.querySelectorAll('.page-view').forEach(view => {
      view.classList.remove('active');
    });

    const activeView = document.getElementById(`view-${route}`);
    if (activeView) {
      activeView.classList.add('active');
    }

    // Close mobile sidebar if open
    const sidebar = document.getElementById('appSidebar');
    if (sidebar) sidebar.classList.remove('show');

    // Load data for specific route
    switch (route) {
      case 'overview':
        this.loadOverview();
        break;
      case 'products':
        this.loadProducts();
        break;
      case 'orders':
        this.loadOrders();
        break;
      case 'customers':
        this.loadCustomers();
        break;
      case 'categories':
        this.loadCategories();
        break;
      case 'analytics':
        this.loadAnalytics();
        break;
      case 'sql-console':
        this.loadSqlConsole();
        break;
    }
  },

  // --------------------------------------------------------------------------
  // Common Data Caching (Categories, Customers, Products for dropdowns)
  // --------------------------------------------------------------------------
  async loadCommonData() {
    try {
      const [catRes, custRes, prodRes] = await Promise.all([
        this.api.get('categories.php'),
        this.api.get('customers.php'),
        this.api.get('products.php')
      ]);

      if (catRes.success) {
        this.state.categories = catRes.data;
        this.populateCategorySelects();
      }
      if (custRes.success) {
        this.state.customers = custRes.data;
        this.populateCustomerSelects();
      }
      if (prodRes.success) {
        this.state.products = prodRes.data;
      }
    } catch (e) {
      console.warn('Common data pre-load error:', e);
    }
  },

  populateCategorySelects() {
    const selects = ['filterCategory', 'productCategoryInput', 'editProductCategoryInput'];
    selects.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      const isFilter = id.startsWith('filter');
      let html = isFilter ? '<option value="">All Categories</option>' : '<option value="">Select Category</option>';
      this.state.categories.forEach(cat => {
        html += `<option value="${cat.category_id}">${this.escapeHtml(cat.category_name)}</option>`;
      });
      el.innerHTML = html;
    });
  },

  populateCustomerSelects() {
    const el = document.getElementById('newOrderCustomer');
    if (!el) return;
    let html = '<option value="">Select Customer...</option>';
    this.state.customers.forEach(cust => {
      html += `<option value="${cust.customer_id}">${this.escapeHtml(cust.first_name + ' ' + cust.last_name)} (${this.escapeHtml(cust.email)})</option>`;
    });
    el.innerHTML = html;
  },

  // --------------------------------------------------------------------------
  // View 1: Overview / Executive Dashboard
  // --------------------------------------------------------------------------
  async loadOverview() {
    try {
      const res = await this.api.get('stats.php');
      if (!res.success) throw new Error(res.message);
      this.state.stats = res.data;

      const sum = res.data.summary;
      this.animateNumber('kpiRevenue', sum.total_revenue, true);
      this.animateNumber('kpiOrders', sum.total_orders);
      this.animateNumber('kpiCustomers', sum.total_customers);
      this.animateNumber('kpiProducts', sum.total_products);

      document.getElementById('kpiAov').textContent = '$' + Number(sum.average_order_value).toFixed(2);
      document.getElementById('kpiLowStock').textContent = sum.low_stock_count;

      this.renderOverviewCharts(res.data);
      this.renderRecentOrdersTable(res.data.recent_orders);
      this.renderLowStockList(res.data.low_stock_products);
    } catch (e) {
      this.showToast('Error loading overview data: ' + e.message, 'danger');
    }
  },

  renderOverviewCharts(data) {
    const isDark = this.state.theme === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    // 1. Revenue Timeline Chart
    const revCtx = document.getElementById('chartRevenueTrend');
    if (revCtx) {
      if (this.state.charts.revenue) this.state.charts.revenue.destroy();

      const labels = data.revenue_timeline.map(r => r.formatted_date);
      const values = data.revenue_timeline.map(r => Number(r.daily_revenue));

      this.state.charts.revenue = new Chart(revCtx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Daily Revenue ($)',
            data: values,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.12)',
            borderWidth: 3,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: isDark ? '#1e293b' : '#0f172a',
              titleColor: '#fff',
              bodyColor: '#e2e8f0',
              padding: 12,
              cornerRadius: 8,
              callbacks: {
                label: (ctx) => `Revenue: $${Number(ctx.raw).toFixed(2)}`
              }
            }
          },
          scales: {
            x: {
              grid: { color: gridColor },
              ticks: { color: textColor, maxRotation: 45 }
            },
            y: {
              grid: { color: gridColor },
              ticks: {
                color: textColor,
                callback: (v) => '$' + v
              }
            }
          }
        }
      });
    }

    // 2. Category Share Doughnut
    const catCtx = document.getElementById('chartCategoryShare');
    if (catCtx) {
      if (this.state.charts.category) this.state.charts.category.destroy();

      const catLabels = data.category_sales.map(c => c.category_name);
      const catData = data.category_sales.map(c => Number(c.category_revenue));
      const colors = ['#6366f1', '#a855f7', '#ec4899', '#06b6d4', '#10b981', '#f59e0b', '#8b5cf6'];

      this.state.charts.category = new Chart(catCtx, {
        type: 'doughnut',
        data: {
          labels: catLabels,
          datasets: [{
            data: catData,
            backgroundColor: colors.slice(0, catLabels.length),
            borderWidth: isDark ? 2 : 1,
            borderColor: isDark ? '#111827' : '#ffffff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { color: textColor, boxWidth: 12, padding: 15, font: { size: 11 } }
            },
            tooltip: {
              callbacks: {
                label: (ctx) => ` ${ctx.label}: $${Number(ctx.raw).toFixed(2)}`
              }
            }
          },
          cutout: '70%'
        }
      });
    }

    // 3. Order Status Bar
    const statusCtx = document.getElementById('chartOrderStatus');
    if (statusCtx) {
      if (this.state.charts.status) this.state.charts.status.destroy();

      const sLabels = data.orders_by_status.map(s => s.status);
      const sCounts = data.orders_by_status.map(s => Number(s.count));

      const statusColorMap = {
        'Delivered': '#10b981',
        'Shipped': '#6366f1',
        'Pending': '#f59e0b',
        'Cancelled': '#f43f5e',
        'Processing': '#06b6d4'
      };

      this.state.charts.status = new Chart(statusCtx, {
        type: 'bar',
        data: {
          labels: sLabels,
          datasets: [{
            data: sCounts,
            backgroundColor: sLabels.map(s => statusColorMap[s] || '#6366f1'),
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: { grid: { display: false }, ticks: { color: textColor } },
            y: { grid: { color: gridColor }, ticks: { color: textColor, stepSize: 2 } }
          }
        }
      });
    }
  },

  recreateCharts() {
    if (this.state.stats) {
      this.renderOverviewCharts(this.state.stats);
    }
  },

  renderRecentOrdersTable(orders) {
    const tbody = document.getElementById('recentOrdersTableBody');
    if (!tbody) return;
    if (!orders || orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No recent orders found</td></tr>';
      return;
    }

    tbody.innerHTML = orders.map(o => `
      <tr>
        <td class="fw-bold">#ORD-${o.order_id}</td>
        <td>
          <div class="fw-semibold">${this.escapeHtml(o.customer_name)}</div>
          <small class="text-muted">${this.escapeHtml(o.customer_email)}</small>
        </td>
        <td>${o.order_date}</td>
        <td><span class="badge bg-light text-dark border">${o.items_count} item(s)</span></td>
        <td class="fw-bold text-success">$${Number(o.total_amount).toFixed(2)}</td>
        <td>${this.getStatusBadge(o.status)}</td>
        <td>
          <button class="btn btn-sm btn-outline-primary" onclick="App.viewOrderDetails(${o.order_id})">
            <i class="bi bi-eye"></i> Details
          </button>
        </td>
      </tr>
    `).join('');
  },

  renderLowStockList(products) {
    const container = document.getElementById('lowStockContainer');
    if (!container) return;
    if (!products || products.length === 0) {
      container.innerHTML = '<div class="text-muted text-center py-3">All product inventory levels are healthy!</div>';
      return;
    }

    container.innerHTML = products.map(p => {
      const isOut = Number(p.stock_quantity) === 0;
      return `
        <div class="d-flex align-items-center justify-content-between p-2 rounded mb-2 border ${isOut ? 'border-danger-subtle bg-danger-subtle bg-opacity-25' : 'border-warning-subtle bg-warning-subtle bg-opacity-25'}">
          <div>
            <div class="fw-semibold">${this.escapeHtml(p.product_name)}</div>
            <small class="text-muted">${this.escapeHtml(p.category_name || 'General')} &bull; $${Number(p.price).toFixed(2)}</small>
          </div>
          <div class="text-end">
            <span class="badge ${isOut ? 'bg-danger' : 'bg-warning text-dark'} fw-bold">
              ${isOut ? 'Out of Stock' : p.stock_quantity + ' left'}
            </span>
          </div>
        </div>
      `;
    }).join('');
  },

  // --------------------------------------------------------------------------
  // View 2: Products Catalog
  // --------------------------------------------------------------------------
  async loadProducts() {
    const search = document.getElementById('searchProductInput')?.value || '';
    const categoryId = document.getElementById('filterCategory')?.value || '';
    const stock = document.getElementById('filterStock')?.value || 'all';

    try {
      const res = await this.api.get('products.php', {
        search: search,
        category_id: categoryId,
        stock: stock
      });

      if (!res.success) throw new Error(res.message);
      this.state.products = res.data;
      this.renderProductsTable(res.data);
    } catch (e) {
      this.showToast('Error loading products: ' + e.message, 'danger');
    }
  },

  renderProductsTable(products) {
    const tbody = document.getElementById('productsTableBody');
    const countEl = document.getElementById('productsCountBadge');
    if (countEl) countEl.textContent = `${products.length} products`;
    if (!tbody) return;

    if (!products || products.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="text-center py-5 text-muted">No products match your search/filter criteria.</td></tr>';
      return;
    }

    tbody.innerHTML = products.map(p => {
      const stock = Number(p.stock_quantity);
      let stockBadge = '<span class="badge-status badge-instock"><i class="bi bi-check-circle"></i> In Stock (' + stock + ')</span>';
      if (stock === 0) {
        stockBadge = '<span class="badge-status badge-outofstock"><i class="bi bi-x-circle"></i> Out of Stock</span>';
      } else if (stock <= 30) {
        stockBadge = '<span class="badge-status badge-lowstock"><i class="bi bi-exclamation-triangle"></i> Low Stock (' + stock + ')</span>';
      }

      const activeBadge = Number(p.is_active) === 1
        ? '<span class="badge-status badge-active">Active</span>'
        : '<span class="badge-status badge-inactive">Inactive</span>';

      return `
        <tr>
          <td class="fw-bold">#${p.product_id}</td>
          <td>
            <div class="fw-bold">${this.escapeHtml(p.product_name)}</div>
            <small class="text-muted text-truncate d-inline-block" style="max-width: 220px;">${this.escapeHtml(p.description || 'No description')}</small>
          </td>
          <td><span class="badge bg-secondary-subtle text-secondary border">${this.escapeHtml(p.category_name || 'Uncategorized')}</span></td>
          <td class="fw-bold">$${Number(p.price).toFixed(2)}</td>
          <td>${stockBadge}</td>
          <td>${activeBadge}</td>
          <td>
            <div class="small fw-semibold text-primary">${p.units_sold} sold</div>
            <small class="text-muted">$${Number(p.total_revenue).toFixed(2)}</small>
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" title="Edit Product" onclick="App.openEditProductModal(${p.product_id})">
                <i class="bi bi-pencil-square"></i>
              </button>
              <button class="btn btn-outline-danger" title="Delete Product" onclick="App.deleteProduct(${p.product_id}, '${this.escapeHtml(p.product_name)}')">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  openAddProductModal() {
    document.getElementById('productForm').reset();
    document.getElementById('productIdInput').value = '';
    document.getElementById('productModalTitle').textContent = 'Add New Product';
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
  },

  async openEditProductModal(id) {
    try {
      const res = await this.api.get('products.php', { id: id });
      if (!res.success) throw new Error(res.message);
      const p = res.data;

      document.getElementById('productIdInput').value = p.product_id;
      document.getElementById('productNameInput').value = p.product_name;
      document.getElementById('productCategoryInput').value = p.category_id;
      document.getElementById('productPriceInput').value = p.price;
      document.getElementById('productStockInput').value = p.stock_quantity;
      document.getElementById('productActiveInput').value = p.is_active;
      document.getElementById('productDescInput').value = p.description || '';

      document.getElementById('productModalTitle').textContent = `Edit Product #${p.product_id}`;
      const modal = new bootstrap.Modal(document.getElementById('productModal'));
      modal.show();
    } catch (e) {
      this.showToast('Failed to load product details: ' + e.message, 'danger');
    }
  },

  async saveProduct(e) {
    e.preventDefault();
    const id = document.getElementById('productIdInput').value;
    const data = {
      product_name: document.getElementById('productNameInput').value,
      category_id: document.getElementById('productCategoryInput').value,
      price: document.getElementById('productPriceInput').value,
      stock_quantity: document.getElementById('productStockInput').value,
      is_active: document.getElementById('productActiveInput').value,
      description: document.getElementById('productDescInput').value
    };

    try {
      let res;
      if (id) {
        data.product_id = id;
        res = await this.api.put('products.php', data);
      } else {
        res = await this.api.post('products.php', data);
      }

      if (!res.success) throw new Error(res.message);

      this.showToast(res.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
      this.loadProducts();
      this.loadCommonData();
    } catch (err) {
      this.showToast(err.message, 'danger');
    }
  },

  async deleteProduct(id, name) {
    if (!confirm(`Are you sure you want to delete product "${name}" (#${id})?`)) return;

    try {
      const res = await this.api.delete('products.php', { id: id });
      if (!res.success) throw new Error(res.message);
      this.showToast(res.message, 'success');
      this.loadProducts();
      this.loadCommonData();
    } catch (e) {
      this.showToast(e.message, 'warning');
    }
  },

  exportProductsCSV() {
    if (!this.state.products || !this.state.products.length) {
      this.showToast('No products to export', 'warning');
      return;
    }
    const headers = ['ID', 'Product Name', 'Category', 'Price', 'Stock Quantity', 'Active', 'Units Sold', 'Total Revenue'];
    const rows = this.state.products.map(p => [
      p.product_id,
      `"${(p.product_name || '').replace(/"/g, '""')}"`,
      `"${(p.category_name || '').replace(/"/g, '""')}"`,
      p.price,
      p.stock_quantity,
      p.is_active ? 'Yes' : 'No',
      p.units_sold,
      p.total_revenue
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `shop_db_products_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    this.showToast('Products exported to CSV', 'success');
  },

  // --------------------------------------------------------------------------
  // View 3: Orders Management
  // --------------------------------------------------------------------------
  async loadOrders() {
    const search = document.getElementById('searchOrderInput')?.value || '';
    const status = document.getElementById('filterOrderStatus')?.value || 'All';

    try {
      const res = await this.api.get('orders.php', {
        search: search,
        status: status
      });

      if (!res.success) throw new Error(res.message);
      this.state.orders = res.data;
      this.renderOrdersTable(res.data);
    } catch (e) {
      this.showToast('Error loading orders: ' + e.message, 'danger');
    }
  },

  renderOrdersTable(orders) {
    const tbody = document.getElementById('ordersTableBody');
    const countEl = document.getElementById('ordersCountBadge');
    if (countEl) countEl.textContent = `${orders.length} orders`;
    if (!tbody) return;

    if (!orders || orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No orders match your filter criteria.</td></tr>';
      return;
    }

    tbody.innerHTML = orders.map(o => `
      <tr>
        <td class="fw-bold">#ORD-${o.order_id}</td>
        <td>
          <div class="fw-semibold">${this.escapeHtml(o.customer_name)}</div>
          <small class="text-muted">${this.escapeHtml(o.customer_email)}</small>
        </td>
        <td>${o.order_date}</td>
        <td>
          <span class="badge bg-light text-dark border">
            ${o.items_count} item(s) &bull; ${o.total_units} unit(s)
          </span>
        </td>
        <td class="fw-bold text-success">$${Number(o.total_amount).toFixed(2)}</td>
        <td>${this.getStatusBadge(o.status)}</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" title="View Order" onclick="App.viewOrderDetails(${o.order_id})">
              <i class="bi bi-eye"></i>
            </button>
            <button class="btn btn-outline-secondary" title="Update Status" onclick="App.openUpdateStatusModal(${o.order_id}, '${o.status}')">
              <i class="bi bi-arrow-repeat"></i>
            </button>
            <button class="btn btn-outline-danger" title="Delete Order" onclick="App.deleteOrder(${o.order_id})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  },

  async viewOrderDetails(id) {
    try {
      const res = await this.api.get('orders.php', { id: id });
      if (!res.success) throw new Error(res.message);
      const o = res.data;

      document.getElementById('modalOrderTitle').textContent = `Order #ORD-${o.order_id}`;
      document.getElementById('modalOrderDate').textContent = o.order_date;
      document.getElementById('modalOrderStatus').innerHTML = this.getStatusBadge(o.status);

      document.getElementById('modalCustName').textContent = o.customer_name;
      document.getElementById('modalCustEmail').textContent = o.customer_email;
      document.getElementById('modalCustPhone').textContent = o.customer_phone || 'None';
      document.getElementById('modalCustLocation').textContent = `${o.customer_city || ''}, ${o.customer_country}`;

      const tbody = document.getElementById('modalOrderItemsBody');
      tbody.innerHTML = o.items.map(it => `
        <tr>
          <td>
            <div class="fw-semibold">${this.escapeHtml(it.product_name)}</div>
            <small class="text-muted">${this.escapeHtml(it.category_name || 'Item')}</small>
          </td>
          <td>$${Number(it.unit_price).toFixed(2)}</td>
          <td>${it.quantity}</td>
          <td class="fw-bold text-end">$${Number(it.line_total).toFixed(2)}</td>
        </tr>
      `).join('');

      document.getElementById('modalOrderTotal').textContent = '$' + Number(o.total_amount).toFixed(2);
      document.getElementById('modalOrderUnits').textContent = o.total_units;

      const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
      modal.show();
    } catch (e) {
      this.showToast('Could not load order details: ' + e.message, 'danger');
    }
  },

  openUpdateStatusModal(id, currentStatus) {
    document.getElementById('statusOrderId').value = id;
    document.getElementById('statusSelectInput').value = currentStatus;
    const modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
  },

  async submitStatusUpdate(e) {
    e.preventDefault();
    const id = document.getElementById('statusOrderId').value;
    const newStatus = document.getElementById('statusSelectInput').value;

    try {
      const res = await this.api.put('orders.php', {
        order_id: id,
        status: newStatus
      });
      if (!res.success) throw new Error(res.message);

      this.showToast(res.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('updateStatusModal')).hide();
      this.loadOrders();
    } catch (e) {
      this.showToast(e.message, 'danger');
    }
  },

  async deleteOrder(id) {
    if (!confirm(`Are you sure you want to delete Order #ORD-${id}? This will also remove all its line items.`)) return;

    try {
      const res = await this.api.delete('orders.php', { id: id });
      if (!res.success) throw new Error(res.message);
      this.showToast(res.message, 'success');
      this.loadOrders();
    } catch (e) {
      this.showToast(e.message, 'danger');
    }
  },

  // Dynamic Create Order modal logic
  openCreateOrderModal() {
    document.getElementById('createOrderForm').reset();
    document.getElementById('newOrderDate').value = new Date().toISOString().slice(0, 10);
    this.populateCustomerSelects();

    // Reset item rows to 1 row
    const container = document.getElementById('orderItemsRows');
    container.innerHTML = '';
    this.addOrderItemRow();
    this.recalculateOrderTotal();

    const modal = new bootstrap.Modal(document.getElementById('createOrderModal'));
    modal.show();
  },

  addOrderItemRow() {
    const container = document.getElementById('orderItemsRows');
    const rowIndex = container.children.length;

    let prodOptions = '<option value="">Select a product...</option>';
    this.state.products.forEach(p => {
      prodOptions += `<option value="${p.product_id}" data-price="${p.price}" data-stock="${p.stock_quantity}">${this.escapeHtml(p.product_name)} ($${Number(p.price).toFixed(2)} - Stock: ${p.stock_quantity})</option>`;
    });

    const row = document.createElement('div');
    row.className = 'row g-2 align-items-center mb-2 order-item-row';
    row.innerHTML = `
      <div class="col-6">
        <select class="form-select form-select-sm order-prod-select" required onchange="App.onOrderItemProductChange(this)">
          ${prodOptions}
        </select>
      </div>
      <div class="col-2">
        <input type="number" class="form-control form-select-sm order-prod-qty" min="1" value="1" required onchange="App.recalculateOrderTotal()" onkeyup="App.recalculateOrderTotal()">
      </div>
      <div class="col-2">
        <input type="number" step="0.01" class="form-control form-select-sm order-prod-price" placeholder="Price" required onchange="App.recalculateOrderTotal()" onkeyup="App.recalculateOrderTotal()">
      </div>
      <div class="col-2 text-end">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="App.removeOrderItemRow(this)">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    `;
    container.appendChild(row);
  },

  removeOrderItemRow(btn) {
    const container = document.getElementById('orderItemsRows');
    if (container.children.length <= 1) {
      this.showToast('Order must contain at least one product item', 'warning');
      return;
    }
    btn.closest('.order-item-row').remove();
    this.recalculateOrderTotal();
  },

  onOrderItemProductChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || '0.00';
    const row = selectEl.closest('.order-item-row');
    const priceInput = row.querySelector('.order-prod-price');
    if (priceInput) priceInput.value = price;
    this.recalculateOrderTotal();
  },

  recalculateOrderTotal() {
    const rows = document.querySelectorAll('.order-item-row');
    let total = 0;
    rows.forEach(r => {
      const qty = Number(r.querySelector('.order-prod-qty')?.value || 0);
      const price = Number(r.querySelector('.order-prod-price')?.value || 0);
      total += qty * price;
    });
    const totalEl = document.getElementById('newOrderTotalDisplay');
    if (totalEl) totalEl.textContent = '$' + total.toFixed(2);
  },

  async submitNewOrder(e) {
    e.preventDefault();
    const customerId = document.getElementById('newOrderCustomer').value;
    const orderDate = document.getElementById('newOrderDate').value;
    const status = document.getElementById('newOrderStatus').value;

    const rows = document.querySelectorAll('.order-item-row');
    const items = [];
    rows.forEach(r => {
      const prodId = r.querySelector('.order-prod-select')?.value;
      const qty = r.querySelector('.order-prod-qty')?.value;
      const price = r.querySelector('.order-prod-price')?.value;
      if (prodId && qty) {
        items.push({
          product_id: parseInt(prodId),
          quantity: parseInt(qty),
          unit_price: parseFloat(price)
        });
      }
    });

    if (!items.length) {
      this.showToast('Please add at least one valid product item', 'warning');
      return;
    }

    try {
      const res = await this.api.post('orders.php', {
        customer_id: customerId,
        order_date: orderDate,
        status: status,
        items: items
      });

      if (!res.success) throw new Error(res.message);

      this.showToast(`Order #ORD-${res.order_id} created successfully!`, 'success');
      bootstrap.Modal.getInstance(document.getElementById('createOrderModal')).hide();
      this.loadOrders();
      this.loadCommonData();
    } catch (err) {
      this.showToast(err.message, 'danger');
    }
  },

  // --------------------------------------------------------------------------
  // View 4: Customers Directory
  // --------------------------------------------------------------------------
  async loadCustomers() {
    const search = document.getElementById('searchCustomerInput')?.value || '';
    const city = document.getElementById('filterCustomerCity')?.value || '';

    try {
      const res = await this.api.get('customers.php', { search, city });
      if (!res.success) throw new Error(res.message);
      this.state.customers = res.data;
      this.renderCustomersTable(res.data);
    } catch (e) {
      this.showToast('Error loading customers: ' + e.message, 'danger');
    }
  },

  renderCustomersTable(customers) {
    const tbody = document.getElementById('customersTableBody');
    const countEl = document.getElementById('customersCountBadge');
    if (countEl) countEl.textContent = `${customers.length} customers`;
    if (!tbody) return;

    if (!customers || customers.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No customers found.</td></tr>';
      return;
    }

    tbody.innerHTML = customers.map(c => {
      const initials = (c.first_name[0] || '') + (c.last_name[0] || '');
      return `
        <tr>
          <td>
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-initials">${initials.toUpperCase()}</div>
              <div>
                <div class="fw-bold">${this.escapeHtml(c.first_name + ' ' + c.last_name)}</div>
                <small class="text-muted">ID #${c.customer_id}</small>
              </div>
            </div>
          </td>
          <td>
            <div><i class="bi bi-envelope text-muted me-1"></i> ${this.escapeHtml(c.email)}</div>
            <small class="text-muted"><i class="bi bi-telephone me-1"></i> ${this.escapeHtml(c.phone || 'None')}</small>
          </td>
          <td>
            <div>${this.escapeHtml(c.city || 'N/A')}</div>
            <small class="text-muted">${this.escapeHtml(c.country || 'Malaysia')}</small>
          </td>
          <td>
            <span class="badge bg-primary-subtle text-primary border">${c.total_orders} Orders</span>
          </td>
          <td class="fw-bold text-success">$${Number(c.total_spent).toFixed(2)}</td>
          <td>
            <small class="text-muted">${c.last_order_date || 'No orders yet'}</small>
          </td>
          <td>
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-secondary" title="Edit Customer" onclick="App.openEditCustomerModal(${c.customer_id})">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-outline-danger" title="Delete Customer" onclick="App.deleteCustomer(${c.customer_id}, '${this.escapeHtml(c.first_name + ' ' + c.last_name)}')">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  },

  openAddCustomerModal() {
    document.getElementById('customerForm').reset();
    document.getElementById('customerIdInput').value = '';
    document.getElementById('customerModalTitle').textContent = 'Add New Customer';
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
  },

  async openEditCustomerModal(id) {
    try {
      const res = await this.api.get('customers.php', { id: id });
      if (!res.success) throw new Error(res.message);
      const c = res.data;

      document.getElementById('customerIdInput').value = c.customer_id;
      document.getElementById('custFirstName').value = c.first_name;
      document.getElementById('custLastName').value = c.last_name;
      document.getElementById('custEmail').value = c.email;
      document.getElementById('custPhone').value = c.phone || '';
      document.getElementById('custCity').value = c.city || '';
      document.getElementById('custCountry').value = c.country || 'Malaysia';

      document.getElementById('customerModalTitle').textContent = `Edit Customer #${c.customer_id}`;
      const modal = new bootstrap.Modal(document.getElementById('customerModal'));
      modal.show();
    } catch (e) {
      this.showToast('Could not load customer: ' + e.message, 'danger');
    }
  },

  async saveCustomer(e) {
    e.preventDefault();
    const id = document.getElementById('customerIdInput').value;
    const data = {
      first_name: document.getElementById('custFirstName').value,
      last_name: document.getElementById('custLastName').value,
      email: document.getElementById('custEmail').value,
      phone: document.getElementById('custPhone').value,
      city: document.getElementById('custCity').value,
      country: document.getElementById('custCountry').value
    };

    try {
      let res;
      if (id) {
        data.customer_id = id;
        res = await this.api.put('customers.php', data);
      } else {
        res = await this.api.post('customers.php', data);
      }

      if (!res.success) throw new Error(res.message);

      this.showToast(res.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
      this.loadCustomers();
      this.loadCommonData();
    } catch (err) {
      this.showToast(err.message, 'danger');
    }
  },

  async deleteCustomer(id, name) {
    if (!confirm(`Are you sure you want to delete customer "${name}"?`)) return;

    try {
      const res = await this.api.delete('customers.php', { id: id });
      if (!res.success) throw new Error(res.message);
      this.showToast(res.message, 'success');
      this.loadCustomers();
      this.loadCommonData();
    } catch (e) {
      this.showToast(e.message, 'warning');
    }
  },

  // --------------------------------------------------------------------------
  // View 5: Categories Management
  // --------------------------------------------------------------------------
  async loadCategories() {
    try {
      const res = await this.api.get('categories.php');
      if (!res.success) throw new Error(res.message);
      this.state.categories = res.data;
      this.renderCategoriesGrid(res.data);
    } catch (e) {
      this.showToast('Error loading categories: ' + e.message, 'danger');
    }
  },

  renderCategoriesGrid(categories) {
    const grid = document.getElementById('categoriesGrid');
    if (!grid) return;

    if (!categories || categories.length === 0) {
      grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">No categories defined yet.</div>';
      return;
    }

    grid.innerHTML = categories.map(cat => `
      <div class="col-md-6 col-xl-4 mb-4">
        <div class="card-neo h-100 p-4 d-flex flex-column justify-content-between">
          <div>
            <div class="d-flex align-items-center justify-content-between mb-3">
              <span class="badge bg-indigo-subtle text-primary border fw-bold px-3 py-2 rounded-pill">
                #CAT-${cat.category_id}
              </span>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow">
                  <li><a class="dropdown-item" href="javascript:void(0)" onclick="App.openEditCategoryModal(${cat.category_id}, '${this.escapeHtml(cat.category_name)}', '${this.escapeHtml(cat.description || '')}')"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                  <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="App.deleteCategory(${cat.category_id}, '${this.escapeHtml(cat.category_name)}')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                </ul>
              </div>
            </div>

            <h5 class="fw-bold mb-2">${this.escapeHtml(cat.category_name)}</h5>
            <p class="text-muted small mb-4">${this.escapeHtml(cat.description || 'No description provided')}</p>
          </div>

          <div class="border-top pt-3">
            <div class="row g-2 text-center">
              <div class="col-4 border-end">
                <div class="fw-bold">${cat.product_count}</div>
                <small class="text-muted" style="font-size: 0.725rem;">Products</small>
              </div>
              <div class="col-4 border-end">
                <div class="fw-bold">${cat.total_inventory}</div>
                <small class="text-muted" style="font-size: 0.725rem;">Stock Units</small>
              </div>
              <div class="col-4">
                <div class="fw-bold text-success">$${Number(cat.total_revenue).toFixed(0)}</div>
                <small class="text-muted" style="font-size: 0.725rem;">Revenue</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    `).join('');
  },

  openAddCategoryModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryIdInput').value = '';
    document.getElementById('categoryModalTitle').textContent = 'Add New Category';
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
  },

  openEditCategoryModal(id, name, desc) {
    document.getElementById('categoryIdInput').value = id;
    document.getElementById('catNameInput').value = name;
    document.getElementById('catDescInput').value = desc;
    document.getElementById('categoryModalTitle').textContent = `Edit Category #${id}`;
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
  },

  async saveCategory(e) {
    e.preventDefault();
    const id = document.getElementById('categoryIdInput').value;
    const data = {
      category_name: document.getElementById('catNameInput').value,
      description: document.getElementById('catDescInput').value
    };

    try {
      let res;
      if (id) {
        data.category_id = id;
        res = await this.api.put('categories.php', data);
      } else {
        res = await this.api.post('categories.php', data);
      }

      if (!res.success) throw new Error(res.message);

      this.showToast(res.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('categoryModal')).hide();
      this.loadCategories();
      this.loadCommonData();
    } catch (err) {
      this.showToast(err.message, 'danger');
    }
  },

  async deleteCategory(id, name) {
    if (!confirm(`Are you sure you want to delete category "${name}"?`)) return;

    try {
      const res = await this.api.delete('categories.php', { id: id });
      if (!res.success) throw new Error(res.message);
      this.showToast(res.message, 'success');
      this.loadCategories();
      this.loadCommonData();
    } catch (e) {
      this.showToast(e.message, 'warning');
    }
  },

  // --------------------------------------------------------------------------
  // View 6: Realtime Analytics
  // --------------------------------------------------------------------------
  async loadAnalytics() {
    try {
      const res = await this.api.get('stats.php');
      if (!res.success) throw new Error(res.message);
      const data = res.data;

      // Top Products Leaderboard
      const topTbody = document.getElementById('analyticsTopProducts');
      if (topTbody) {
        topTbody.innerHTML = data.top_products.map((p, idx) => {
          const pct = Math.min(100, Math.round((Number(p.total_sales) / (data.summary.total_revenue || 1)) * 100));
          return `
            <tr>
              <td class="fw-bold">#${idx + 1}</td>
              <td>
                <div class="fw-bold">${this.escapeHtml(p.product_name)}</div>
                <small class="text-muted">${this.escapeHtml(p.category_name)}</small>
              </td>
              <td>$${Number(p.price).toFixed(2)}</td>
              <td class="fw-bold">${p.total_sold} units</td>
              <td class="fw-bold text-success">$${Number(p.total_sales).toFixed(2)}</td>
              <td style="width: 140px;">
                <div class="progress" style="height: 6px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: ${pct}%"></div>
                </div>
                <small class="text-muted">${pct}% share</small>
              </td>
            </tr>
          `;
        }).join('');
      }

      // Category breakdown table
      const catTbody = document.getElementById('analyticsCategoryTable');
      if (catTbody) {
        catTbody.innerHTML = data.category_sales.map(c => `
          <tr>
            <td class="fw-bold">${this.escapeHtml(c.category_name)}</td>
            <td>${c.product_count}</td>
            <td>${c.units_sold}</td>
            <td class="fw-bold text-success">$${Number(c.category_revenue).toFixed(2)}</td>
          </tr>
        `).join('');
      }
    } catch (e) {
      this.showToast('Error loading analytics: ' + e.message, 'danger');
    }
  },

  // --------------------------------------------------------------------------
  // View 7: Interactive SQL Query Console (shop_db phpMyAdmin query engine)
  // --------------------------------------------------------------------------
  async loadSqlConsole() {
    this.loadSqlPresets();
  },

  async loadSqlPresets() {
    const container = document.getElementById('sqlPresetChips');
    if (!container) return;

    try {
      const res = await this.api.get('query.php', { action: 'presets' });
      if (!res.success) return;

      container.innerHTML = res.presets.map((p, idx) => `
        <div class="preset-chip" onclick="App.applyPresetQuery(${idx})">
          <i class="bi bi-play-circle-fill text-indigo"></i>
          <span>${this.escapeHtml(p.title)}</span>
        </div>
      `).join('');

      this.state.queryPresets = res.presets;
    } catch (e) {
      console.warn('Preset load failed:', e);
    }
  },

  applyPresetQuery(index) {
    if (this.state.queryPresets && this.state.queryPresets[index]) {
      const sqlBox = document.getElementById('sqlQueryInput');
      if (sqlBox) {
        sqlBox.value = this.state.queryPresets[index].sql;
        this.runSqlQuery();
      }
    }
  },

  async runSqlQuery() {
    const sql = document.getElementById('sqlQueryInput')?.value.trim();
    if (!sql) {
      this.showToast('Please type an SQL query to execute', 'warning');
      return;
    }

    const resultBox = document.getElementById('sqlResultContainer');
    const metricTime = document.getElementById('sqlMetricTime');
    const metricRows = document.getElementById('sqlMetricRows');
    const tableHeader = document.getElementById('sqlResultHeader');
    const tableBody = document.getElementById('sqlResultBody');
    const rawMsg = document.getElementById('sqlRawMessage');

    tableHeader.innerHTML = '';
    tableBody.innerHTML = '';
    rawMsg.style.display = 'none';

    try {
      const res = await this.api.post('query.php', { query: sql });
      metricTime.textContent = `${res.execution_time_ms} ms`;

      if (!res.success) {
        metricRows.textContent = '0 rows';
        rawMsg.style.display = 'block';
        rawMsg.className = 'alert alert-danger font-monospace';
        rawMsg.textContent = res.message;
        return;
      }

      if (res.is_select) {
        metricRows.textContent = `${res.row_count} rows`;
        this.state.lastQueryResult = res;

        if (res.columns && res.columns.length) {
          tableHeader.innerHTML = '<tr>' + res.columns.map(col => `<th>${this.escapeHtml(col)}</th>`).join('') + '</tr>';
        }

        if (res.data && res.data.length) {
          tableBody.innerHTML = res.data.map(row => {
            return '<tr>' + res.columns.map(col => `<td>${this.escapeHtml(row[col] !== null ? String(row[col]) : 'NULL')}</td>`).join('') + '</tr>';
          }).join('');
        } else {
          tableBody.innerHTML = `<tr><td colspan="${res.columns.length || 1}" class="text-center py-4 text-muted">Empty result set (0 rows returned)</td></tr>`;
        }
      } else {
        metricRows.textContent = `${res.affected_rows} affected`;
        rawMsg.style.display = 'block';
        rawMsg.className = 'alert alert-success font-monospace';
        rawMsg.textContent = res.message;
      }
    } catch (e) {
      metricTime.textContent = '0 ms';
      metricRows.textContent = 'Error';
      rawMsg.style.display = 'block';
      rawMsg.className = 'alert alert-danger font-monospace';
      rawMsg.textContent = 'Request failed: ' + e.message;
    }
  },

  exportSqlResultCSV() {
    const res = this.state.lastQueryResult;
    if (!res || !res.columns || !res.data || !res.data.length) {
      this.showToast('No query data to export', 'warning');
      return;
    }

    const headers = res.columns.map(c => `"${c}"`);
    const rows = res.data.map(r => res.columns.map(c => {
      const val = r[c] !== null ? String(r[c]) : '';
      return `"${val.replace(/"/g, '""')}"`;
    }).join(','));

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows].join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `query_result_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    this.showToast('Query results exported to CSV', 'success');
  },

  // --------------------------------------------------------------------------
  // Global Event Listeners & Utilities
  // --------------------------------------------------------------------------
  initGlobalEvents() {
    // Form submits
    document.getElementById('productForm')?.addEventListener('submit', (e) => this.saveProduct(e));
    document.getElementById('customerForm')?.addEventListener('submit', (e) => this.saveCustomer(e));
    document.getElementById('categoryForm')?.addEventListener('submit', (e) => this.saveCategory(e));
    document.getElementById('createOrderForm')?.addEventListener('submit', (e) => this.submitNewOrder(e));
    document.getElementById('updateStatusForm')?.addEventListener('submit', (e) => this.submitStatusUpdate(e));

    // Products filters
    document.getElementById('searchProductInput')?.addEventListener('input', () => this.debounce(() => this.loadProducts(), 300)());
    document.getElementById('filterCategory')?.addEventListener('change', () => this.loadProducts());
    document.getElementById('filterStock')?.addEventListener('change', () => this.loadProducts());

    // Orders filters
    document.getElementById('searchOrderInput')?.addEventListener('input', () => this.debounce(() => this.loadOrders(), 300)());
    document.getElementById('filterOrderStatus')?.addEventListener('change', () => this.loadOrders());

    // Customer filters
    document.getElementById('searchCustomerInput')?.addEventListener('input', () => this.debounce(() => this.loadCustomers(), 300)());

    // Global header search
    document.getElementById('globalSearchInput')?.addEventListener('keyup', (e) => {
      if (e.key === 'Enter') {
        const val = e.target.value.trim();
        if (val) {
          window.location.hash = '#products';
          setTimeout(() => {
            const input = document.getElementById('searchProductInput');
            if (input) {
              input.value = val;
              this.loadProducts();
            }
          }, 100);
        }
      }
    });
  },

  getStatusBadge(status) {
    const s = (status || '').toLowerCase();
    switch (s) {
      case 'delivered':
        return `<span class="badge-status badge-delivered"><i class="bi bi-check2-all"></i> Delivered</span>`;
      case 'shipped':
        return `<span class="badge-status badge-shipped"><i class="bi bi-truck"></i> Shipped</span>`;
      case 'pending':
        return `<span class="badge-status badge-pending"><i class="bi bi-clock-history"></i> Pending</span>`;
      case 'processing':
        return `<span class="badge-status badge-processing"><i class="bi bi-gear-wide-connected"></i> Processing</span>`;
      case 'cancelled':
        return `<span class="badge-status badge-cancelled"><i class="bi bi-x-circle"></i> Cancelled</span>`;
      default:
        return `<span class="badge bg-secondary">${status}</span>`;
    }
  },

  animateNumber(elementId, endValue, isCurrency = false) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const startValue = 0;
    const duration = 1000;
    const startTime = performance.now();

    const update = (now) => {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const easeProgress = 1 - Math.pow(1 - progress, 3);
      const current = startValue + (endValue - startValue) * easeProgress;

      if (isCurrency) {
        el.textContent = '$' + current.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      } else {
        el.textContent = Math.round(current).toLocaleString('en-US');
      }

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    };
    requestAnimationFrame(update);
  },

  showToast(message, type = 'primary') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const iconMap = {
      success: 'bi-check-circle-fill text-success',
      danger: 'bi-exclamation-octagon-fill text-danger',
      warning: 'bi-exclamation-triangle-fill text-warning',
      primary: 'bi-info-circle-fill text-primary'
    };

    const toastEl = document.createElement('div');
    toastEl.className = 'toast toast-neo show mb-2';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
      <div class="toast-header border-0 bg-transparent">
        <i class="bi ${iconMap[type] || iconMap.primary} me-2 fs-5"></i>
        <strong class="me-auto text-capitalize">${type}</strong>
        <small class="text-muted">Just now</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div class="toast-body pt-0">
        ${this.escapeHtml(message)}
      </div>
    `;

    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
      toastEl.remove();
    });
  },

  escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  },

  debounce(func, wait) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
