<style>
    #map {
        width: 100%;
        height: 460px;
        background: #0F2609;
        position: relative;
    }

    #map-sidebar {
        position: absolute;
        top: 0;
        width: 300px;
        height: 100%;
        left: -320px;
        display: flex;
        flex-direction: column;
        background: linear-gradient(160deg, #021E24, #032A36);
        border-right: 1px solid rgba(255, 255, 255, .10);
        /*box-shadow: 6px 0 30px rgba(0, 0, 0, .45);*/
        z-index: 1000;
        transition: left .32s cubic-bezier(.4, 0, .2, 1);
    }

    .sb-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        border-bottom: 1px solid rgba(255, 255, 255, .08);
    }

    /* ← FIX: accent bar + label sejajar */
    .sb-header-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sb-header-label {
        font-size: 9px;
        letter-spacing: 2px;
        text-transform: uppercase;
        opacity: .55;
    }

    .sb-accent-bar {
        width: 3px;
        height: 18px;
        border-radius: 3px;
        background: linear-gradient(#22c55e, #16a34a);
    }

    .sb-close-btn {
        width: 26px;
        height: 26px;
        font-size: 14px;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, .15);
        background: rgba(255, 255, 255, .08);
        color: #fff;
        cursor: pointer;
        transition: .2s;
    }

    .sb-close-btn:hover {
        background: rgba(255, 255, 255, .18);
    }

    .sb-body {
        flex: 1;
        overflow-y: auto;
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        font-size: 12px;
    }

    .sb-field-label {
        font-size: 9px;
        opacity: .4;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .sb-divider {
        height: 1px;
        background: rgba(255, 255, 255, .08);
    }

    .sb-footer {
        padding: 12px 14px;
        border-top: 1px solid rgba(255, 255, 255, .08);
    }

    #sb-link {
        display: block;
        text-align: center;
        padding: 10px;
        font-size: 12px;
        border-radius: 8px;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        text-decoration: none;
        transition: .2s;
    }

    #sb-link:hover {
        opacity: .9;
    }

    @media (max-width: 768px) {
        #map {
            height: 250px;
        }

        #map-sidebar {
            position: absolute;
            top: 0;
            width: 300px;
            height: 100%;
            left: -100%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(160deg, #021E24, #032A36);
            border-right: 1px solid rgba(255, 255, 255, .10);
            border-radius: 0 12px 12px 0;
            box-shadow: 6px 0 30px rgba(0, 0, 0, .45);
            transition: left .32s ease;
            z-index: 1000;
        }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prunecluster@2.1.0/LeafletStyleSheet.css" />

<div class="w-full bg-[#0B1E07] pt-4 pb-14 px-4 text-white">

    {{-- PANEL HEADER --}}
    <div class="max-w-7xl mx-auto flex items-center justify-between gap-4 py-3">
        <h2 class="flex items-center gap-2 font-data text-[10px] tracking-[0.22em] uppercase text-white/60">
            <span class="w-2 h-2 rounded-full bg-[#9BDB4D]"></span>
            Peta Sebaran Kasus
        </h2>
        <span class="hidden sm:block font-data text-[10px] tracking-[0.22em] uppercase text-white/35">
            Klik titik untuk detail kasus
        </span>
    </div>

    {{-- FILTERS (di atas peta) --}}
    <div class="max-w-7xl mx-auto">
        @livewire('peta.map-filter')
    </div>

    {{-- MAP WRAPPER --}}
    <div class="max-w-7xl mx-auto"
        style="position: relative; overflow: hidden; border-radius: 10px; border: 1px solid rgba(255,255,255,.14);">

        {{-- SIDEBAR --}}
        <div id="map-sidebar">

            <div class="sb-header">
                <div class="sb-header-left">
                    <div class="sb-accent-bar"></div>
                    <span class="sb-header-label">Detail Kasus</span>
                </div>
                <button id="sidebar-close" class="sb-close-btn" aria-label="Tutup">&#x2715;</button>
            </div>

            <div class="sb-body">

                <div id="sb-status-badge" style="display:none;">
                    <span id="sb-status-text"
                        style="font-size:11px;font-weight:700;letter-spacing:1.5px;
                               padding:5px 14px;border-radius:20px;text-transform:uppercase;
                               font-family:'Poppins',sans-serif;"></span>
                </div>

                <div>
                    <div class="sb-field-label">Judul Kasus</div>
                    <div id="sb-title"
                        style="font-size:15px;font-weight:600;line-height:1.65;opacity:0.95;font-family:'Poppins',sans-serif;">
                    </div>
                </div>

                <div class="sb-divider"></div>

                <div id="sb-cat-wrap">
                    <div class="sb-field-label">Kategori</div>
                    <div id="sb-category"
                        style="font-size:13px;opacity:0.85;background:rgba(255,255,255,0.07);
                               padding:6px 12px;border-radius:7px;display:inline-block;
                               font-family:'Poppins',sans-serif;"></div>
                </div>

                <div id="sb-date-wrap">
                    <div class="sb-field-label">Tanggal</div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <svg width="14" height="14" fill="none" stroke="rgba(255,255,255,0.45)"
                            stroke-width="2" viewBox="0 0 24 24" style="flex-shrink:0;">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        <div id="sb-date"
                            style="font-size:13px;opacity:0.85;font-family:'Poppins',sans-serif;"></div>
                    </div>
                </div>

                <div class="sb-divider"></div>

                <div id="sb-desc-wrap">
                    <div class="sb-field-label">Deskripsi</div>
                    <div id="sb-desc"
                        style="font-size:13px;opacity:0.72;line-height:1.85;font-family:'Poppins',sans-serif;"></div>
                </div>

            </div>

            <div class="sb-footer">
                <a id="sb-link" href="#">Lihat Detail Kasus &rarr;</a>
            </div>

        </div>
        {{-- END SIDEBAR --}}

        <div id="map" class="relative z-1"></div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {

    // ===== SIDEBAR =====
    const sidebar      = document.getElementById('map-sidebar');
    const sidebarClose = document.getElementById('sidebar-close');

    const statusColorMap = {
        open:          { bg: '#1d4ed8', label: 'Open' },
        investigation: { bg: '#b45309', label: 'Investigation' },
        prosecution:   { bg: '#b91c1c', label: 'Prosecution' },
        trial:         { bg: '#7c3aed', label: 'Trial' },
        executed:      { bg: '#6b4226', label: 'Executed' },
        closed:        { bg: '#4b5563', label: 'Closed' },
        rejected:      { bg: '#9d174d', label: 'Rejected' },
        published:     { bg: '#15803d', label: 'Published' },
        completed:     { bg: '#0e7490', label: 'Completed' }
    };

    function openSidebar(props, caseNumber, safeTitle, safeDesc) {
        document.getElementById('sb-title').textContent = safeTitle || '-';

        const statusKey = props.status_key || '';
        const sbBadge   = document.getElementById('sb-status-badge');
        const sbText    = document.getElementById('sb-status-text');

        if (statusKey && statusColorMap[statusKey]) {
            const sc             = statusColorMap[statusKey];
            sbText.textContent   = sc.label;
            sbText.style.background = sc.bg + '28';
            sbText.style.color   = sc.bg;
            sbText.style.border  = '1px solid ' + sc.bg + '70';
            sbBadge.style.display = 'block';
        } else {
            sbBadge.style.display = 'none';
        }

        const catWrap = document.getElementById('sb-cat-wrap');
        if (props.category) {
            document.getElementById('sb-category').textContent = props.category;
            catWrap.style.display = 'block';
        } else {
            catWrap.style.display = 'none';
        }

        const dateWrap = document.getElementById('sb-date-wrap');
        if (props.event_date) {
            document.getElementById('sb-date').textContent = props.event_date;
            dateWrap.style.display = 'block';
        } else {
            dateWrap.style.display = 'none';
        }

        const descWrap = document.getElementById('sb-desc-wrap');
        if (safeDesc) {
            document.getElementById('sb-desc').textContent = safeDesc;
            descWrap.style.display = 'block';
        } else {
            descWrap.style.display = 'none';
        }

        const detailBase = '/{{ app()->getLocale() }}/detail-case/';
        const sbLink     = document.getElementById('sb-link');
        if (caseNumber) {
            sbLink.href          = detailBase + encodeURIComponent(caseNumber);
            sbLink.style.display = 'block';
        } else {
            sbLink.style.display = 'none';
        }

        sidebar.style.left = '0';
    }

    function closeSidebar() {
        sidebar.style.left = '-320px';
    }

    sidebarClose.addEventListener('click', closeSidebar);
    // ===== END SIDEBAR =====


    // ===== MAP =====
    var map = L.map('map', {
        zoomControl: false,
        center: [-2.5, 118],
        zoom: 5,
        gestureHandling: true,
        gestureHandlingOptions: {
            text: {
                touch:     "Gunakan dua jari untuk menggeser peta",
                scroll:    "Gunakan Ctrl + scroll untuk zoom peta",
                scrollMac: "Gunakan \u2318 + scroll untuk zoom peta"
            }
        },
        attributionControl: false,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        touchZoom: true,
        boxZoom: true,
        keyboard: false
    });

    map.on('click', function () { closeSidebar(); });
    map.dragging.enable();
    L.tileLayer('', {}).addTo(map);


    // ===== PRUNECLUSTER =====
    var pruneCluster = new PruneClusterForLeaflet(120, 20);

    pruneCluster.BuildLeafletClusterIcon = function (cluster) {
        var count = cluster.population;
        var size  = count < 10 ? 36 : count < 100 ? 44 : 54;
        var fs    = count < 10 ? 13 : count < 100 ? 14 : 15;
        var r     = size / 2;

        var colorCount = {};
        try {
            var clusterMarkers = pruneCluster.Cluster.FindMarkersInArea(cluster.bounds);
            clusterMarkers.forEach(function (m) {
                var col = m.data && m.data.color ? m.data.color : '#0e7490';
                colorCount[col] = (colorCount[col] || 0) + 1;
            });
        } catch(e) {}

        var segments = Object.keys(colorCount).map(col => ({ color: col, count: colorCount[col] }));
        if (segments.length === 0) segments = [{ color: '#0e7490', count: count }];

        var svgParts = '';

        if (segments.length === 1) {
            svgParts = `<circle cx="${r}" cy="${r}" r="${r}" fill="${segments[0].color}" />`;
        } else {
            var total      = segments.reduce((s, sg) => s + sg.count, 0);
            var startAngle = -Math.PI / 2;

            segments.forEach(function (sg) {
                var sweep    = (sg.count / total) * 2 * Math.PI;
                var endAngle = startAngle + sweep;
                var x1 = r + r * Math.cos(startAngle);
                var y1 = r + r * Math.sin(startAngle);
                var x2 = r + r * Math.cos(endAngle);
                var y2 = r + r * Math.sin(endAngle);
                var large = sweep > Math.PI ? 1 : 0;
                svgParts += `<path d="M${r},${r} L${x1},${y1} A${r},${r} 0 ${large} 1 ${x2},${y2} Z" fill="${sg.color}" />`;
                startAngle = endAngle;
            });

            startAngle = -Math.PI / 2;
            segments.forEach(function (sg) {
                var x1 = r + r * Math.cos(startAngle);
                var y1 = r + r * Math.sin(startAngle);
                svgParts += `<line x1="${r}" y1="${r}" x2="${x1}" y2="${y1}" stroke="rgba(255,255,255,0.7)" stroke-width="1.5"/>`;
                startAngle += (sg.count / total) * 2 * Math.PI;
            });
        }

        return L.divIcon({
            html: `
                <div style="width:${size}px;height:${size}px;position:relative;cursor:pointer;
                            filter:drop-shadow(0 3px 14px rgba(0,0,0,0.4));">
                    <svg width="${size}" height="${size}" style="border-radius:50%;overflow:hidden;display:block;">
                        ${svgParts}
                        <circle cx="${r}" cy="${r}" r="${r-1}" fill="none"
                            stroke="rgba(255,255,255,0.65)" stroke-width="1.5"/>
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;align-items:center;
                                justify-content:center;color:#fff;font-size:${fs}px;font-weight:700;
                                font-family:'Poppins',sans-serif;text-shadow:0 1px 4px rgba(0,0,0,0.7);">
                        ${count}
                    </div>
                </div>`,
            className:  '',
            iconSize:   L.point(size, size),
            iconAnchor: L.point(r, r),
        });
    };

    pruneCluster.PrepareLeafletMarker = function (leafletMarker, data) {
        leafletMarker.setIcon(L.divIcon({
            html: `<div style="width:14px;height:14px;background:${data.color || '#2ca02c'};
                border:2px solid #fff;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,0.4);"></div>`,
            className:  '',
            iconSize:   L.point(14, 14),
            iconAnchor: L.point(7, 7),
        }));

        leafletMarker.off('click');
        leafletMarker.on('click', function (e) {
            L.DomEvent.stopPropagation(e);
            openSidebar(data.props || {}, data.caseNumber || null, data.safeTitle || '', data.safeDesc || '');
        });
    };

    map.addLayer(pruneCluster);


    // ===== GEOJSON BASEMAP =====
    fetch("/id.geojson")
        .then(res => res.json())
        .then(data => {

            var geoLayer = L.geoJSON(data, {
                style: {
                    color:       "#0F2609",
                    weight:      1,
                    fillColor:   "#D9E0CC",
                    fillOpacity: 1
                }
            }).addTo(map);

            if (window.innerWidth > 768) {
                map.setView([-2.5, 118], 5);
            } else {
                setTimeout(() => {
                    map.fitBounds(geoLayer.getBounds(), { padding: [30, 30] });
                }, 100);
            }


            // ===== LOAD MARKERS =====
            function loadGeometries(filters = {}) {
                const qs  = new URLSearchParams({
                    ...filters,
                    locale: document.documentElement.lang || '{{ app()->getLocale() }}'
                }).toString();
                const url = '/case-geometries' + (qs ? ('?' + qs) : '');

                fetch(url)
                    .then(res => res.json())
                    .then(geomData => {
                        if (!(geomData && Array.isArray(geomData.features))) return;

                        pruneCluster.RemoveMarkers();

                        // ← FIX: strip HTML helper
                        const stripHtml = html => {
                            if (!html) return '';
                            const d = document.createElement('div');
                            d.innerHTML = html;
                            return (d.textContent || d.innerText || '').trim();
                        };

                        const truncate = (txt, max) =>
                            (!txt || txt.length <= max) ? (txt || '') : txt.slice(0, max).trimEnd() + '…';

                        const markerStatusColors = {
                            open:          '#1f78b4',
                            investigation: '#ff7f0e',
                            prosecution:   '#d62728',
                            trial:         '#9467bd',
                            executed:      '#8c564b',
                            closed:        '#7f7f7f',
                            rejected:      '#e377c2',
                            published:     '#2ca02c',
                            completed:     '#17becf'
                        };

                        function colorFromString(str) {
                            if (!str) return '#2c3e50';
                            let hash = 0;
                            for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
                            return `hsl(${Math.abs(hash) % 360} 70% 45%)`;
                        }

                        geomData.features.forEach(f => {
                            if (!f.geometry || !f.geometry.type) return;

                            const caseNumber = f.properties.case_number || f.properties.case_id || null;
                            const rawTitle   = f.properties.title || ('Case ' + (caseNumber || ''));

                            // ← FIX: strip HTML + truncate untuk title dan desc
                            const safeTitle  = truncate(stripHtml(rawTitle), 120);
                            const safeDesc   = truncate(stripHtml(f.properties.case_description || ''), 240);

                            const color = markerStatusColors[f.properties.status_key]
                                       ?? colorFromString(f.properties.category || rawTitle);

                            function registerAt(lat, lng) {
                                var m      = new PruneCluster.Marker(lat, lng);
                                m.data     = { color, props: f.properties, caseNumber, safeTitle, safeDesc };
                                m.category = 0;
                                pruneCluster.RegisterMarker(m);
                            }

                            if (f.geometry.type === 'Point') {
                                registerAt(f.geometry.coordinates[1], f.geometry.coordinates[0]);
                            }

                            if (f.geometry.type === 'Polygon' || f.geometry.type === 'MultiPolygon') {
                                try {
                                    const c = L.geoJSON(f.geometry).getBounds().getCenter();
                                    registerAt(c.lat, c.lng);
                                } catch (e) {}
                            }
                        });

                        pruneCluster.ProcessView();
                        closeSidebar();
                    })
                    .catch(err => console.error('Failed to load case geometries', err));
            }

            window.loadGeometries = loadGeometries;

            if (window.pendingLeafletFilter) {
                try { window.loadGeometries(window.pendingLeafletFilter); } catch (e) {}
                window.pendingLeafletFilter = null;
            }
            if (window.pendingLeafletReset) {
                try { window.loadGeometries(); } catch (e) {}
                window.pendingLeafletReset = null;
            }

            loadGeometries();

            window.addEventListener('apply-leaflet-filter', function (e) {
                const detail  = Array.isArray(e.detail) ? (e.detail[0] || {}) : (e.detail || {});
                const payload = {
                    sector: detail.sector ?? '',
                    status: detail.status ?? '',
                    search: detail.search ?? '',
                    lat:    detail.lat    ?? '',
                    lng:    detail.lng    ?? '',
                    radius: detail.radius ?? ''
                };
                if (typeof window.loadGeometries === 'function') {
                    window.loadGeometries(payload);
                } else {
                    window.pendingLeafletFilter = payload;
                }
            });

            window.addEventListener('reset-leaflet-filter', function () {
                if (typeof window.loadGeometries === 'function') {
                    window.loadGeometries();
                } else {
                    window.pendingLeafletReset = true;
                }
            });

            setTimeout(() => { map.dragging.disable(); }, 400);
        })
        .catch(err => console.error("ERROR:", err));
    });
</script>
