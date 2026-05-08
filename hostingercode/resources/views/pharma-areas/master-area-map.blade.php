@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/vis-network{!!'@'!!}9.1.9/styles/vis-network.min.css">
<style>
    #master-area-map-network {
        width: 100%;
        height: min(85vh, 980px);
        min-height: 560px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #fafbfd;
    }
    .map-stat-chip {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        text-align: center;
        color: #fff;
        font-size: 0.85rem;
    }
    .map-stat-chip strong { font-size: 1.35rem; display: block; }
    .map-stat-chip.root { background: linear-gradient(135deg, #5c4d7a 0%, #3d3355 100%); }
    .map-stat-chip.zone { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); }
    .map-stat-chip.region { background: linear-gradient(135deg, #0d6efd 0%, #084298 100%); }
    .map-stat-chip.area { background: linear-gradient(135deg, #198754 0%, #146c43 100%); }
    .map-stat-chip.headquarter { background: linear-gradient(135deg, #8bab4c 0%, #6d8a3c 100%); }
    .map-stat-chip.station { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
    #detail-panel {
        min-height: 280px;
    }
    .subtree-hint { font-size: 0.8rem; color: #6c757d; }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-1">Master Area Map</h4>
            <p class="text-muted mb-0 f-14">
                Interactive hierarchy: Zone → Region → Area → Headquarter → Ex-stations / Out-stations (via HQ assignments).
                Turn off <strong>Show Ex / Out stations</strong> for a simpler tree. Scroll or pinch to zoom, drag to pan.
            </p>
        </div>
        <div class="mt-2 mt-lg-0">
            <label class="switch mb-0 d-flex align-items-center f-14">
                <input type="checkbox" id="toggle-stations" checked class="mr-2">
                <span>Show Ex / Out stations</span>
            </label>
        </div>
    </div>

    <div class="row mb-3" id="global-stats-row">
        <div class="col-6 col-md-2 mb-2">
            <div class="map-stat-chip zone"><strong id="stat-zones">{{ $graphStats['zones'] ?? 0 }}</strong>Zones</div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="map-stat-chip region"><strong id="stat-regions">{{ $graphStats['regions'] ?? 0 }}</strong>Regions</div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="map-stat-chip area"><strong id="stat-areas">{{ $graphStats['areas'] ?? 0 }}</strong>Areas</div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="map-stat-chip headquarter"><strong id="stat-headquarters">{{ $graphStats['headquarters'] ?? 0 }}</strong>Headquarters</div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="map-stat-chip station"><strong id="stat-ex">{{ $graphStats['exstations'] ?? 0 }}</strong>Ex-stations</div>
        </div>
        <div class="col-6 col-md-2 mb-2">
            <div class="map-stat-chip station"><strong id="stat-out">{{ $graphStats['outstations'] ?? 0 }}</strong>Out-stations</div>
        </div>
    </div>
    <p class="subtree-hint mb-2" id="subtree-hint" style="display:none;">
        <i class="fa fa-filter"></i> Counts below reflect the <strong>selected subtree</strong> (including the node you clicked).
    </p>

    <div class="row">
        <div class="col-12 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                    <div id="master-area-map-network"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-5 col-xl-4 mb-3">
            <div class="card border-0 shadow-sm h-100" id="detail-panel">
                <div class="card-header bg-white border-bottom-0">
                    <h6 class="mb-0"><i class="fa fa-info-circle text-primary"></i> Selected node</h6>
                </div>
                <div class="card-body f-14" id="detail-body">
                    <p class="text-muted mb-0">Click any node in the map to see name, type, database id, and quick links to manage records.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/vis-network{!!'@'!!}9.1.9/standalone/umd/vis-network.min.js"></script>
<script>
window.__MASTER_AREA_MAP__ = @json($masterAreaMapClient ?? []);
</script>
@verbatim
<script>
(function () {
    var C = window.__MASTER_AREA_MAP__ || {};
    var visNodesFull = C.visNodes || [];
    var visEdgesFull = C.visEdges || [];
    var nodesMeta = C.nodesMeta || {};
    var globalStats = C.graphStats || {};
    var manageLinks = C.manageLinks || {};
    var groups = C.groups || {};

    var network = null;
    var currentEdges = [];

    function filterGraph(showStations) {
        if (showStations) {
            return { nodes: visNodesFull, edges: visEdgesFull };
        }
        var hide = new Set(['exstation', 'outstation']);
        var nodeIds = new Set(
            visNodesFull.filter(function (n) { return !hide.has(n.group); }).map(function (n) { return n.id; })
        );
        var edges = visEdgesFull.filter(function (e) { return nodeIds.has(e.from) && nodeIds.has(e.to); });
        var nodes = visNodesFull.filter(function (n) { return nodeIds.has(n.id); });
        return { nodes: nodes, edges: edges };
    }

    function buildNetwork(showStations) {
        var fe = filterGraph(showStations);
        var nodes = fe.nodes;
        var edges = fe.edges;
        currentEdges = edges;
        var container = document.getElementById('master-area-map-network');
        if (network) {
            network.destroy();
            network = null;
        }
        if (typeof vis === 'undefined' || !vis.Network) {
            container.innerHTML = '<div class="alert alert-warning m-3">Chart library failed to load. Check your network connection or firewall (vis-network from CDN).</div>';
            return;
        }
        if (nodes.length === 0) {
            container.innerHTML = '<div class="alert alert-info m-3">No map data for this company.</div>';
            return;
        }
        var hasHierarchy = edges.length > 0;
        var nodeCount = nodes.length;
        var dense = nodeCount > 220;
        var data = { nodes: nodes, edges: edges };
        var options = {
            layout: {
                hierarchical: hasHierarchy ? {
                    enabled: true,
                    direction: 'UD',
                    sortMethod: 'directed',
                    levelSeparation: dense ? 165 : 240,
                    nodeSpacing: dense ? 185 : 300,
                    treeSpacing: dense ? 280 : 420,
                    blockShifting: true,
                    edgeMinimization: false,
                    parentCentralization: true,
                    shakeTowards: 'leaves',
                } : { enabled: false },
            },
            physics: { enabled: false },
            interaction: {
                hover: true,
                navigationButtons: true,
                keyboard: true,
                zoomView: true,
                zoomSpeed: 1.05,
            },
            nodes: {
                borderWidth: 1,
                shadow: true,
                shape: 'box',
                margin: { top: 14, right: 18, bottom: 14, left: 18 },
                font: { face: 'system-ui, "Segoe UI", sans-serif' },
                widthConstraint: { maximum: 210 },
            },
            edges: { arrows: { to: { enabled: true } }, smooth: hasHierarchy ? { type: 'cubicBezier', forceDirection: 'vertical' } : true },
            groups: groups,
        };
        try {
            network = new vis.Network(container, data, options);
        } catch (err) {
            console.error('Master Area Map:', err);
            container.innerHTML = '<div class="alert alert-danger m-3">Could not render the map: ' + escapeHtml(String(err.message || err)) + '</div>';
            return;
        }
        network.on('click', function (params) {
            if (params.nodes.length === 1) {
                renderDetail(params.nodes[0]);
                updateSubtreeStats(params.nodes[0]);
            }
        });
        setTimeout(function () {
            if (network) {
                try {
                    network.fit({
                        animation: { duration: 450, easingFunction: 'easeInOutQuad' },
                        padding: dense ? 40 : 80,
                    });
                } catch (e) { }
            }
        }, 280);
    }

    function descendantIds(nodeId) {
        var out = new Set([nodeId]);
        var changed = true;
        while (changed) {
            changed = false;
            currentEdges.forEach(function (e) {
                if (out.has(e.from) && !out.has(e.to)) {
                    out.add(e.to);
                    changed = true;
                }
            });
        }
        return out;
    }

    function countTypesInSet(idSet) {
        var c = { zone: 0, region: 0, area: 0, headquarter: 0, exstation: 0, outstation: 0 };
        idSet.forEach(function (id) {
            var m = nodesMeta[id];
            if (!m || !m.type) return;
            var t = m.type;
            if (t === 'zone') c.zone++;
            else if (t === 'region') c.region++;
            else if (t === 'area') c.area++;
            else if (t === 'headquarter') c.headquarter++;
            else if (t === 'exstation') c.exstation++;
            else if (t === 'outstation') c.outstation++;
        });
        return c;
    }

    function updateSubtreeStats(selectedId) {
        var hint = document.getElementById('subtree-hint');
        if (selectedId === 'cr_root') {
            hint.style.display = 'none';
            document.getElementById('stat-zones').textContent = globalStats.zones;
            document.getElementById('stat-regions').textContent = globalStats.regions;
            document.getElementById('stat-areas').textContent = globalStats.areas;
            document.getElementById('stat-headquarters').textContent = globalStats.headquarters;
            document.getElementById('stat-ex').textContent = globalStats.exstations;
            document.getElementById('stat-out').textContent = globalStats.outstations;
            return;
        }
        hint.style.display = 'block';
        var ids = descendantIds(selectedId);
        var c = countTypesInSet(ids);
        document.getElementById('stat-zones').textContent = c.zone;
        document.getElementById('stat-regions').textContent = c.region;
        document.getElementById('stat-areas').textContent = c.area;
        document.getElementById('stat-headquarters').textContent = c.headquarter;
        document.getElementById('stat-ex').textContent = c.exstation;
        document.getElementById('stat-out').textContent = c.outstation;
    }

    function renderDetail(nodeId) {
        var meta = nodesMeta[nodeId] || {};
        var type = meta.type || '';
        var body = document.getElementById('detail-body');
        var link = manageLinks[type] || manageLinks.bucket;

        var html = '<dl class="mb-0">';
        html += '<dt class="text-muted f-12 mb-1">Type</dt><dd class="mb-2">' + (type ? type.replace(/_/g, ' ') : '—') + '</dd>';
        if (meta.name) {
            html += '<dt class="text-muted f-12 mb-1">Name</dt><dd class="mb-2">' + escapeHtml(meta.name) + '</dd>';
        }
        if (meta.entityId != null) {
            html += '<dt class="text-muted f-12 mb-1">Record ID</dt><dd class="mb-2"><code>' + meta.entityId + '</code></dd>';
        }
        if (meta.headquarterId) {
            html += '<dt class="text-muted f-12 mb-1">Linked HQ id</dt><dd class="mb-2"><code>' + meta.headquarterId + '</code></dd>';
        }
        if (meta.childCounts && typeof meta.childCounts === 'object') {
            var parts = Object.keys(meta.childCounts).map(function (k) {
                return k.replace(/_/g, ' ') + ': ' + meta.childCounts[k];
            });
            html += '<dt class="text-muted f-12 mb-1">Children</dt><dd class="mb-2"><small>' + escapeHtml(parts.join(' · ')) + '</small></dd>';
        }
        html += '</dl>';
        html += '<hr class="my-3">';
        html += '<a href="' + link.url + '" class="btn btn-primary btn-sm btn-block" target="_blank" rel="noopener">';
        html += '<i class="fa fa-external-link-alt mr-1"></i> ' + escapeHtml(link.label);
        html += '</a>';
        if (type === 'root') {
            html += '<a href="' + manageLinks.zone.url + '" class="btn btn-outline-secondary btn-sm btn-block mt-2" target="_blank" rel="noopener">Zones</a>';
        }
        body.innerHTML = html;
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    document.getElementById('toggle-stations').addEventListener('change', function () {
        buildNetwork(this.checked);
    });

    buildNetwork(true);
    renderDetail('cr_root');
    updateSubtreeStats('cr_root');
})();
</script>
@endverbatim
@endpush
