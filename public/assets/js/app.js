/* ========================================
   app.js — InApp Dashboard
   ========================================
   Usage: Include after Bootstrap JS
   ======================================== */

(function () {
  'use strict';

  /* ---- Loading indicator utilities ---- */
  /**
   * Show a Bootstrap spinner-border loading indicator inside a container element.
   * Clears any existing .loading-indicator children first to avoid stacking.
   * @param {HTMLElement} container  - The target container (tbody, div, etc.)
   * @param {number} [colspan]     - Optional colspan for table-row spinners
   */
  window.showElLoading = function (container, colspan) {
    if (!container) return;
    // Remove any existing loading indicators
    container.querySelectorAll('.loading-indicator').forEach(function (el) {
      el.remove();
    });
    var spinner = document.createElement('div');
    spinner.className = 'loading-indicator';
    spinner.setAttribute('role', 'status');
    if (colspan !== undefined) {
      // Table-row spinner
      var tr = document.createElement('tr');
      var td = document.createElement('td');
      td.colSpan = colspan;
      td.className = 'text-center py-4';
      var inner = document.createElement('div');
      inner.className = 'spinner-border spinner-border-sm text-secondary';
      inner.setAttribute('role', 'status');
      inner.innerHTML = '<span class="visually-hidden">Cargando...</span>';
      var text = document.createElement('span');
      text.className = 'ms-2 text-muted';
      text.textContent = 'Cargando...';
      td.appendChild(inner);
      td.appendChild(text);
      tr.appendChild(td);
      spinner.appendChild(tr);
    } else {
      // Inline spinner (for dropdowns, buttons, etc.)
      spinner.className = 'loading-indicator text-center py-2';
      var inner = document.createElement('div');
      inner.className = 'spinner-border spinner-border-sm text-secondary';
      inner.setAttribute('role', 'status');
      inner.innerHTML = '<span class="visually-hidden">Cargando...</span>';
      var text = document.createElement('span');
      text.className = 'ms-2 text-muted';
      text.textContent = 'Cargando...';
      spinner.appendChild(inner);
      spinner.appendChild(text);
    }
    container.appendChild(spinner);
  };

  /**
   * Remove all .loading-indicator children from a container.
   * @param {HTMLElement} container - The target container
   */
  window.hideElLoading = function (container) {
    if (!container) return;
    container.querySelectorAll('.loading-indicator').forEach(function (el) {
      el.remove();
    });
  };

  /* ---- Remove loading-placeholder rows on DOM ready ---- */
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.loading-placeholder').forEach(function (el) {
      el.remove();
    });
  });

  /* ---- Sidebar toggle (desktop) ---- */
  var sidebar = document.getElementById('sidebar');
  var content = document.getElementById('content');
  var toggleBtn = document.getElementById('toggleBtn');
  var mobileBtn = document.getElementById('mobileBtn');
  var overlay = document.getElementById('overlay');

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      if (sidebar) sidebar.classList.toggle('collapsed');
      if (content) content.classList.toggle('full');
    });
  }

  /* ---- Mobile sidebar open ---- */
  if (mobileBtn) {
    mobileBtn.addEventListener('click', function () {
      if (sidebar) sidebar.classList.add('mobile-show');
      if (overlay) overlay.classList.add('show');
    });
  }

  /* ---- Click overlay to close ---- */
  if (overlay) {
    overlay.addEventListener('click', function () {
      if (sidebar) sidebar.classList.remove('mobile-show');
      if (overlay) overlay.classList.remove('show');
    });
  }

  /* ---- Highlight active nav link ---- */
  var currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.sidebar .nav-link').forEach(function (link) {
    link.classList.remove('active');
    if (link.getAttribute('href') === currentPage) {
      link.classList.add('active');
    }
  });

  /* ---- Auto-label table cells for mobile card view ---- */
  function labelTable(table) {
    var headers = [];
    table.querySelectorAll('thead th').forEach(function (th) {
      headers.push(th.textContent.trim());
    });
    if (headers.length === 0) {
      table.querySelectorAll('thead td').forEach(function (td) {
        headers.push(td.textContent.trim());
      });
    }
    table.querySelectorAll('tbody tr').forEach(function (tr) {
      tr.querySelectorAll('td').forEach(function (td, i) {
        if (headers[i]) {
          td.setAttribute('data-label', headers[i]);
        }
      });
    });
  }

  /* Initial label + watch for dynamically added rows */
  document.querySelectorAll('.table-card-mobile').forEach(function (table) {
    labelTable(table);
    var observer = new MutationObserver(function () {
      labelTable(table);
    });
    observer.observe(table.querySelector('tbody'), { childList: true, subtree: true });
  });

})();
