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

  /* ---- CLP currency formatting utilities ---- */
  /**
   * Format a numeric value as CLP: $1.234.567 (no decimals).
   * @param {number|string} value - Raw numeric value
   * @returns {string} Formatted string like "$500.000"
   */
  window.formatCLP = function(value) {
    var num = parseInt(String(value).replace(/[^0-9]/g, ''), 10);
    if (isNaN(num) || num === 0) return '$0';
    return '$' + num.toLocaleString('es-CL');
  };

  /**
   * Strip CLP formatting to get raw numeric string.
   * @param {string} formatted - Formatted string like "$500.000"
   * @returns {string} Raw digits like "500000"
   */
  window.stripCLP = function(formatted) {
    return String(formatted).replace(/[^0-9]/g, '');
  };

  /**
   * Handle real-time CLP formatting while typing.
   * Formats the input value and preserves cursor position relative to digits.
   * @param {HTMLInputElement} input - The CLP input element
   */
  window.handleCLPInput = function(input) {
    var raw = window.stripCLP(input.value);
    if (!raw) {
      input.value = '';
      return;
    }

    // Save cursor position relative to digits before formatting
    var selectionStart = input.selectionStart;
    var valueBeforeCursor = input.value.substring(0, selectionStart);
    var digitsBeforeCursor = valueBeforeCursor.replace(/\D/g, '').length;

    // Format the value
    var formatted = window.formatCLP(raw);
    input.value = formatted;

    // Restore cursor position relative to digits
    var newPos = 0;
    var digitCount = 0;
    for (var i = 0; i < formatted.length; i++) {
      if (/\d/.test(formatted[i])) {
        digitCount++;
        if (digitCount >= digitsBeforeCursor) {
          newPos = i + 1;
          break;
        }
      }
    }
    // If we haven't set newPos (e.g., cursor at end), place after last digit
    if (newPos === 0 && digitCount > 0) {
      for (var j = formatted.length - 1; j >= 0; j--) {
        if (/\d/.test(formatted[j])) {
          newPos = j + 1;
          break;
        }
      }
    }
    input.setSelectionRange(newPos, newPos);
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
