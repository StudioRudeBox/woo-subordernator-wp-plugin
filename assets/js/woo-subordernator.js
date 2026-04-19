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

    // Show sub-order rows whose parent is not collapsed (CSS hides all by default)
    rows.forEach(function (tr) {
        var meta = tr.querySelector('.srb-order-meta');
        if (!meta) return;
        var parentId = meta.getAttribute('data-parent-id');
        if (!parentId) return;
        if (sessionStorage.getItem('srb-collapsed-' + parentId) !== '1') {
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
        toggle.textContent = '▼';

        // Insert toggle before the order number link
        var orderNumberCell = tr.querySelector('.order_number');
        if (orderNumberCell) {
            orderNumberCell.insertBefore(toggle, orderNumberCell.firstChild);
        }

        // Restore collapsed state
        var storageKey = 'srb-collapsed-' + orderId;
        if (sessionStorage.getItem(storageKey) === '1') {
            collapse(orderId, toggle, trByOrderId, childrenMap, false);
        }

        toggle.addEventListener('click', function () {
            var isCollapsed = tr.classList.contains('srb-collapsed');
            if (isCollapsed) {
                expand(orderId, toggle, trByOrderId, childrenMap);
                sessionStorage.removeItem(storageKey);
            } else {
                collapse(orderId, toggle, trByOrderId, childrenMap, true);
                sessionStorage.setItem(storageKey, '1');
            }
        });
    });

    function collapse(parentId, toggle, trByOrderId, childrenMap, animate) {
        var parentTr = trByOrderId[parentId];
        if (parentTr) parentTr.classList.add('srb-collapsed');
        toggle.textContent = '▶';
        (childrenMap[parentId] || []).forEach(function (childId) {
            var childTr = trByOrderId[childId];
            if (childTr) childTr.style.display = 'none';
        });
    }

    function expand(parentId, toggle, trByOrderId, childrenMap) {
        var parentTr = trByOrderId[parentId];
        if (parentTr) parentTr.classList.remove('srb-collapsed');
        toggle.textContent = '▼';
        (childrenMap[parentId] || []).forEach(function (childId) {
            var childTr = trByOrderId[childId];
            if (childTr) childTr.style.display = 'table-row';
        });
    }

    // ── Order edit page ───────────────────────────────────────────
    var nonceEl        = document.getElementById('srb-nonce');
    var currentOrderEl = document.getElementById('srb-current-order-id');
    if (!nonceEl || !currentOrderEl) return;

    var nonce          = nonceEl.value;
    var currentId      = currentOrderEl.value;
    var addState       = document.getElementById('srb-add-state');
    var connectedState = document.getElementById('srb-connected-state');
    var addBtn         = document.getElementById('srb-btn-add');
    var editBtn        = document.getElementById('srb-btn-edit');
    var removeBtn      = document.getElementById('srb-btn-remove');
    var cancelBtn      = document.getElementById('srb-btn-cancel');
    var panel          = document.getElementById('srb-search-panel');
    var searchInput    = document.getElementById('srb-search-input');
    var results        = document.getElementById('srb-search-results');
    var hiddenInput    = document.getElementById('srb-parent-id-input');
    var parentLink     = document.getElementById('srb-parent-link');

    function openPanel() {
        panel.style.display = 'block';
        searchInput.value = '';
        results.innerHTML = '';
        searchInput.focus();
    }

    function closePanel() {
        panel.style.display = 'none';
    }

    if(addBtn) {
        addBtn.addEventListener('click', openPanel);
    }

    if(editBtn) {
        editBtn.addEventListener('click', openPanel);
    }

    if(cancelBtn) {
        cancelBtn.addEventListener('click', closePanel);
    }

    if(removeBtn) {
        removeBtn.addEventListener('click', function () {
            hiddenInput.value = '';
            connectedState.style.display = 'none';
            addState.style.display = 'inline';
            closePanel();
        });
    }

    if(searchInput) {
        var searchTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            var q = searchInput.value.trim();
            if (q.length < 2) {
                results.innerHTML = '';
                return;
            }
            results.innerHTML = '<li class="srb-loading">Searching\u2026</li>';
            searchTimer = setTimeout(function () {
                var url = ajaxurl
                    + '?action=srb_search_orders'
                    + '&q=' + encodeURIComponent(q)
                    + '&exclude=' + encodeURIComponent(currentId)
                    + '&exclude_parent=' + encodeURIComponent(hiddenInput.value || '')
                    + '&nonce=' + encodeURIComponent(nonce);

                fetch(url)
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        results.innerHTML = '';
                        if (!data.success || !data.data.length) {
                            results.innerHTML = '<li class="srb-no-results">No orders found.</li>';
                            return;
                        }
                        data.data.forEach(function (order) {
                            var li = document.createElement('li');
                            li.textContent = order.label;
                            li.addEventListener('click', function () {
                                hiddenInput.value = order.id;
                                parentLink.textContent = order.display;
                                parentLink.href = order.edit_url;
                                connectedState.style.display = 'inline';
                                addState.style.display = 'none';
                                closePanel();
                            });
                            results.appendChild(li);
                        });
                    })
                    .catch(function () {
                        results.innerHTML = '<li class="srb-no-results">Search failed. Please try again.</li>';
                    });
            }, 300);
        });
    }
});
