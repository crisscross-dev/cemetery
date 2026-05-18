const SVG_WIDTH = 1498;
const SVG_HEIGHT = 1190;
const DEFAULT_GPS_ACCURACY_WARNING_METERS = 20;

function showGpsWarning(message) {
    const warning = document.getElementById("gpsWarning");
    if (!warning) return;

    warning.textContent = message;
    warning.hidden = false;
}

function hideGpsWarning() {
    const warning = document.getElementById("gpsWarning");
    if (!warning) return;

    warning.hidden = true;
    warning.textContent = "";
}

function haversineMeters(a, b) {
    const earthRadius = 6371000;
    const toRadians = (value) => (value * Math.PI) / 180;
    const latDelta = toRadians(b.lat - a.lat);
    const lngDelta = toRadians(b.lng - a.lng);
    const lat1 = toRadians(a.lat);
    const lat2 = toRadians(b.lat);

    const value =
        Math.sin(latDelta / 2) * Math.sin(latDelta / 2) +
        Math.cos(lat1) *
            Math.cos(lat2) *
            Math.sin(lngDelta / 2) *
            Math.sin(lngDelta / 2);

    return earthRadius * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
}

function hasCalibration(calibration) {
    return Boolean(calibration && calibration.configured && calibration.topLeft && calibration.bottomRight);
}

function gpsToSvgPoint(latitude, longitude, calibration) {
    if (!hasCalibration(calibration)) {
        return null;
    }

    const topLeft = calibration.topLeft;
    const bottomRight = calibration.bottomRight;
    const lngRange = bottomRight.lng - topLeft.lng;
    const latRange = topLeft.lat - bottomRight.lat;

    if (!lngRange || !latRange) return null;

    return {
        x: topLeft.x + ((longitude - topLeft.lng) / lngRange) * (bottomRight.x - topLeft.x),
        y: topLeft.y + ((topLeft.lat - latitude) / latRange) * (bottomRight.y - topLeft.y),
    };
}

function estimateSvgUnitsPerMeter(calibration) {
    if (!hasCalibration(calibration)) {
        return 1;
    }

    const gpsDistance = haversineMeters(calibration.topLeft, calibration.bottomRight);
    const svgDistance = Math.hypot(
        calibration.bottomRight.x - calibration.topLeft.x,
        calibration.bottomRight.y - calibration.topLeft.y
    );

    return gpsDistance > 0 ? svgDistance / gpsDistance : 1;
}

function prepareLeafletContainer(containerSelector) {
    const originalSvg = document.getElementById("houseGraveSVG");
    const mapContainer = document.querySelector(containerSelector);

    if (!originalSvg || !mapContainer) {
        throw new Error("Cemetery SVG or map container was not found.");
    }

    const svgElement = originalSvg.cloneNode(true);
    mapContainer.innerHTML = "";

    const leafletElement = document.createElement("div");
    leafletElement.id = "cemeteryLeafletMap";
    leafletElement.className = "cemetery-leaflet-map";

    const warning = document.createElement("div");
    warning.id = "gpsWarning";
    warning.className = "gps-warning";
    warning.hidden = true;

    mapContainer.appendChild(leafletElement);
    mapContainer.appendChild(warning);

    return { leafletElement, svgElement };
}

export function createCemeteryLeafletMap({
    onGraveClick,
    onMapClick,
    autoStartGps = true,
    showLocateControl = true,
    showMissingCalibrationWarning = true,
    containerSelector = ".map-container",
} = {}) {
    if (!window.L) {
        throw new Error("Leaflet is not loaded. Check the Leaflet CDN script in the Blade view.");
    }

    const { leafletElement, svgElement } = prepareLeafletContainer(containerSelector);
    const bounds = [
        [0, 0],
        [SVG_HEIGHT, SVG_WIDTH],
    ];

    const map = L.map(leafletElement, {
        crs: L.CRS.Simple,
        minZoom: -2,
        maxZoom: 5,
        zoomSnap: 0.25,
        wheelPxPerZoomLevel: 80,
        attributionControl: false,
    });

    L.svgOverlay(svgElement, bounds, {
        interactive: true,
    }).addTo(map);

    map.fitBounds(bounds);
    map.setMaxBounds(bounds);

    let graves = [];
    let userMarker = null;
    let accuracyCircle = null;
    let lastPosition = null;
    let watchId = null;
    let calibration = null;
    let svgUnitsPerMeter = 1;
    let accuracyWarningMeters = DEFAULT_GPS_ACCURACY_WARNING_METERS;

    async function loadGraves() {
        const timestamp = Date.now();
        const response = await fetch(`/graves/statuses?_=${timestamp}`, {
            cache: "no-cache",
            headers: {
                "Cache-Control": "no-cache",
                Pragma: "no-cache",
            },
        });

        if (!response.ok) {
            throw new Error("Failed to load grave statuses.");
        }

        graves = await response.json();
        updateGraveBoxes();
        return graves;
    }

    async function loadCalibration() {
        const response = await fetch(`/cemetery-map/calibration?_=${Date.now()}`, {
            cache: "no-cache",
            headers: {
                "Cache-Control": "no-cache",
                Pragma: "no-cache",
            },
        });

        if (!response.ok) {
            throw new Error("Failed to load cemetery map calibration.");
        }

        const data = await response.json();
        calibration = {
            configured: data.configured === true,
            topLeft: data.top_left,
            bottomRight: data.bottom_right,
        };
        accuracyWarningMeters =
            data.accuracy_warning_meters || DEFAULT_GPS_ACCURACY_WARNING_METERS;
        svgUnitsPerMeter = estimateSvgUnitsPerMeter(calibration);

        if (!hasCalibration(calibration) && showMissingCalibrationWarning) {
            showGpsWarning("GPS is active, but cemetery map calibration points are missing.");
        }

        return calibration;
    }

    function updateGraveBoxes() {
        const graveBoxes = [...svgElement.querySelectorAll(".grave-box")];
        const graveByNumber = new Map(graves.map((grave) => [grave.grave_number, grave]));
        const indexOffset = graveBoxes.length === graves.length + 1 ? 1 : 0;

        graveBoxes.forEach((box, index) => {
            const existingNumber = box.getAttribute("data-grave-number");
            const existingId = box.getAttribute("data-grave-id");
            let grave = existingNumber ? graveByNumber.get(existingNumber) : null;

            if (!grave && existingId) {
                grave = graves.find((item) => String(item.id) === String(existingId));
            }

            if (!grave) {
                grave = graves[index - indexOffset];
            }

            if (!grave) return;

            box.setAttribute("data-status", grave.status);
            box.setAttribute("data-grave-id", grave.id);
            box.setAttribute("data-grave-number", grave.grave_number);
            box.classList.add("grave-clickable");
        });
    }

    function startGpsTracking({ centerOnUser = false } = {}) {
        if (!navigator.geolocation) {
            showGpsWarning("GPS is not supported by this browser.");
            return;
        }

        if (!window.isSecureContext) {
            showGpsWarning("GPS works reliably only on HTTPS or localhost.");
        }

        if (!hasCalibration(calibration) && showMissingCalibrationWarning) {
            showGpsWarning("GPS is active, but cemetery map calibration points are missing.");
        }

        if (watchId !== null) {
            if (centerOnUser && userMarker) map.setView(userMarker.getLatLng(), 1);
            return;
        }

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                lastPosition = position;
                const accuracy = position.coords.accuracy;
                const point = gpsToSvgPoint(
                    position.coords.latitude,
                    position.coords.longitude,
                    calibration
                );

                if (!point) return;

                const latLng = L.latLng(point.y, point.x);
                const radius = Math.max(8, accuracy * svgUnitsPerMeter);

                if (!userMarker) {
                    userMarker = L.circleMarker(latLng, {
                        radius: 8,
                        color: "#ffffff",
                        weight: 2,
                        fillColor: "#2563eb",
                        fillOpacity: 1,
                    }).addTo(map);

                    accuracyCircle = L.circle(latLng, {
                        radius,
                        color: "#2563eb",
                        weight: 1,
                        fillColor: "#3b82f6",
                        fillOpacity: 0.15,
                    }).addTo(map);
                } else {
                    userMarker.setLatLng(latLng);
                    accuracyCircle.setLatLng(latLng);
                    accuracyCircle.setRadius(radius);
                }

                if (centerOnUser) {
                    map.setView(latLng, Math.max(map.getZoom(), 1));
                }

                if (accuracy > accuracyWarningMeters) {
                    showGpsWarning(`GPS accuracy is low: about ${Math.round(accuracy)} meters. Use it as a nearby guide, not exact grave-level positioning.`);
                } else {
                    hideGpsWarning();
                }
            },
            (error) => {
                if (error.code === error.PERMISSION_DENIED) {
                    showGpsWarning("Location permission was denied.");
                    return;
                }

                showGpsWarning("Unable to read GPS location. Please try again outside or check browser location settings.");
            },
            {
                enableHighAccuracy: true,
                maximumAge: 5000,
                timeout: 15000,
            }
        );
    }

    function addLocateControl() {
        const locateControl = L.control({ position: "topleft" });

        locateControl.onAdd = function () {
            const button = L.DomUtil.create("button", "leaflet-control locate-me-btn");
            button.type = "button";
            button.textContent = "Locate Me";
            button.title = "Show current GPS location";

            L.DomEvent.disableClickPropagation(button);
            L.DomEvent.on(button, "click", () => {
                startGpsTracking({ centerOnUser: true });
            });

            return button;
        };

        locateControl.addTo(map);
    }

    function highlightGrave(graveId) {
        const graveBox = svgElement.querySelector(`.grave-box[data-grave-id="${graveId}"]`);
        if (!graveBox) return;

        const existingMarker = svgElement.querySelector("#graveMarker");
        if (existingMarker) existingMarker.remove();

        const bbox = graveBox.getBBox();
        const marker = document.createElementNS("http://www.w3.org/2000/svg", "circle");
        marker.setAttribute("id", "graveMarker");
        marker.setAttribute("cx", bbox.x + bbox.width / 2);
        marker.setAttribute("cy", bbox.y + bbox.height / 2);
        marker.setAttribute("r", "12");
        marker.setAttribute("fill", "none");
        marker.setAttribute("stroke", "#2563eb");
        marker.setAttribute("stroke-width", "4");
        marker.setAttribute("pointer-events", "none");
        svgElement.appendChild(marker);

        const latLng = L.latLng(bbox.y + bbox.height / 2, bbox.x + bbox.width / 2);
        map.setView(latLng, Math.max(map.getZoom(), 1));
    }

    function showTestMarker(point) {
        if (!point || Number.isNaN(point.x) || Number.isNaN(point.y)) return;

        const latLng = L.latLng(point.y, point.x);
        let marker = map._cemeteryTestMarker;

        if (!marker) {
            marker = L.circleMarker(latLng, {
                radius: 10,
                color: "#111827",
                weight: 2,
                fillColor: "#f97316",
                fillOpacity: 1,
            }).addTo(map);
            map._cemeteryTestMarker = marker;
        } else {
            marker.setLatLng(latLng);
        }

        map.setView(latLng, Math.max(map.getZoom(), 1));
    }

    let clickStartTime = 0;
    let clickStartPos = { x: 0, y: 0 };

    svgElement.addEventListener("pointerdown", (event) => {
        clickStartTime = Date.now();
        clickStartPos = { x: event.clientX, y: event.clientY };
    });

    svgElement.addEventListener("click", (event) => {
        const clickedElement = event.target.closest(".grave-box");
        if (!clickedElement) return;

        const distance = Math.hypot(
            event.clientX - clickStartPos.x,
            event.clientY - clickStartPos.y
        );
        const wasTap = Date.now() - clickStartTime < 350 && distance < 12;
        if (!wasTap) return;

        const graveId = Number(clickedElement.getAttribute("data-grave-id"));
        if (graveId && onGraveClick) {
            onGraveClick(graveId);
        }
    });

    if (onMapClick) {
        map.on("click", (event) => {
            onMapClick({
                svgX: event.latlng.lng,
                svgY: event.latlng.lat,
                event,
            });
        });
    }

    if (showLocateControl) addLocateControl();
    loadCalibration()
        .catch((error) => {
            console.error(error);
            if (showMissingCalibrationWarning) {
                showGpsWarning("Calibration data could not be loaded.");
            }
        })
        .finally(() => {
            if (autoStartGps) startGpsTracking();
        });

    return {
        map,
        svgElement,
        loadCalibration,
        loadGraves,
        getGraves: () => graves,
        getLastPosition: () => lastPosition,
        highlightGrave,
        showTestMarker,
    };
}
