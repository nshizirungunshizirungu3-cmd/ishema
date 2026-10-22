/* ISHEMA application JavaScript */
(function () {
    'use strict';

    var ISHEMA = {
        locale: (document.documentElement.getAttribute('data-locale')) || 'en',
        csrf: function () {
            var el = document.querySelector('input[name="csrf_token"]');
            return el ? el.value : '';
        }
    };

    /* ---------- Toast notifications ---------- */
    function toast(message, type) {
        type = type || 'success';
        var container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }
        var el = document.createElement('div');
        el.className = 'toast ' + type;
        el.innerHTML = '<div class="t-msg">' + escapeHtml(message) + '</div>';
        container.appendChild(el);
        setTimeout(function () {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s';
            setTimeout(function () { el.remove(); }, 320);
        }, 4200);
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    /* ---------- AJAX helper ---------- */
    function api(url, data, method) {
        method = method || 'POST';
        var body;
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (data instanceof FormData) {
            body = data;
        } else {
            body = JSON.stringify(data || {});
            headers['Content-Type'] = 'application/json';
        }
        headers['X-CSRF-Token'] = ISHEMA.csrf();
        return fetch(url, { method: method, headers: headers, body: body })
            .then(function (res) { return res.json().catch(function () { return { ok: false, error: 'Invalid response' }; }); });
    }

    /* ---------- Modal ---------- */
    function openModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
    }
    function closeModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
    }

    /* ---------- Delete confirmation ---------- */
    function confirmDelete(url, message) {
        if (!confirm(message || 'Are you sure you want to delete this record?')) { return; }
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';
        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = 'csrf_token';
        token.value = ISHEMA.csrf();
        form.appendChild(token);
        document.body.appendChild(form);
        form.submit();
    }

    /* ---------- Sidebar mobile toggle ---------- */
    function initSidebar() {
        var toggle = document.querySelector('.menu-toggle');
        var sidebar = document.querySelector('.sidebar');
        var overlay = document.getElementById('overlay');
        if (toggle && sidebar) {
            function closeMenu() {
                sidebar.classList.remove('open');
                if (overlay) { overlay.classList.remove('show'); }
            }
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                sidebar.classList.toggle('open');
                if (overlay) { overlay.classList.toggle('show', sidebar.classList.contains('open')); }
            });
            if (overlay) {
                overlay.addEventListener('click', closeMenu);
            }
            document.addEventListener('click', function (e) {
                if (window.innerWidth <= 1024 &&
                    sidebar.classList.contains('open') &&
                    !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    closeMenu();
                }
            });
            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 1024) { closeMenu(); }
                });
            });
        }
    }

    /* ---------- Public nav mobile toggle ---------- */
    function initPublicNav() {
        var toggle = document.querySelector('.public-nav .nav-toggle');
        var links = document.querySelector('.public-nav .nav-links');
        if (toggle && links) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                links.classList.toggle('open');
            });
            links.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    links.classList.remove('open');
                });
            });
            document.addEventListener('click', function (e) {
                if (links.classList.contains('open') &&
                    !links.contains(e.target) && !toggle.contains(e.target)) {
                    links.classList.remove('open');
                }
            });
        }
    }

    /* ---------- Notifications polling ---------- */
    function initNotifications() {
        var pollUrl = '/notifications/poll';
        var dropdown = document.querySelector('.notif-dropdown');
        var btn = document.querySelector('.notif-btn');
        if (!dropdown) { return; }

        function refresh() {
            fetch(pollUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) { return; }
                    var countEl = document.querySelector('.notif-btn .count');
                    if (countEl) {
                        countEl.textContent = data.unread;
                        countEl.style.display = data.unread > 0 ? 'flex' : 'none';
                    }
                    renderNotifs(data.notifications);
                })
                .catch(function () { /* offline - ignore */ });
        }

        function renderNotifs(list) {
            var html = '';
            if (!list || list.length === 0) {
                html = '<div class="empty-state" style="padding:24px;"><div class="es-title">No notifications</div></div>';
            } else {
                list.forEach(function (n) {
                    html += '<div class="notif-item' + (n.is_read ? '' : ' unread') + '">' +
                        '<div class="dot"></div><div><div class="nt-title">' + escapeHtml(n.title) + '</div>' +
                        (n.message ? '<div class="nt-msg">' + escapeHtml(n.message) + '</div>' : '') +
                        '<div class="nt-time">' + escapeHtml(n.created_at || '') + '</div></div></div>';
                });
            }
            dropdown.innerHTML = html;
        }

        if (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
                if (dropdown.classList.contains('open')) { refresh(); }
            });
            document.addEventListener('click', function () { dropdown.classList.remove('open'); });
        }

        // Poll every 20 seconds with fallback
        setInterval(refresh, 20000);
        refresh();

        // Mark all read link
        var markAll = document.querySelector('[data-mark-all-read]');
        if (markAll) {
            markAll.addEventListener('click', function (e) {
                e.preventDefault();
                api('/notifications/read', { id: 0 }).then(function () { refresh(); });
            });
        }
    }

    /* ---------- Language selector ---------- */
    function initLanguage() {
        var sel = document.getElementById('language-select');
        if (sel) {
            sel.addEventListener('change', function () {
                window.location.href = '/language/' + sel.value;
            });
        }
    }

    /* ---------- Simple bar chart ---------- */
    function renderBarChart(containerId, data, labelKey, valueKey) {
        var container = document.getElementById(containerId);
        if (!container || !data || data.length === 0) { return; }
        var max = Math.max.apply(null, data.map(function (d) { return parseFloat(d[valueKey]) || 0; })) || 1;
        var html = '<div class="chart-bar-row">';
        data.forEach(function (d) {
            var val = parseFloat(d[valueKey]) || 0;
            var h = Math.round((val / max) * 100);
            html += '<div class="chart-bar-col"><div class="chart-bar" style="height:' + Math.max(h, 2) + '%" title="' + escapeHtml(String(d[labelKey])) + ': ' + val + '"></div><div class="chart-bar-lbl">' + escapeHtml(String(d[labelKey])) + '</div></div>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    /* ---------- Star rating ---------- */
    function initStars() {
        document.querySelectorAll('.star-rating').forEach(function (wrap) {
            var input = wrap.querySelector('input[type="hidden"]');
            var stars = wrap.querySelectorAll('.star');
            stars.forEach(function (star, idx) {
                star.addEventListener('click', function () {
                    var val = idx + 1;
                    if (input) { input.value = val; }
                    stars.forEach(function (s, i) { s.classList.toggle('selected', i < val); });
                });
            });
        });
    }

    /* ---------- QR cart (customer portal) ---------- */
    var Cart = {
        token: '',
        items: {},
        init: function (token) {
            this.token = token;
            this.refreshBar();
        },
        add: function (productId, name, price) {
            var qty = 1;
            api('/qr/' + this.token + '/add-to-cart', { product_id: productId, quantity: qty })
                .then(function (res) {
                    if (res.ok) {
                        toast('Added to cart');
                        if (res.cart) { updateCartUI(res.cart); }
                    } else {
                        toast(res.error || 'Failed', 'error');
                    }
                });
        },
        changeQty: function (productId, delta) {
            var items = document.querySelectorAll('[data-cart-item]');
            var current = 1;
            items.forEach(function (el) {
                if (el.getAttribute('data-cart-item') === String(productId)) {
                    current = parseInt(el.querySelector('.qty-value') ? el.querySelector('.qty-value').textContent : '1', 10);
                }
            });
            var newQty = current + delta;
            if (newQty <= 0) {
                api('/qr/' + this.token + '/update-cart', { product_id: productId, quantity: 0 })
                    .then(function (res) {
                        if (res.ok && res.cart) { updateCartUI(res.cart); toast('Removed from cart', 'info'); }
                    });
            } else {
                api('/qr/' + this.token + '/update-cart', { product_id: productId, quantity: newQty })
                    .then(function (res) {
                        if (res.ok && res.cart) { updateCartUI(res.cart); }
                        else if (!res.ok) { toast(res.error || 'Failed', 'error'); }
                    });
            }
        },
        remove: function (productId) {
            api('/qr/' + this.token + '/update-cart', { product_id: productId, quantity: 0 })
                .then(function (res) {
                    if (res.ok && res.cart) { updateCartUI(res.cart); toast('Removed', 'info'); }
                });
        },
        clear: function () {
            api('/qr/' + this.token + '/clear-cart', {})
                .then(function (res) { if (res.ok) { updateCartUI({ count: 0, total: 0, items: [] }); } });
        },
        refreshBar: function () {
            var self = this;
            // Initial cart state rendered server-side; nothing to fetch here.
        }
    };

    function updateCartUI(cart) {
        var countEl = document.querySelector('.cart-count');
        var totalEl = document.querySelector('.cart-total');
        if (countEl) { countEl.textContent = cart.count || 0; }
        if (totalEl) { totalEl.textContent = (cart.total || 0).toLocaleString() + ' RWF'; }
        // Redraw drawer items
        var drawerBody = document.querySelector('.cart-drawer .cd-body');
        if (drawerBody) {
            var html = '';
            (cart.items || []).forEach(function (item) {
                html += '<div class="cart-item" data-cart-item="' + item.product_id + '">' +
                    '<div class="ci-info"><div class="ci-name">' + escapeHtml(item.name) + '</div>' +
                    '<div class="ci-price">' + item.price + ' RWF ' + '× ' + item.quantity + '</div></div>' +
                    '<div class="qty-ctrl"><button class="qty-btn" onclick="ISHEMA.cart.changeQty(' + item.product_id + ',-1)">−</button>' +
                    '<span class="qty-value">' + item.quantity + '</span>' +
                    '<button class="qty-btn" onclick="ISHEMA.cart.changeQty(' + item.product_id + ',1)">+</button></div>' +
                    '<button class="btn btn-sm btn-outline" onclick="ISHEMA.cart.remove(' + item.product_id + ')">✕</button></div>';
            });
            if (!html) { html = '<div class="empty-state"><div class="es-icon">🛒</div><div class="es-title">Cart is empty</div></div>'; }
            drawerBody.innerHTML = html;
        }
    }

    /* ---------- Order status polling (customer) ---------- */
    function initOrderPolling() {
        var orderEl = document.querySelector('[data-order-number]');
        if (!orderEl) { return; }
        var number = orderEl.getAttribute('data-order-number');
        var url = '/order/' + encodeURIComponent(number) + '/poll';
        setInterval(function () {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) { return; }
                    updateOrderStatus(data.status, data.items);
                })
                .catch(function () { });
        }, 8000);
    }

    function updateOrderStatus(status, items) {
        var badge = document.querySelector('[data-order-status-badge]');
        if (badge) {
            badge.textContent = status.replace(/_/g, ' ');
            badge.className = 'badge ' + (status === 'delivered' || status === 'completed' ? 'bg-success' : (status === 'cancelled' ? 'bg-danger' : 'bg-info'));
        }
        // Update per-item delivered quantities
        (items || []).forEach(function (item) {
            var row = document.querySelector('[data-item-row="' + item.id + '"]');
            if (row) {
                var del = row.querySelector('[data-delivered-qty]');
                if (del) { del.textContent = item.delivered_quantity; }
                var ds = row.querySelector('[data-delivery-status]');
                if (ds) { ds.textContent = item.delivery_status.replace(/_/g, ' '); }
            }
        });
    }

    /* ---------- Image preview ---------- */
    function initImagePreview() {
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                var target = document.querySelector(input.getAttribute('data-preview'));
                if (!target) { return; }
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function (e) { target.src = e.target.result; target.style.display = 'block'; };
                    reader.readAsDataURL(input.files[0]);
                }
            });
        });
    }

    /* ---------- Live search (simple) ---------- */
    function initTableSearch() {
        var input = document.querySelector('[data-search-input]');
        if (!input) { return; }
        input.addEventListener('input', function () {
            var term = input.value.toLowerCase();
            document.querySelectorAll('[data-search-row]').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(term) >= 0 ? '' : 'none';
            });
        });
    }

    /* ---------- Init on DOM ready ---------- */
    document.addEventListener('DOMContentLoaded', function () {
        initSidebar();
        initPublicNav();
        initNotifications();
        initLanguage();
        initStars();
        initImagePreview();
        initTableSearch();
        initOrderPolling();

        // Global exposure for inline handlers
        window.ISHEMA = ISHEMA;
        ISHEMA.toast = toast;
        ISHEMA.api = api;
        ISHEMA.openModal = openModal;
        ISHEMA.closeModal = closeModal;
        ISHEMA.confirmDelete = confirmDelete;
        ISHEMA.renderBarChart = renderBarChart;
        ISHEMA.cart = Cart;

        // Demo modal
        var demoBtn = document.querySelector('[data-open-demo]');
        if (demoBtn) {
            demoBtn.addEventListener('click', function () { openModal('demo-modal'); });
        }
    });
})();
