document.addEventListener('DOMContentLoaded', function () {

    // ── Order list page ───────────────────────────────────────────
    var rows = document.querySelectorAll('#the-list tr');
    var parentMap = {};
    var childrenMap = {};

    // Build relationship maps from hidden spans
    rows.forEach(function (tr) {
        var meta = tr.querySelector('.srb-order-meta');
        if (!meta) return;
        var orderId = meta.getAttribute('data-order-id');
        var parentId = meta.getAttribute('data-parent-id');
        parentMap[orderId] = parentId;
        if (parentId) {
            if (!childrenMap[parentId]) childrenMap[parentId] = [];
            childrenMap[parentId].push(orderId);
        }
    });

    // Apply classes to sub-order rows and store TR references by order ID
    var trByOrderId = {};
    rows.forEach(function (tr) {
        var meta = tr.querySelector('.srb-order-meta');
        if (!meta) return;
        var orderId = meta.getAttribute('data-order-id');
        var parentId = meta.getAttribute('data-parent-id');
        trByOrderId[orderId] = tr;
        if (parentId) {
            tr.classList.add('is-suborder', 'srb-child-of-' + parentId);
        }
    });

    // Show sub-order rows only if the user previously expanded them (collapsed by default)
    rows.forEach(function (tr) {
        var meta = tr.querySelector('.srb-order-meta');
        if (!meta) return;
        var parentId = meta.getAttribute('data-parent-id');
        if (!parentId) return;
        if (sessionStorage.getItem('srb-expanded-' + parentId) === '1') {
            tr.style.display = 'table-row';
        }
    });

    // Add collapse toggle buttons to main orders that have children
    rows.forEach(function (tr) {
        var meta = tr.querySelector('.srb-order-meta');
        if (!meta) return;
        var orderId = meta.getAttribute('data-order-id');
        if (!childrenMap[orderId]) return;

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'srb-collapse-toggle';
        toggle.setAttribute('data-order-id', orderId);
        toggle.setAttribute('aria-label', 'Toggle sub-orders');

        // Insert toggle before the order number link
        var orderNumberCell = tr.querySelector('.order_number');
        if (orderNumberCell) {
            orderNumberCell.insertBefore(toggle, orderNumberCell.firstChild);
        }

        // Default collapsed; restore expanded state from localStorage
        var storageKey = 'srb-expanded-' + orderId;
        if (sessionStorage.getItem(storageKey) === '1') {
            expand(orderId, toggle, trByOrderId, childrenMap);
        } else {
            collapse(orderId, toggle, trByOrderId, childrenMap, false);
        }

        toggle.addEventListener('click', function () {
            var isCollapsed = tr.classList.contains('srb-collapsed');
            if (isCollapsed) {
                expand(orderId, toggle, trByOrderId, childrenMap);
                sessionStorage.setItem(storageKey, '1');
            } else {
                collapse(orderId, toggle, trByOrderId, childrenMap, true);
                sessionStorage.removeItem(storageKey);
            }
        });
    });

    function collapse(parentId, toggle, trByOrderId, childrenMap, animate) {
        var parentTr = trByOrderId[parentId];
        if (parentTr) parentTr.classList.add('srb-collapsed');
        toggle.textContent = '';
        (childrenMap[parentId] || []).forEach(function (childId) {
            var childTr = trByOrderId[childId];
            if (childTr) childTr.style.display = 'none';
        });
    }

    function expand(parentId, toggle, trByOrderId, childrenMap) {
        var parentTr = trByOrderId[parentId];
        if (parentTr) parentTr.classList.remove('srb-collapsed');
        toggle.textContent = '';
        (childrenMap[parentId] || []).forEach(function (childId) {
            var childTr = trByOrderId[childId];
            if (childTr) childTr.style.display = 'table-row';
        });
    }

    // ── Disconnect sub-order buttons (order edit page, main order view) ──
    var disconnectNonceEl = document.querySelector('.srb-disconnect-nonce');
    if (disconnectNonceEl) {
        document.querySelectorAll('.srb-btn-disconnect').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var subOrderId = btn.getAttribute('data-sub-order-id');
                if (!confirm(srbSubordernator.confirmDisconnect)) return;
                var body = new URLSearchParams({
                    action: 'srb_disconnect_suborder',
                    sub_order_id: subOrderId,
                    nonce: disconnectNonceEl.value
                });
                fetch(ajaxurl, { method: 'POST', body: body })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            btn.closest('li').remove();
                        }
                    });
            });
        });
    }

});
