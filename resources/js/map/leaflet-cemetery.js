const SVG_WIDTH = 1498;
const SVG_HEIGHT = 1190;
const DEFAULT_GPS_ACCURACY_WARNING_METERS = 20;
const GPS_POSITION_OPTIONS = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0,
};

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

function showGpsDebug(message) {
    const debug = document.getElementById("gpsDebug");
    if (!debug) return;

    debug.textContent = message;
    debug.hidden = false;
}

function formatGpsPosition(position) {
    return [
        `Latitude: ${position.coords.latitude}`,
        `Longitude: ${position.coords.longitude}`,
        `Accuracy: ${Math.round(position.coords.accuracy)} meters`,
        `Timestamp: ${new Date(position.timestamp).toLocaleString()}`,
    ].join("\n");
}

function gpsErrorMessage(error) {
    if (error.code === error.PERMISSION_DENIED) {
        return "Location permission was denied.";
    }

    if (error.code === error.POSITION_UNAVAILABLE) {
        return "GPS position is unavailable. Please try again outside or check device location settings.";
    }

    if (error.code === error.TIMEOUT) {
        return "GPS request timed out. Please try again with a clearer sky view.";
    }

    return "Unable to read GPS location.";
}

function isLocalhost() {
    return ["localhost", "127.0.0.1", "::1"].includes(window.location.hostname);
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
    return Boolean(
        calibration &&
            (
                hasFourAnchorCalibration(calibration) ||
                (calibration.configured && calibration.topLeft && calibration.bottomRight)
            )
    );
}

function hasFourAnchorCalibration(calibration) {
    return Boolean(
        calibration &&
            calibration.anchors &&
            ["a", "b", "c", "d"].every((key) => {
                const anchor = calibration.anchors[key];
                return anchor &&
                    anchor.lat !== null &&
                    anchor.lng !== null &&
                    anchor.x !== null &&
                    anchor.y !== null;
            })
    );
}

function solveLinearSystem(matrix, vector) {
    const size = vector.length;
    const augmented = matrix.map((row, index) => [...row, vector[index]]);

    for (let column = 0; column < size; column += 1) {
        let pivotRow = column;

        for (let row = column + 1; row < size; row += 1) {
            if (Math.abs(augmented[row][column]) > Math.abs(augmented[pivotRow][column])) {
                pivotRow = row;
            }
        }

        if (Math.abs(augmented[pivotRow][column]) < 1e-12) {
            return null;
        }

        [augmented[column], augmented[pivotRow]] = [augmented[pivotRow], augmented[column]];

        const pivot = augmented[column][column];
        for (let col = column; col <= size; col += 1) {
            augmented[column][col] /= pivot;
        }

        for (let row = 0; row < size; row += 1) {
            if (row === column) continue;
            const factor = augmented[row][column];

            for (let col = column; col <= size; col += 1) {
                augmented[row][col] -= factor * augmented[column][col];
            }
        }
    }

    return augmented.map((row) => row[size]);
}

function computeHomography(anchors) {
    const points = ["a", "b", "c", "d"].map((key) => anchors[key]);
    const matrix = [];
    const vector = [];

    points.forEach((point) => {
        const sourceX = point.lng;
        const sourceY = point.lat;
        const targetX = point.x;
        const targetY = point.y;

        matrix.push([sourceX, sourceY, 1, 0, 0, 0, -targetX * sourceX, -targetX * sourceY]);
        vector.push(targetX);

        matrix.push([0, 0, 0, sourceX, sourceY, 1, -targetY * sourceX, -targetY * sourceY]);
        vector.push(targetY);
    });

    const solution = solveLinearSystem(matrix, vector);
    if (!solution) return null;

    return [
        [solution[0], solution[1], solution[2]],
        [solution[3], solution[4], solution[5]],
        [solution[6], solution[7], 1],
    ];
}

function applyHomography(latitude, longitude, homography) {
    const sourceX = longitude;
    const sourceY = latitude;
    const denominator =
        homography[2][0] * sourceX +
        homography[2][1] * sourceY +
        homography[2][2];

    if (Math.abs(denominator) < 1e-12) return null;

    return {
        x:
            (homography[0][0] * sourceX +
                homography[0][1] * sourceY +
                homography[0][2]) /
            denominator,
        y:
            (homography[1][0] * sourceX +
                homography[1][1] * sourceY +
                homography[1][2]) /
            denominator,
    };
}

function gpsToSvgPoint(latitude, longitude, calibration) {
    if (!hasCalibration(calibration)) {
        return null;
    }

    if (hasFourAnchorCalibration(calibration)) {
        const homography = calibration.homography || computeHomography(calibration.anchors);
        if (!homography) return null;

        return applyHomography(latitude, longitude, homography);
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

    const debug = document.createElement("pre");
    debug.id = "gpsDebug";
    debug.className = "gps-debug";
    debug.hidden = true;

    mapContainer.appendChild(leafletElement);
    mapContainer.appendChild(warning);
    mapContainer.appendChild(debug);

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
    let locateButton = null;

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
            anchorCount: data.anchor_count || 0,
            anchors: data.anchors || null,
            topLeft: data.top_left,
            bottomRight: data.bottom_right,
        };
        calibration.homography = hasFourAnchorCalibration(calibration)
            ? computeHomography(calibration.anchors)
            : null;
        accuracyWarningMeters =
            data.accuracy_warning_meters || DEFAULT_GPS_ACCURACY_WARNING_METERS;
        svgUnitsPerMeter = estimateSvgUnitsPerMeter(calibration);
        console.log("Cemetery GPS calibration status:", {
            configured: hasCalibration(calibration),
            calibration,
        });

        if (!hasCalibration(calibration) && showMissingCalibrationWarning) {
            showGpsWarning("GPS is active, but cemetery map calibration points are missing.");
        }

        return calibration;
    }

    function moveUserMarkerFromPosition(position, { centerOnUser = false } = {}) {
        const accuracy = position.coords.accuracy;

        if (!hasCalibration(calibration)) {
            console.log("GPS conversion skipped because calibration is missing.", {
                calibration,
            });
            return false;
        }

        const point = gpsToSvgPoint(
            position.coords.latitude,
            position.coords.longitude,
            calibration
        );

        if (!point) {
            console.log("GPS conversion failed.", { position, calibration });
            showGpsWarning("GPS is working, but calibration values could not convert this position.");
            return false;
        }

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

        return true;
    }

    function handleGpsSuccess(position, { centerOnUser = false, source = "watchPosition" } = {}) {
        lastPosition = position;
        if (typeof cemeteryApi?.onGpsSuccess === "function") {
            cemeteryApi.onGpsSuccess(position, { source });
        }
        console.log("GPS success payload:", {
            source,
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            timestamp: position.timestamp,
        });

        const rawGpsMessage = formatGpsPosition(position);
        const calibrated = moveUserMarkerFromPosition(position, { centerOnUser });

        if (!calibrated) {
            showGpsWarning("GPS is working, but map calibration is missing. User marker cannot be placed on SVG yet.");
            showGpsDebug(`${rawGpsMessage}\n\nGPS is working, but map calibration is missing. User marker cannot be placed on SVG yet.`);
            return;
        }

        if (position.coords.accuracy > accuracyWarningMeters) {
            showGpsWarning(`GPS accuracy is low: about ${Math.round(position.coords.accuracy)} meters. Use it as a nearby guide, not exact grave-level positioning.`);
        } else {
            hideGpsWarning();
        }

        showGpsDebug(rawGpsMessage);
    }

    function handleGpsError(error, { source = "watchPosition" } = {}) {
        console.log("GPS error payload:", {
            source,
            code: error.code,
            message: error.message,
            error,
        });
        showGpsWarning(gpsErrorMessage(error));
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
            console.log("GPS unavailable: browser does not support geolocation.");
            showGpsWarning("GPS is not supported by this browser.");
            return;
        }

        if (!window.isSecureContext && !isLocalhost()) {
            console.log("GPS insecure context warning.", {
                protocol: window.location.protocol,
                hostname: window.location.hostname,
            });
            showGpsWarning("GPS requires HTTPS or localhost in most browsers.");
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
                handleGpsSuccess(position, { centerOnUser, source: "watchPosition" });
            },
            (error) => {
                handleGpsError(error, { source: "watchPosition" });
            },
            GPS_POSITION_OPTIONS
        );
    }

    function requestCurrentGpsPosition(button) {
        console.log("Locate button clicked");

        if (!navigator.geolocation) {
            console.log("GPS request blocked: browser does not support geolocation.");
            showGpsWarning("GPS is not supported by this browser.");
            return;
        }

        if (!window.isSecureContext && !isLocalhost()) {
            console.log("GPS request blocked or likely to fail: page is not HTTPS or localhost.", {
                protocol: window.location.protocol,
                hostname: window.location.hostname,
            });
            showGpsWarning("GPS requires HTTPS or localhost in most browsers.");
        }

        console.log("GPS request started", GPS_POSITION_OPTIONS);
        console.log("Cemetery GPS calibration status:", {
            configured: hasCalibration(calibration),
            calibration,
        });

        showGpsWarning("Requesting GPS location...");
        showGpsDebug("Requesting GPS location...");

        if (button) {
            button.disabled = true;
            button.textContent = "Locating...";
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                handleGpsSuccess(position, {
                    centerOnUser: true,
                    source: "getCurrentPosition",
                });
                if (button) {
                    button.disabled = false;
                    button.textContent = "Locate Me";
                }
            },
            (error) => {
                handleGpsError(error, { source: "getCurrentPosition" });
                if (button) {
                    button.disabled = false;
                    button.textContent = "Locate Me";
                }
            },
            GPS_POSITION_OPTIONS
        );
    }

    function addLocateControl() {
        const locateControl = L.control({ position: "topleft" });

        locateControl.onAdd = function () {
            const button = L.DomUtil.create("button", "leaflet-control locate-me-btn");
            locateButton = button;
            button.type = "button";
            button.textContent = "Locate Me";
            button.title = "Show current GPS location";

            L.DomEvent.disableClickPropagation(button);
            L.DomEvent.on(button, "click", () => {
                requestCurrentGpsPosition(button);
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

    const cemeteryApi = {
        map,
        svgElement,
        loadCalibration,
        loadGraves,
        getGraves: () => graves,
        getLastPosition: () => lastPosition,
        highlightGrave,
        showTestMarker,
    };

    return cemeteryApi;
}
