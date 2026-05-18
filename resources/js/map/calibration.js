import { createCemeteryLeafletMap } from "./leaflet-cemetery";

const roundCoordinate = (value) => Math.round(Number(value) * 100) / 100;

function getNumber(id) {
    const value = document.getElementById(id).value;
    return value === "" ? null : Number(value);
}

function setValue(id, value) {
    document.getElementById(id).value = roundCoordinate(value).toFixed(2);
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

function readFormCalibration() {
    const calibration = {
        topLeft: {
            lat: getNumber("topLeftLat"),
            lng: getNumber("topLeftLng"),
            x: getNumber("topLeftSvgX"),
            y: getNumber("topLeftSvgY"),
        },
        bottomRight: {
            lat: getNumber("bottomRightLat"),
            lng: getNumber("bottomRightLng"),
            x: getNumber("bottomRightSvgX"),
            y: getNumber("bottomRightSvgY"),
        },
    };

    const values = [
        calibration.topLeft.lat,
        calibration.topLeft.lng,
        calibration.topLeft.x,
        calibration.topLeft.y,
        calibration.bottomRight.lat,
        calibration.bottomRight.lng,
        calibration.bottomRight.x,
        calibration.bottomRight.y,
    ];

    return {
        configured: values.every((value) => value !== null && !Number.isNaN(value)),
        ...calibration,
    };
}

function clearCalibrationForm() {
    [
        "topLeftLat",
        "topLeftLng",
        "topLeftSvgX",
        "topLeftSvgY",
        "bottomRightLat",
        "bottomRightLng",
        "bottomRightSvgX",
        "bottomRightSvgY",
        "testLat",
        "testLng",
    ].forEach((id) => {
        const element = document.getElementById(id);
        if (element) element.value = "";
    });

    document.getElementById("previewSvgX").textContent = "-";
    document.getElementById("previewSvgY").textContent = "-";
}

function gpsToSvgPoint(latitude, longitude, calibration) {
    if (!calibration.configured) return null;

    const lngRange = calibration.bottomRight.lng - calibration.topLeft.lng;
    const latRange = calibration.topLeft.lat - calibration.bottomRight.lat;

    if (!lngRange || !latRange) return null;

    return {
        x:
            calibration.topLeft.x +
            ((longitude - calibration.topLeft.lng) / lngRange) *
                (calibration.bottomRight.x - calibration.topLeft.x),
        y:
            calibration.topLeft.y +
            ((calibration.topLeft.lat - latitude) / latRange) *
                (calibration.bottomRight.y - calibration.topLeft.y),
    };
}

document.addEventListener("DOMContentLoaded", async () => {
    let lastClickedPoint = null;

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

    if (!readFormCalibration().configured) {
        showMessage(
            "calibrationWarning",
            "Calibration is incomplete. Click the map to fill SVG points, then enter real GPS latitude and longitude."
        );
    }

    document.getElementById("useTopLeftPoint").addEventListener("click", () => {
        if (!lastClickedPoint) {
            showMessage("calibrationWarning", "Click a point on the map first.");
            return;
        }

        setValue("topLeftSvgX", lastClickedPoint.x);
        setValue("topLeftSvgY", lastClickedPoint.y);
        hideMessage("calibrationSuccess");
    });

    document.getElementById("useBottomRightPoint").addEventListener("click", () => {
        if (!lastClickedPoint) {
            showMessage("calibrationWarning", "Click a point on the map first.");
            return;
        }

        setValue("bottomRightSvgX", lastClickedPoint.x);
        setValue("bottomRightSvgY", lastClickedPoint.y);
        hideMessage("calibrationSuccess");
    });

    document.getElementById("calibrationForm").addEventListener("submit", async (event) => {
        event.preventDefault();
        hideMessage("calibrationWarning");
        hideMessage("calibrationSuccess");

        const calibration = readFormCalibration();

        if (!calibration.configured) {
            showMessage("calibrationWarning", "All calibration fields are required.");
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
            showMessage("calibrationSuccess", data.message);
            showMessage(
                "calibrationWarning",
                "Calibration is incomplete. Click the map to fill SVG points, then enter real GPS latitude and longitude."
            );
        } catch (error) {
            console.error(error);
            showMessage("calibrationWarning", "Network error while resetting calibration.");
        }
    });

    document.getElementById("previewGpsPoint").addEventListener("click", () => {
        hideMessage("calibrationWarning");

        const calibration = readFormCalibration();
        const latitude = getNumber("testLat");
        const longitude = getNumber("testLng");

        if (!calibration.configured) {
            showMessage("calibrationWarning", "Save or complete both calibration anchors before previewing.");
            return;
        }

        if (latitude === null || longitude === null || Number.isNaN(latitude) || Number.isNaN(longitude)) {
            showMessage("calibrationWarning", "Enter a test latitude and longitude.");
            return;
        }

        const point = gpsToSvgPoint(latitude, longitude, calibration);

        if (!point) {
            showMessage("calibrationWarning", "Unable to convert GPS point. Check anchor values.");
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
