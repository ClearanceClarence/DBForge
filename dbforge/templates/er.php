<?php if (!$currentDb): ?>
<div class="error-box">Select a database to view its ER diagram.</div>
<?php return; endif; ?>

<!-- Header -->
<div class="info-header info-header-gold">
    <div class="info-header-left">
        <div class="info-header-icon"><?= icon('share', 24) ?></div>
        <div>
            <h3 class="info-header-title">ER Diagram</h3>
            <span class="info-header-sub"><?= h($currentDb) ?> — entity relationships</span>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        <button type="button" class="btn btn-primary btn-sm" id="er-auto-layout" title="Auto-arrange tables"><?= icon('share', 13) ?> Auto Layout</button>
        <button type="button" class="btn btn-ghost btn-sm" id="er-fit" title="Fit to view"><?= icon('layers', 13) ?> Fit</button>
        <button type="button" class="btn btn-ghost btn-sm" id="er-zoom-in" title="Zoom in">+</button>
        <button type="button" class="btn btn-ghost btn-sm" id="er-zoom-out" title="Zoom out">−</button>
        <span class="er-zoom-label" id="er-zoom-label">100%</span>
    </div>
</div>

<div class="er-canvas-wrap" id="er-canvas-wrap">
    <div class="er-loading" id="er-loading">
        <?= icon('share', 24) ?>
        <div>Loading schema…</div>
    </div>
    <svg id="er-canvas" class="er-canvas"></svg>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var db = '<?= addslashes($currentDb) ?>';
    var wrap = document.getElementById('er-canvas-wrap');
    var svg = document.getElementById('er-canvas');
    var loading = document.getElementById('er-loading');

    // State
    var tables = [];
    var relations = [];
    var tableBoxes = {}; // name → { x, y, w, h, el }
    var scale = 1;
    var panX = 0, panY = 0;
    var dragging = null; // { name, offsetX, offsetY }
    var panning = false;
    var panStart = { x: 0, y: 0, panX: 0, panY: 0 };

    // Colors from CSS
    var colors = {
        bg: getComputedStyle(document.documentElement).getPropertyValue('--bg-panel').trim() || '#101018',
        bgAlt: getComputedStyle(document.documentElement).getPropertyValue('--bg-panel-alt').trim() || '#13131c',
        border: getComputedStyle(document.documentElement).getPropertyValue('--border').trim() || '#1c1c28',
        text: getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim() || '#d8d8e4',
        textDim: getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim() || '#4e4e5e',
        textSec: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary').trim() || '#8b8b9a',
        accent: getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#4ade80',
        warning: getComputedStyle(document.documentElement).getPropertyValue('--warning').trim() || '#f0a030',
        info: getComputedStyle(document.documentElement).getPropertyValue('--info').trim() || '#6b9fc4',
        gold: getComputedStyle(document.documentElement).getPropertyValue('--gold').trim() || '#e8c34a',
        purple: getComputedStyle(document.documentElement).getPropertyValue('--purple').trim() || '#c084fc',
    };

    var COL_HEIGHT = 22;
    var HEADER_HEIGHT = 32;
    var TABLE_WIDTH = 200;
    var TABLE_PAD = 8;

    // Fetch data
    fetch('ajax.php?action=er_data&db=' + encodeURIComponent(db))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) {
                loading.innerHTML = '<div class="error-box">' + data.error + '</div>';
                return;
            }
            tables = data.tables || [];
            relations = data.relations || [];
            loading.style.display = 'none';
            layout();
            if (relations.length > 0) forceLayout();
            else { render(); fitToView(); }
        });

    // Initial grid layout
    function layout() {
        var cols = Math.max(1, Math.ceil(Math.sqrt(tables.length)));
        var gapX = TABLE_WIDTH + 60;
        var gapY = 280;

        var connected = {};
        relations.forEach(function(r) {
            if (!connected[r.from_table]) connected[r.from_table] = new Set();
            if (!connected[r.to_table]) connected[r.to_table] = new Set();
            connected[r.from_table].add(r.to_table);
            connected[r.to_table].add(r.from_table);
        });

        var sorted = tables.slice().sort(function(a, b) {
            var ca = (connected[a.name] || new Set()).size;
            var cb = (connected[b.name] || new Set()).size;
            return cb - ca;
        });

        sorted.forEach(function(t, i) {
            var col = i % cols;
            var row = Math.floor(i / cols);
            var h = HEADER_HEIGHT + t.columns.length * COL_HEIGHT + TABLE_PAD;
            tableBoxes[t.name] = {
                x: 40 + col * gapX,
                y: 40 + row * gapY,
                w: TABLE_WIDTH,
                h: h,
                table: t,
            };
        });
    }

    // Force-directed layout
    function forceLayout() {
        var names = Object.keys(tableBoxes);
        if (names.length < 2) return;

        // Build adjacency set
        var adj = {};
        names.forEach(function(n) { adj[n] = new Set(); });
        relations.forEach(function(r) {
            if (adj[r.from_table]) adj[r.from_table].add(r.to_table);
            if (adj[r.to_table]) adj[r.to_table].add(r.from_table);
        });

        // Initialize velocities
        var vel = {};
        names.forEach(function(n) { vel[n] = { x: 0, y: 0 }; });

        // Center of mass
        function center() {
            var cx = 0, cy = 0;
            names.forEach(function(n) { cx += tableBoxes[n].x; cy += tableBoxes[n].y; });
            return { x: cx / names.length, y: cy / names.length };
        }

        var REPULSION = 80000;
        var ATTRACTION = 0.005;
        var GRAVITY = 0.01;
        var DAMPING = 0.85;
        var MIN_DIST = 40;
        var ITERATIONS = 300;

        for (var iter = 0; iter < ITERATIONS; iter++) {
            var temp = 1 - iter / ITERATIONS; // cooling
            var c = center();

            // Reset forces
            var fx = {}, fy = {};
            names.forEach(function(n) { fx[n] = 0; fy[n] = 0; });

            // Repulsion between all pairs
            for (var i = 0; i < names.length; i++) {
                for (var j = i + 1; j < names.length; j++) {
                    var a = tableBoxes[names[i]];
                    var b = tableBoxes[names[j]];
                    var dx = a.x - b.x;
                    var dy = a.y - b.y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < MIN_DIST) dist = MIN_DIST;
                    var force = REPULSION / (dist * dist);
                    var nx = dx / dist;
                    var ny = dy / dist;
                    fx[names[i]] += nx * force;
                    fy[names[i]] += ny * force;
                    fx[names[j]] -= nx * force;
                    fy[names[j]] -= ny * force;
                }
            }

            // Attraction along edges
            relations.forEach(function(r) {
                var a = tableBoxes[r.from_table];
                var b = tableBoxes[r.to_table];
                if (!a || !b) return;
                var dx = b.x - a.x;
                var dy = b.y - a.y;
                var dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 1) return;
                var force = dist * ATTRACTION;
                var nx = dx / dist;
                var ny = dy / dist;
                fx[r.from_table] += nx * force;
                fy[r.from_table] += ny * force;
                fx[r.to_table] -= nx * force;
                fy[r.to_table] -= ny * force;
            });

            // Gravity toward center
            names.forEach(function(n) {
                var b = tableBoxes[n];
                fx[n] += (c.x - b.x) * GRAVITY;
                fy[n] += (c.y - b.y) * GRAVITY;
            });

            // Apply forces with damping and cooling
            names.forEach(function(n) {
                vel[n].x = (vel[n].x + fx[n]) * DAMPING * temp;
                vel[n].y = (vel[n].y + fy[n]) * DAMPING * temp;
                tableBoxes[n].x += vel[n].x;
                tableBoxes[n].y += vel[n].y;
            });
        }

        // Snap to avoid overlap — push apart any tables that overlap
        for (var pass = 0; pass < 10; pass++) {
            var moved = false;
            for (var i = 0; i < names.length; i++) {
                for (var j = i + 1; j < names.length; j++) {
                    var a = tableBoxes[names[i]];
                    var b = tableBoxes[names[j]];
                    var overlapX = (a.w / 2 + b.w / 2 + 30) - Math.abs((a.x + a.w/2) - (b.x + b.w/2));
                    var overlapY = (a.h / 2 + b.h / 2 + 20) - Math.abs((a.y + a.h/2) - (b.y + b.h/2));
                    if (overlapX > 0 && overlapY > 0) {
                        var pushX = overlapX / 2 + 5;
                        var pushY = overlapY / 2 + 5;
                        if (overlapX < overlapY) {
                            if (a.x < b.x) { a.x -= pushX; b.x += pushX; }
                            else { a.x += pushX; b.x -= pushX; }
                        } else {
                            if (a.y < b.y) { a.y -= pushY; b.y += pushY; }
                            else { a.y += pushY; b.y -= pushY; }
                        }
                        moved = true;
                    }
                }
            }
            if (!moved) break;
        }

        // Round positions
        names.forEach(function(n) {
            tableBoxes[n].x = Math.round(tableBoxes[n].x);
            tableBoxes[n].y = Math.round(tableBoxes[n].y);
        });

        render();
        fitToView();
    }

    document.getElementById('er-auto-layout').addEventListener('click', function() {
        forceLayout();
    });

    // SVG helpers
    function svgEl(tag, attrs) {
        var el = document.createElementNS('http://www.w3.org/2000/svg', tag);
        for (var k in attrs) el.setAttribute(k, attrs[k]);
        return el;
    }

    function render() {
        svg.innerHTML = '';

        // Defs for arrow markers
        var defs = svgEl('defs', {});
        var marker = svgEl('marker', { id: 'arrowhead', markerWidth: '8', markerHeight: '6', refX: '8', refY: '3', orient: 'auto' });
        var poly = svgEl('polygon', { points: '0 0, 8 3, 0 6', fill: colors.gold });
        marker.appendChild(poly);
        defs.appendChild(marker);
        svg.appendChild(defs);

        // Container group for zoom/pan
        var g = svgEl('g', { id: 'er-group', transform: 'translate(' + panX + ',' + panY + ') scale(' + scale + ')' });

        // Draw relations first (behind tables)
        // Pre-compute edge offsets to prevent line overlap
        var edgeSlots = {}; // 'tableName-side' → count
        function getSlot(table, side) {
            var key = table + '-' + side;
            if (!edgeSlots[key]) edgeSlots[key] = 0;
            edgeSlots[key]++;
            return edgeSlots[key];
        }

        var relGroup = svgEl('g', { class: 'er-relations' });

        relations.forEach(function(rel, ri) {
            var from = tableBoxes[rel.from_table];
            var to = tableBoxes[rel.to_table];
            if (!from || !to) return;

            // Find column Y positions
            var fromColIdx = 0, toColIdx = 0;
            from.table.columns.forEach(function(c, i) { if (c.name === rel.from_col) fromColIdx = i; });
            to.table.columns.forEach(function(c, i) { if (c.name === rel.to_col) toColIdx = i; });

            var fromY = from.y + HEADER_HEIGHT + fromColIdx * COL_HEIGHT + COL_HEIGHT / 2;
            var toY = to.y + HEADER_HEIGHT + toColIdx * COL_HEIGHT + COL_HEIGHT / 2;

            var fromCx = from.x + from.w / 2;
            var toCx = to.x + to.w / 2;

            // Decide which side to exit/enter
            var fromSide, toSide;
            if (fromCx < toCx) {
                fromSide = 'right'; toSide = 'left';
            } else {
                fromSide = 'left'; toSide = 'right';
            }

            // Self-reference
            if (rel.from_table === rel.to_table) {
                fromSide = 'right'; toSide = 'right';
            }

            var x1 = fromSide === 'right' ? from.x + from.w : from.x;
            var x2 = toSide === 'right' ? to.x + to.w : to.x;
            var y1 = fromY;
            var y2 = toY;

            // Orthogonal routing with offset stubs
            var stubLen = 20 + getSlot(rel.from_table, fromSide) * 12;
            var stubLen2 = 20 + getSlot(rel.to_table, toSide) * 12;
            var dir1 = fromSide === 'right' ? 1 : -1;
            var dir2 = toSide === 'right' ? 1 : -1;

            var mx1 = x1 + stubLen * dir1;
            var mx2 = x2 + stubLen2 * dir2;

            // Self-reference: loop out and back
            var d;
            if (rel.from_table === rel.to_table) {
                var loopX = x1 + stubLen * 1.5;
                d = 'M' + x1 + ',' + y1
                  + ' H' + loopX
                  + ' V' + y2
                  + ' H' + x2;
            } else {
                // Choose a midpoint X between the two stubs
                var midX = (mx1 + mx2) / 2;
                d = 'M' + x1 + ',' + y1
                  + ' H' + mx1
                  + ' V' + y2
                  + ' H' + x2;
            }

            var pathEl = svgEl('path', {
                d: d,
                stroke: colors.gold,
                'stroke-width': '1.2',
                fill: 'none',
                opacity: '0.35',
                'stroke-linejoin': 'round',
                'marker-end': 'url(#arrowhead)',
                class: 'er-rel-line',
                'data-from': rel.from_table,
                'data-to': rel.to_table,
            });
            relGroup.appendChild(pathEl);
        });

        g.appendChild(relGroup);

        // Draw tables
        Object.keys(tableBoxes).forEach(function(name) {
            var box = tableBoxes[name];
            var t = box.table;
            var tg = svgEl('g', { class: 'er-table', 'data-table': name, transform: 'translate(' + box.x + ',' + box.y + ')' });

            // Shadow
            tg.appendChild(svgEl('rect', { x: 2, y: 2, width: box.w, height: box.h, rx: 6, fill: 'rgba(0,0,0,0.3)' }));

            // Background
            tg.appendChild(svgEl('rect', { x: 0, y: 0, width: box.w, height: box.h, rx: 6, fill: colors.bg, stroke: colors.border, 'stroke-width': '1' }));

            // Header bg
            tg.appendChild(svgEl('rect', { x: 0, y: 0, width: box.w, height: HEADER_HEIGHT, rx: 6, fill: colors.bgAlt }));
            // Fix bottom corners of header
            tg.appendChild(svgEl('rect', { x: 0, y: HEADER_HEIGHT - 8, width: box.w, height: 8, fill: colors.bgAlt }));
            // Header border
            tg.appendChild(svgEl('line', { x1: 0, y1: HEADER_HEIGHT, x2: box.w, y2: HEADER_HEIGHT, stroke: colors.border, 'stroke-width': '1' }));

            // Table name
            var nameEl = svgEl('text', { x: 10, y: HEADER_HEIGHT / 2 + 5, 'font-size': '12', 'font-weight': '700', fill: colors.warning, 'font-family': 'var(--font-mono)', style: 'cursor:move;' });
            nameEl.textContent = name.length > 22 ? name.substring(0, 20) + '…' : name;
            tg.appendChild(nameEl);

            // Row count badge
            var badge = svgEl('text', { x: box.w - 8, y: HEADER_HEIGHT / 2 + 4, 'text-anchor': 'end', 'font-size': '9', fill: colors.textDim, 'font-family': 'var(--font-mono)' });
            badge.textContent = t.rows.toLocaleString();
            tg.appendChild(badge);

            // Columns
            t.columns.forEach(function(col, ci) {
                var cy = HEADER_HEIGHT + ci * COL_HEIGHT;
                var isPK = col.key === 'PRI';
                var isFK = relations.some(function(r) { return r.from_table === name && r.from_col === col.name; });

                // Row highlight for PK
                if (isPK) {
                    tg.appendChild(svgEl('rect', { x: 1, y: cy, width: box.w - 2, height: COL_HEIGHT, fill: 'rgba(74,222,128,0.04)' }));
                }

                // PK/FK indicator
                if (isPK) {
                    var keyIcon = svgEl('text', { x: 8, y: cy + COL_HEIGHT / 2 + 4, 'font-size': '9', fill: colors.accent, 'font-family': 'var(--font-mono)', 'font-weight': '700' });
                    keyIcon.textContent = 'PK';
                    tg.appendChild(keyIcon);
                } else if (isFK) {
                    var fkIcon = svgEl('text', { x: 8, y: cy + COL_HEIGHT / 2 + 4, 'font-size': '9', fill: colors.gold, 'font-family': 'var(--font-mono)', 'font-weight': '700' });
                    fkIcon.textContent = 'FK';
                    tg.appendChild(fkIcon);
                }

                // Column name
                var colNameEl = svgEl('text', {
                    x: isPK || isFK ? 28 : 10,
                    y: cy + COL_HEIGHT / 2 + 4,
                    'font-size': '11',
                    fill: isPK ? colors.text : colors.textSec,
                    'font-family': 'var(--font-mono)',
                    'font-weight': isPK ? '600' : '400',
                });
                var displayName = col.name.length > 18 ? col.name.substring(0, 16) + '…' : col.name;
                colNameEl.textContent = displayName;
                tg.appendChild(colNameEl);

                // Column type (right-aligned)
                var shortType = col.type.replace(/\(\d+[\d,]*\)/g, '').toUpperCase();
                if (shortType.length > 8) shortType = shortType.substring(0, 7) + '…';
                var typeEl = svgEl('text', { x: box.w - 8, y: cy + COL_HEIGHT / 2 + 4, 'text-anchor': 'end', 'font-size': '9', fill: colors.textDim, 'font-family': 'var(--font-mono)' });
                typeEl.textContent = shortType;
                tg.appendChild(typeEl);

                // Row separator
                if (ci < t.columns.length - 1) {
                    tg.appendChild(svgEl('line', { x1: 0, y1: cy + COL_HEIGHT, x2: box.w, y2: cy + COL_HEIGHT, stroke: colors.border, 'stroke-width': '0.5', opacity: '0.4' }));
                }
            });

            g.appendChild(tg);
        });

        svg.appendChild(g);
        updateCanvasSize();
    }

    function updateCanvasSize() {
        var wrapW = wrap.clientWidth;
        var wrapH = wrap.clientHeight;
        svg.setAttribute('width', wrapW);
        svg.setAttribute('height', wrapH);
    }

    function updateTransform() {
        var g = document.getElementById('er-group');
        if (g) g.setAttribute('transform', 'translate(' + panX + ',' + panY + ') scale(' + scale + ')');
        document.getElementById('er-zoom-label').textContent = Math.round(scale * 100) + '%';
    }

    function fitToView() {
        var keys = Object.keys(tableBoxes);
        if (!keys.length) return;
        var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
        keys.forEach(function(k) {
            var b = tableBoxes[k];
            minX = Math.min(minX, b.x);
            minY = Math.min(minY, b.y);
            maxX = Math.max(maxX, b.x + b.w);
            maxY = Math.max(maxY, b.y + b.h);
        });
        var contentW = maxX - minX + 80;
        var contentH = maxY - minY + 80;
        var wrapW = wrap.clientWidth;
        var wrapH = wrap.clientHeight;
        scale = Math.min(1.5, Math.min(wrapW / contentW, wrapH / contentH));
        scale = Math.max(0.15, scale);
        panX = (wrapW - contentW * scale) / 2 - minX * scale + 40 * scale;
        panY = (wrapH - contentH * scale) / 2 - minY * scale + 40 * scale;
        updateTransform();
    }

    // ── Interaction: Drag tables ──
    svg.addEventListener('mousedown', function(e) {
        var tableEl = e.target.closest('.er-table');
        if (tableEl) {
            var name = tableEl.dataset.table;
            var box = tableBoxes[name];
            if (!box) return;
            var pt = svgPoint(e);
            dragging = { name: name, offsetX: pt.x - box.x, offsetY: pt.y - box.y };
            tableEl.style.cursor = 'grabbing';
            e.preventDefault();
            return;
        }
        // Pan
        panning = true;
        panStart = { x: e.clientX, y: e.clientY, panX: panX, panY: panY };
        svg.style.cursor = 'grabbing';
        e.preventDefault();
    });

    svg.addEventListener('mousemove', function(e) {
        if (dragging) {
            var pt = svgPoint(e);
            tableBoxes[dragging.name].x = pt.x - dragging.offsetX;
            tableBoxes[dragging.name].y = pt.y - dragging.offsetY;
            render();
            updateTransform();
        } else if (panning) {
            panX = panStart.panX + (e.clientX - panStart.x);
            panY = panStart.panY + (e.clientY - panStart.y);
            updateTransform();
        }
    });

    document.addEventListener('mouseup', function() {
        dragging = null;
        panning = false;
        svg.style.cursor = '';
    });

    function svgPoint(e) {
        var rect = svg.getBoundingClientRect();
        return {
            x: (e.clientX - rect.left - panX) / scale,
            y: (e.clientY - rect.top - panY) / scale,
        };
    }

    // ── Zoom ──
    wrap.addEventListener('wheel', function(e) {
        e.preventDefault();
        var delta = e.deltaY > 0 ? 0.9 : 1.1;
        var rect = svg.getBoundingClientRect();
        var mx = e.clientX - rect.left;
        var my = e.clientY - rect.top;
        var newScale = Math.max(0.1, Math.min(3, scale * delta));
        panX = mx - (mx - panX) * (newScale / scale);
        panY = my - (my - panY) * (newScale / scale);
        scale = newScale;
        updateTransform();
    }, { passive: false });

    document.getElementById('er-zoom-in').addEventListener('click', function() {
        scale = Math.min(3, scale * 1.2);
        updateTransform();
    });
    document.getElementById('er-zoom-out').addEventListener('click', function() {
        scale = Math.max(0.1, scale / 1.2);
        updateTransform();
    });
    document.getElementById('er-fit').addEventListener('click', fitToView);

    window.addEventListener('resize', function() {
        updateCanvasSize();
    });

    // Double-click table → navigate to it
    svg.addEventListener('dblclick', function(e) {
        var tableEl = e.target.closest('.er-table');
        if (tableEl) {
            window.location.href = '?db=' + encodeURIComponent(db) + '&table=' + encodeURIComponent(tableEl.dataset.table) + '&tab=structure';
        }
    });

    // ── Hover highlight relationships ──
    svg.addEventListener('mouseover', function(e) {
        var tableEl = e.target.closest('.er-table');
        if (!tableEl) return;
        var name = tableEl.dataset.table;
        svg.querySelectorAll('.er-rel-line').forEach(function(line) {
            if (line.dataset.from === name || line.dataset.to === name) {
                line.setAttribute('opacity', '0.9');
                line.setAttribute('stroke-width', '2');
            } else {
                line.setAttribute('opacity', '0.1');
            }
        });
    });
    svg.addEventListener('mouseout', function(e) {
        var tableEl = e.target.closest('.er-table');
        if (!tableEl) return;
        svg.querySelectorAll('.er-rel-line').forEach(function(line) {
            line.setAttribute('opacity', '0.35');
            line.setAttribute('stroke-width', '1.2');
        });
    });
});
</script>
