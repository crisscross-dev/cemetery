import { createCemeteryLeafletMap } from "./leaflet-cemetery";

const ANCHORS = ["a", "b", "c", "d"];
const roundCoordinate = (value) => Math.round(Number(value) * 100) / 100;
const anchorLabel = (key) => key.toUpperCase();

function inputId(anchor, field) {
    const suffix = {
        lat: "Lat",
        lng: "Lng",
        x: "SvgX",
        y: "SvgY",
    }[field];

    return `anchor${anchorLabel(anchor)}${suffix}`;
}

function getNumberById(id) {
    const value = document.getElementById(id).value;
    return value === "" ? null : Number(value);
}

function getAnchorNumber(anchor, field) {
    return getNumberById(inputId(anchor, field));
}

function setAnchorValue(anchor, field, value, precision = 2) {
    document.getElementById(inputId(anchor, field)).value = Number(value).toFixed(precision);
}

function showMessage(id, message) {
    const element = document.getElementById(id);
    element.textContent = message;
    element.hidden = false;
}

function hideMessage(id) {
    const element = document.getElementById(id);
    element.textContent = "";
    element.hidden = true;
}

function readAnchors() {
    const anchors = {};

    ANCHORS.forEach((anchor) => {
        anchors[anchor] = {
            lat: getAnchorNumber(anchor, "lat"),
            lng: getAnchorNumber(anchor, "lng"),
            x: getAnchorNumber(anchor, "x"),
            y: getAnchorNumber(anchor, "y"),
        };
    });

    return anchors;
}

function anchorIsComplete(anchor) {
    return ["lat", "lng", "x", "y"].every((field) => {
        const value = anchor[field];
        return value !== null && !Number.isNaN(value);
    });
}

function configuredAnchorCount(anchors) {
    return Object.values(anchors).filter(anchorIsComplete).length;
}

function updateCompleteness() {
    const anchors = readAnchors();
    const count = configuredAnchorCount(anchors);
    document.getElementById("anchorCompleteness").textContent = `${count}/4 anchors configured`;

    ANCHORS.forEach((anchor) => {
        const card = document.querySelector(`[data-anchor-card="${anchor}"]`);
        if (card) card.classList.toggle("is-complete", anchorIsComplete(anchors[anchor]));
    });

    return { anchors, count };
}

function clearCalibrationForm() {
    ANCHORS.forEach((anchor) => {
        ["lat", "lng", "x", "y"].forEach((field) => {
            document.getElementById(inputId(anchor, field)).value = "";
        });
    });

    ["testLat", "testLng"].forEach((id) => {
        document.getElementById(id).value = "";
    });

    document.getElementById("previewSvgX").textContent = "-";
    document.getElementById("previewSvgY").textContent = "-";
    document.getElementById("previewGpsAccuracy").textContent = "-";
    updateCompleteness();
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

        if (Math.abs(augmented[pivotRow][column]) < 1e-12) return null;

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
    const points = ANCHORS.map((key) => anchors[key]);
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

function transformGpsToSvg(latitude, longitude, anchors) {
    if (configuredAnchorCount(anchors) !== 4) return null;

    const homography = computeHomography(anchors);
    if (!homography) return null;

    const denominator =
        homography[2][0] * longitude +
        homography[2][1] * latitude +
        homography[2][2];

    if (Math.abs(denominator) < 1e-12) return null;

    return {
        x:
            (homography[0][0] * longitude +
                homography[0][1] * latitude +
                homography[0][2]) /
            denominator,
        y:
            (homography[1][0] * longitude +
                homography[1][1] * latitude +
                homography[1][2]) /
            denominator,
    };
}

document.addEventListener("DOMContentLoaded", async () => {
    let lastClickedPoint = null;
    let lastGpsPosition = null;
    let anchorLayer = null;
    let guidePolygon = null;

    const cemeteryMap = createCemeteryLeafletMap({
        autoStartGps: false,
        showLocateControl: true,
        showMissingCalibrationWarning: true,
        containerSelector: "#calibrationMapContainer",
        onMapClick: ({ svgX, svgY }) => {
            lastClickedPoint = {
                x: roundCoordinate(svgX),
                y: roundCoordinate(svgY),
            };

            document.getElementById("clickedSvgX").textContent = lastClickedPoint.x.toFixed(2);
            document.getElementById("clickedSvgY").textContent = lastClickedPoint.y.toFixed(2);
        },
    });

    try {
        await cemeteryMap.loadGraves();
    } catch (error) {
        console.error(error);
    }

    function renderAnchorOverlay() {
        const anchors = readAnchors();
        const completeAnchors = ANCHORS
            .map((key) => ({ key, ...anchors[key] }))
            .filter(anchorIsComplete);

        if (!anchorLayer) {
            anchorLayer = L.layerGroup().addTo(cemeteryMap.map);
        }

        anchorLayer.clearLayers();

        completeAnchors.forEach((anchor) => {
            const latLng = L.latLng(anchor.y, anchor.x);
            L.circleMarker(latLng, {
                radius: 8,
                color: "#ffffff",
                weight: 2,
                fillColor: "#7c3aed",
                fillOpacity: 1,
            })
                .bindTooltip(`Anchor ${anchorLabel(anchor.key)}`, {
                    permanent: true,
                    direction: "top",
                    className: "anchor-tooltip",
                })
                .addTo(anchorLayer);
        });

        if (guidePolygon) {
            cemeteryMap.map.removeLayer(guidePolygon);
            guidePolygon = null;
        }

        if (completeAnchors.length >= 3) {
            guidePolygon = L.polygon(
                completeAnchors.map((anchor) => [anchor.y, anchor.x]),
                {
                    color: "#7c3aed",
                    weight: 2,
                    fillColor: "#7c3aed",
                    fillOpacity: 0.08,
                    dashArray: "6 6",
                }
            ).addTo(cemeteryMap.map);
        }
    }

    function refreshCalibrationState() {
        const state = updateCompleteness();
        renderAnchorOverlay();

        if (state.count < 4) {
            showMessage(
                "calibrationWarning",
                `${state.count}/4 anchors configured. Capture GPS, click the matching SVG landmark, then assign it to Anchor A-D.`
            );
        } else {
            hideMessage("calibrationWarning");
        }
    }

    refreshCalibrationState();

    cemeteryMap.onGpsSuccess = (position) => {
        lastGpsPosition = position;
        document.getElementById("capturedGpsSummary").textContent =
            `${position.coords.latitude.toFixed(8)}, ${position.coords.longitude.toFixed(8)} (${Math.round(position.coords.accuracy)}m)`;
    };

    document.querySelectorAll(".anchor-assign-btn").forEach((button) => {
        button.addEventListener("click", () => {
            const anchor = button.dataset.anchor;

            if (!lastClickedPoint) {
                showMessage("calibrationWarning", "Click the matching point on the SVG map first.");
                return;
            }

            if (!lastGpsPosition) {
                showMessage("calibrationWarning", "Click Locate Me first to capture the real GPS point.");
                return;
            }

            setAnchorValue(anchor, "lat", lastGpsPosition.coords.latitude, 8);
            setAnchorValue(anchor, "lng", lastGpsPosition.coords.longitude, 8);
            setAnchorValue(anchor, "x", lastClickedPoint.x, 2);
            setAnchorValue(anchor, "y", lastClickedPoint.y, 2);
            hideMessage("calibrationSuccess");
            refreshCalibrationState();
        });
    });

    document.querySelectorAll("#calibrationForm input").forEach((input) => {
        input.addEventListener("input", refreshCalibrationState);
    });

    document.getElementById("calibrationForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        hideMessage("calibrationWarning");
        hideMessage("calibrationSuccess");

        const state = updateCompleteness();

        if (state.count !== 4) {
            showMessage("calibrationWarning", "All four anchors are required before saving.");
            return;
        }

        const formData = new FormData(event.target);
        formData.append("_method", "PUT");

        try {
            const response = await fetch("/map-calibration", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    Accept: "application/json",
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                const errors = data.errors
                    ? Object.values(data.errors).flat().join(" ")
                    : data.message || "Unable to save calibration.";
                showMessage("calibrationWarning", errors);
                return;
            }

            showMessage("calibrationSuccess", data.message);
            refreshCalibrationState();
        } catch (error) {
            console.error(error);
            showMessage("calibrationWarning", "Network error while saving calibration.");
        }
    });

    document.getElementById("resetCalibration").addEventListener("click", async () => {
        hideMessage("calibrationWarning");
        hideMessage("calibrationSuccess");

        if (!confirm("Reset saved map calibration? This will not change grave records.")) {
            return;
        }

        const formData = new FormData();
        formData.append("_method", "DELETE");

        try {
            const response = await fetch("/map-calibration", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    Accept: "application/json",
                },
                body: formData,
            });

            const data = await response.json();

            if (!response.ok) {
                showMessage("calibrationWarning", data.message || "Unable to reset calibration.");
                return;
            }

            clearCalibrationForm();
            renderAnchorOverlay();
            showMessage("calibrationSuccess", data.message);
            refreshCalibrationState();
        } catch (error) {
            console.error(error);
            showMessage("calibrationWarning", "Network error while resetting calibration.");
        }
    });

    document.getElementById("useLastGpsForPreview").addEventListener("click", () => {
        if (!lastGpsPosition) {
            showMessage("calibrationWarning", "Click Locate Me first to capture GPS.");
            return;
        }

        document.getElementById("testLat").value = lastGpsPosition.coords.latitude.toFixed(8);
        document.getElementById("testLng").value = lastGpsPosition.coords.longitude.toFixed(8);
        document.getElementById("previewGpsAccuracy").textContent =
            `${Math.round(lastGpsPosition.coords.accuracy)} meters`;
    });

    document.getElementById("previewGpsPoint").addEventListener("click", () => {
        hideMessage("calibrationWarning");

        const anchors = readAnchors();
        const latitude = getNumberById("testLat");
        const longitude = getNumberById("testLng");

        if (configuredAnchorCount(anchors) !== 4) {
            showMessage("calibrationWarning", "Complete all four anchors before previewing.");
            return;
        }

        if (latitude === null || longitude === null || Number.isNaN(latitude) || Number.isNaN(longitude)) {
            showMessage("calibrationWarning", "Enter a test latitude and longitude.");
            return;
        }

        const point = transformGpsToSvg(latitude, longitude, anchors);

        if (!point) {
            showMessage("calibrationWarning", "Unable to transform GPS point. Check anchor shape and values.");
            return;
        }

        const rounded = {
            x: roundCoordinate(point.x),
            y: roundCoordinate(point.y),
        };

        document.getElementById("previewSvgX").textContent = rounded.x.toFixed(2);
        document.getElementById("previewSvgY").textContent = rounded.y.toFixed(2);
        cemeteryMap.showTestMarker(rounded);
    });
});
