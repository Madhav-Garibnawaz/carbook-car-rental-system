<?php
// ── Session & Auth ────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_name('driver_session');
    session_start();
}
include("connect.php");

if (!isset($_SESSION['driver_id'])) {
    header("location: register.php"); exit;
}
$driver_id = (int)$_SESSION['driver_id'];

// ── AJAX POST handler ─────────────────────────────────────────────────────────
if (isset($_POST['action'])) {
    ob_clean();
    header('Content-Type: application/json');

    $bid = intval($_POST['booking_id'] ?? 0);

    if ($_POST['action'] === 'start_trip') {
        // Verify this booking belongs to this driver
        $chk = mysqli_query($con,
            "SELECT booking_id FROM booking_master
             WHERE booking_id = $bid AND driver_id = $driver_id LIMIT 1"
        );
        if ($chk && mysqli_num_rows($chk) > 0) {
            mysqli_query($con,
                "UPDATE booking_details SET trip_status = 'Started' WHERE booking_id = $bid"
            );
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'Booking not found for this driver']);
        }
        exit;
    }

    if ($_POST['action'] === 'complete_trip') {
        $chk = mysqli_query($con,
            "SELECT booking_id FROM booking_master
             WHERE booking_id = $bid AND driver_id = $driver_id LIMIT 1"
        );
        if ($chk && mysqli_num_rows($chk) > 0) {
            mysqli_query($con,
                "UPDATE booking_details SET trip_status = 'Completed' WHERE booking_id = $bid"
            );
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Unknown action']);
    exit;
}
?>
<?php include("header.php"); ?>
<?php
$map_pickup_lat  = 'null';
$map_pickup_lng  = 'null';
$map_drop_lat    = 'null';
$map_drop_lng    = 'null';
$active_booking  = null;
$show_start_btn  = false;
$trip_status_now = 'Not Started';

if (isset($_GET['booking_id'])) {
    $target_bid = intval($_GET['booking_id']);

    $mapQ = mysqli_query($con,
        "SELECT bm.pickup_lat, bm.pickup_lng, bm.drop_lat, bm.drop_lng,
                bd.trip_status, bd.booking_status
         FROM booking_master bm
         LEFT JOIN booking_details bd ON bd.booking_id = bm.booking_id
         WHERE bm.booking_id = $target_bid AND bm.driver_id = $driver_id
         LIMIT 1"
    );

    if ($mapQ && mysqli_num_rows($mapQ) > 0) {
        $mapRow = mysqli_fetch_assoc($mapQ);

        $map_pickup_lat  = !empty($mapRow['pickup_lat']) ? (float)$mapRow['pickup_lat'] : 'null';
        $map_pickup_lng  = !empty($mapRow['pickup_lng']) ? (float)$mapRow['pickup_lng'] : 'null';
        $map_drop_lat    = !empty($mapRow['drop_lat'])   ? (float)$mapRow['drop_lat']   : 'null';
        $map_drop_lng    = !empty($mapRow['drop_lng'])   ? (float)$mapRow['drop_lng']   : 'null';
        $trip_status_now = $mapRow['trip_status'] ?? 'Not Started';
        $active_booking  = $target_bid;

        $show_start_btn = (
            isset($_GET['start']) &&
            $_GET['start'] == '1' &&
            $trip_status_now === 'Not Started'
        );
    }
}
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<style>
/* ── Trip Completion Overlay ─────────────────────────────────────────────── */
#tripCompleteOverlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.55);
    align-items: center;
    justify-content: center;
}
#tripCompleteOverlay.show {
    display: flex;
}
.trip-complete-card {
    background: #fff;
    border-radius: 20px;
    padding: 40px 48px;
    text-align: center;
    box-shadow: 0 24px 60px rgba(0,0,0,0.25);
    animation: popIn .4s cubic-bezier(.34,1.56,.64,1) both;
    max-width: 360px;
    width: 90%;
}
@keyframes popIn {
    from { opacity:0; transform:scale(.7); }
    to   { opacity:1; transform:scale(1); }
}
.trip-complete-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg,#10b981,#34d399);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; color: #fff; margin: 0 auto 18px;
    box-shadow: 0 8px 24px rgba(16,185,129,.4);
}
.trip-complete-title {
    font-family: 'Manrope', sans-serif;
    font-size: 22px; font-weight: 800; color: #1e293b; margin-bottom: 8px;
}
.trip-complete-sub {
    font-size: 13px; color: #64748b; margin-bottom: 20px;
}
.trip-redirect-bar {
    height: 4px; border-radius: 99px; background: #e2e8f0; overflow: hidden;
}
.trip-redirect-fill {
    height: 100%;
    background: linear-gradient(90deg,#10b981,#34d399);
    border-radius: 99px;
    width: 0%;
    transition: width 5s linear;
}

/* ── Moving marker label ─────────────────────────────────────────────────── */
#statusBanner {
    position: absolute;
    top: 14px; left: 50%; transform: translateX(-50%);
    z-index: 500;
    background: #1e293b; color: #fff;
    padding: 8px 20px; border-radius: 100px;
    font-size: 13px; font-weight: 700;
    box-shadow: 0 4px 16px rgba(0,0,0,.25);
    display: none;
    white-space: nowrap;
    pointer-events: none;
}
</style>

<!-- Main Content -->
<div class="flex-1 relative overflow-hidden flex flex-col">

    <div id="map" class="absolute inset-0 z-0"></div>

    <!-- Status Banner -->
    <div id="statusBanner"></div>

    <!-- Ride Controls -->
    <div id="rideControls" class="absolute bottom-10 left-1/2 transform -translate-x-1/2 z-40 <?= $active_booking ? '' : 'hidden' ?> flex gap-3">

        <?php if ($show_start_btn): ?>
        <button id="startRideBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-full shadow-lg transition flex items-center gap-2 border-2 border-white">
            <i class="fas fa-play"></i> Start Ride
        </button>
        <?php endif; ?>

        <button id="navigateBtn"
                class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-full shadow-lg transition flex items-center gap-2 border-2 border-white">
            <i class="fas fa-location-arrow"></i> Navigate
        </button>
    </div>

    <!-- SOS Button -->
    <a href="tel:911" class="hidden md:flex absolute bottom-8 left-8 w-16 h-16 bg-danger text-white rounded-full shadow-lg items-center justify-center hover:scale-105 transition font-bold text-xl z-20 border-4 border-white dark:border-gray-800 hover:shadow-red-500/50">
        <i class="fas fa-exclamation-triangle"></i>
    </a>
</div>
</main>

<!-- Trip Complete Overlay -->
<div id="tripCompleteOverlay">
    <div class="trip-complete-card">
        <div class="trip-complete-icon"><i class="fas fa-flag-checkered"></i></div>
        <div class="trip-complete-title">Trip Completed!</div>
        <div class="trip-complete-sub">The ride has been successfully completed.<br>Redirecting to My Rides…</div>
        <div class="trip-redirect-bar"><div class="trip-redirect-fill" id="redirectFill"></div></div>
    </div>
</div>

<script src="script.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    var activePickupLat = <?= $map_pickup_lat ?>;
    var activePickupLng = <?= $map_pickup_lng ?>;
    var activeDropLat   = <?= $map_drop_lat ?>;
    var activeDropLng   = <?= $map_drop_lng ?>;
    var activeBookingId = <?= $active_booking ? intval($active_booking) : 'null' ?>;
    var showStartBtn    = <?= $show_start_btn ? 'true' : 'false' ?>;
    var tripStatusNow   = <?= json_encode($trip_status_now) ?>;

    // ── Map init ──────────────────────────────────────────────────────────────
    var map = L.map('map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // ── Icons ─────────────────────────────────────────────────────────────────
    var driverIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
    });
    var pickupIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
    });
    var dropIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
        iconSize:[25,41], iconAnchor:[12,41], popupAnchor:[1,-34], shadowSize:[41,41]
    });

    var driverLat = null, driverLng = null;
    var driverMark = null, pMark = null, dMark = null;
    var routingControl = null;

    // ── Status banner helper ──────────────────────────────────────────────────
    function showBanner(msg) {
        var b = document.getElementById('statusBanner');
        b.textContent = msg;
        b.style.display = 'block';
    }
    function hideBanner() {
        document.getElementById('statusBanner').style.display = 'none';
    }

    // ── Draw route and get coords back via callback ───────────────────────────
    function drawRoute(waypoints, onCoords) {
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
        routingControl = L.Routing.control({
            waypoints: waypoints,
            routeWhileDragging: false,
            addWaypoints: false,
            show: false,
            fitSelectedRoutes: true,
            createMarker: function () { return null; },
            lineOptions: {
                styles: [{ color: '#2563eb', weight: 5, opacity: 0.85 }]
            },
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            })
        }).addTo(map);

        routingControl.on('routesfound', function (e) {
            var coords = e.routes[0].coordinates;
            var bounds = L.latLngBounds(coords.map(function(c){ return [c.lat, c.lng]; }));
            map.fitBounds(bounds.pad(0.25));
            if (typeof onCoords === 'function') onCoords(coords);
        });

        routingControl.on('routingerror', function (e) {
            console.warn('Routing error:', e.error);
        });
    }

    // ── Animate driver marker along route coords ──────────────────────────────
    // Moves ~1 point every 80ms, skipping points to cover route in ~4-6 seconds
    function animateMarker(coords, onDone) {
        if (!coords || coords.length === 0) {
            if (onDone) onDone();
            return;
        }

        // Target: finish in ~5 seconds with a tick every 80ms → ~62 ticks
        var totalTicks = 60;
        var step = Math.max(1, Math.floor(coords.length / totalTicks));
        var i = 0;

        function tick() {
            if (i >= coords.length) {
                // Snap to exact final point
                var last = coords[coords.length - 1];
                driverMark.setLatLng([last.lat, last.lng]);
                if (onDone) setTimeout(onDone, 0);
                return;
            }
            driverMark.setLatLng([coords[i].lat, coords[i].lng]);
            // Don't pan constantly — just every 5 ticks
            if (i % 5 === 0) map.panTo([coords[i].lat, coords[i].lng], { animate: true, duration: 0.4 });
            i += step;
            setTimeout(tick, 80);
        }
        tick();
    }

    // ── Full animation flow after start_trip succeeds ─────────────────────────
    // Leg 1: driver → pickup  (stop 3-4 s)
    // Leg 2: pickup → drop    (complete trip on arrival)
    function startAnimationFlow() {

        showBanner('🚗 Heading to pickup location…');

        drawRoute(
            [L.latLng(driverLat, driverLng), L.latLng(activePickupLat, activePickupLng)],
            function (leg1Coords) {

                animateMarker(leg1Coords, function () {

                    // Arrived at pickup — pause 3.5 seconds
                    showBanner('📍 Arrived at pickup! Starting trip to drop location…');

                    setTimeout(function () {

                        showBanner('🏁 Heading to drop location…');

                        drawRoute(
                            [L.latLng(activePickupLat, activePickupLng), L.latLng(activeDropLat, activeDropLng)],
                            function (leg2Coords) {

                                animateMarker(leg2Coords, function () {

                                    // Arrived at drop — complete trip
                                    hideBanner();
                                    completeTripOnServer();
                                });
                            }
                        );
                    }, 3500);
                });
            }
        );
    }

    // ── Complete trip API call ────────────────────────────────────────────────
    function completeTripOnServer() {
        fetch('index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=complete_trip&booking_id=' + activeBookingId
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            showCompletionOverlay();
        })
        .catch(function (err) {
            console.error('Complete trip error:', err);
            showCompletionOverlay(); // still show overlay even if network hiccup
        });
    }

    // ── Show completion overlay + redirect after 5s ───────────────────────────
    function showCompletionOverlay() {
        var overlay = document.getElementById('tripCompleteOverlay');
        overlay.classList.add('show');

        // Animate progress bar over 5 seconds
        setTimeout(function () {
            document.getElementById('redirectFill').style.width = '100%';
        }, 50);

        setTimeout(function () {
            window.location.href = 'rides.php';
        }, 5000);
    }

    // ── Map initialisation (called once GPS resolves) ─────────────────────────
    function initMap(dLat, dLng) {
        driverLat = dLat;
        driverLng = dLng;

        driverMark = L.marker([driverLat, driverLng], { icon: driverIcon })
            .addTo(map)
            .bindPopup('<b>Your Location</b>');

        if (activePickupLat !== null && activePickupLng !== null) {

            pMark = L.marker([activePickupLat, activePickupLng], { icon: pickupIcon })
                .addTo(map)
                .bindPopup('<b>Pickup Location</b>');

            if (activeDropLat !== null && activeDropLng !== null) {
                dMark = L.marker([activeDropLat, activeDropLng], { icon: dropIcon })
                    .addTo(map)
                    .bindPopup('<b>Drop Location</b>');
            }

            // Show initial route depending on trip status
            if (tripStatusNow === 'Started') {
                drawRoute([
                    L.latLng(activePickupLat, activePickupLng),
                    L.latLng(activeDropLat, activeDropLng)
                ], null);
            } else {
                setTimeout(function () {
                    drawRoute([
                        L.latLng(driverLat, driverLng),
                        L.latLng(activePickupLat, activePickupLng)
                    ], null);
                }, 500);
            }

            document.getElementById('rideControls').classList.remove('hidden');

        } else {
            map.setView([driverLat, driverLng], 14);
            driverMark.bindPopup('<b>Your Location (Waiting for rides)</b>').openPopup();
        }

        setTimeout(function () { map.invalidateSize(); }, 500);
    }

    // ── Get GPS ───────────────────────────────────────────────────────────────
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (pos) { initMap(pos.coords.latitude, pos.coords.longitude); },
            function ()    { initMap(21.1702, 72.8311); },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    } else {
        initMap(21.1702, 72.8311);
    }

    // ── Navigate Button ───────────────────────────────────────────────────────
    document.getElementById('navigateBtn').addEventListener('click', function () {
        var destLat = (tripStatusNow === 'Started' && activeDropLat !== null) ? activeDropLat : activePickupLat;
        var destLng = (tripStatusNow === 'Started' && activeDropLng !== null) ? activeDropLng : activePickupLng;
        if (destLat !== null && destLng !== null) {
            window.open(
                'https://www.google.com/maps/dir/?api=1&destination=' + destLat + ',' + destLng,
                '_blank'
            );
        }
    });

    // ── Start Ride Button ─────────────────────────────────────────────────────
    var startRideBtn = document.getElementById('startRideBtn');
    if (startRideBtn) {
        startRideBtn.addEventListener('click', function () {

            if (!driverLat || !driverLng) {
                alert('Driver location not ready yet. Please wait a moment.');
                return;
            }

            var self = this;
            self.disabled = true;
            self.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting…';

            fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=start_trip&booking_id=' + activeBookingId
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    alert('Failed to start ride: ' + (data.msg || 'Unknown error'));
                    self.disabled = false;
                    self.innerHTML = '<i class="fas fa-play"></i> Start Ride';
                    return;
                }

                // Hide the start button — no longer needed
                self.style.display = 'none';
                tripStatusNow = 'Started';

                startAnimationFlow();
            })
            .catch(function (err) {
                console.error('Start ride error:', err);
                alert('Network error. Please try again.');
                self.disabled = false;
                self.innerHTML = '<i class="fas fa-play"></i> Start Ride';
            });
        });
    }

    setTimeout(function () { map.invalidateSize(); }, 800);
});
</script>
</body>
</html>