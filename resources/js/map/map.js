import { Modal, Toast } from "bootstrap";
import { createCemeteryLeafletMap } from "./leaflet-cemetery";

function formatDate(dateString) {
    if (!dateString) return "N/A";

    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
}

document.addEventListener("DOMContentLoaded", async function () {
    let graves = [];
    let currentGraveId = null;
    let cemeteryMap = null;

    const graveModal = new Modal(document.getElementById("graveModal"));
    const successToast = new Toast(document.getElementById("successToast"));
    const errorToast = new Toast(document.getElementById("errorToast"));

    function showSuccessToast(message) {
        document.getElementById("toastMessage").textContent = message;
        successToast.show();
    }

    function showErrorToast(message) {
        document.getElementById("errorMessage").textContent = message;
        errorToast.show();
    }

    function syncLocalGraves() {
        graves = cemeteryMap.getGraves();
    }

    async function reloadGraves() {
        graves = await cemeteryMap.loadGraves();
    }

    function openGraveModal(graveId) {
        const grave = graves.find((item) => Number(item.id) === Number(graveId));

        if (!grave) {
            alert("This grave is not yet registered in the database.");
            return;
        }

        currentGraveId = graveId;

        document.getElementById("graveNumber").textContent = grave.grave_number;
        document.getElementById("graveStatus").textContent =
            grave.status.charAt(0).toUpperCase() + grave.status.slice(1);
        document.getElementById("graveStatus").className = `badge bg-${
            grave.status === "vacant"
                ? "success"
                : grave.status === "occupied"
                ? "danger"
                : "warning"
        }`;

        document.getElementById("statusSelect").value = grave.status;

        if (grave.status === "occupied" && grave.deceased_name) {
            document.getElementById("deceasedInfo").style.display = "block";
            document.getElementById("deceasedName").textContent = grave.deceased_name || "N/A";
            document.getElementById("dateOfBirth").textContent = formatDate(grave.date_of_birth);
            document.getElementById("dateOfDeath").textContent = formatDate(grave.date_of_death);

            const deceasedImage = document.getElementById("deceasedImage");
            const noImageText = document.getElementById("noImageText");

            if (grave.image_url) {
                deceasedImage.onerror = function () {
                    this.style.display = "none";
                    noImageText.style.display = "block";
                };
                deceasedImage.src = grave.image_url;
                deceasedImage.style.display = "block";
                noImageText.style.display = "none";
            } else {
                deceasedImage.style.display = "none";
                noImageText.style.display = "block";
            }
        } else {
            document.getElementById("deceasedInfo").style.display = "none";
        }

        document.getElementById("deceasedNameInput").value = grave.deceased_name || "";
        document.getElementById("dateOfBirthInput").value = grave.date_of_birth
            ? grave.date_of_birth.split("T")[0]
            : "";
        document.getElementById("dateOfDeathInput").value = grave.date_of_death
            ? grave.date_of_death.split("T")[0]
            : "";
        document.getElementById("deceasedImageInput").value = "";

        const clearBtn = document.getElementById("clearDataBtn");
        clearBtn.style.display =
            grave.deceased_name || grave.date_of_birth || grave.date_of_death || grave.image_url
                ? "inline-block"
                : "none";

        toggleOccupiedFields();
        graveModal.show();
    }

    function toggleOccupiedFields() {
        const status = document.getElementById("statusSelect").value;
        document.getElementById("occupiedFields").style.display =
            status === "occupied" ? "block" : "none";
    }

    async function saveGraveData() {
        if (!currentGraveId) {
            showErrorToast("No grave selected.");
            return;
        }

        const status = document.getElementById("statusSelect").value;
        const formData = new FormData();
        formData.append("status", status);

        if (status === "occupied") {
            formData.append("deceased_name", document.getElementById("deceasedNameInput").value);
            formData.append("date_of_birth", document.getElementById("dateOfBirthInput").value);
            formData.append("date_of_death", document.getElementById("dateOfDeathInput").value);

            const imageInput = document.getElementById("deceasedImageInput");
            if (imageInput.files && imageInput.files[0]) {
                formData.append("image", imageInput.files[0]);
            }
        } else {
            formData.append("deceased_name", "");
            formData.append("date_of_birth", "");
            formData.append("date_of_death", "");
            formData.append("clear_image", "1");
        }

        try {
            const response = await fetch(`/graves/${currentGraveId}`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            if (!response.ok) {
                const errorData = await response.json();
                showErrorToast(errorData.message || "Failed to save grave data.");
                return;
            }

            const data = await response.json();
            await reloadGraves();
            graveModal.hide();
            currentGraveId = null;
            showSuccessToast(`Grave #${data.grave_number} saved successfully!`);
        } catch (error) {
            console.error(error);
            showErrorToast("Network error. Please check your connection and try again.");
        }
    }

    async function clearGraveData() {
        if (!currentGraveId) return;

        if (
            !confirm(
                "Are you sure you want to clear all data for this grave? This will set it to vacant."
            )
        ) {
            return;
        }

        const formData = new FormData();
        formData.append("status", "vacant");
        formData.append("deceased_name", "");
        formData.append("date_of_birth", "");
        formData.append("date_of_death", "");
        formData.append("clear_image", "1");

        try {
            const response = await fetch(`/graves/${currentGraveId}`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            if (!response.ok) {
                showErrorToast("Failed to clear grave data. Please try again.");
                return;
            }

            const data = await response.json();
            await reloadGraves();
            graveModal.hide();
            currentGraveId = null;
            showSuccessToast(`Grave #${data.grave_number} cleared successfully!`);
        } catch (error) {
            console.error(error);
            showErrorToast("Network error. Please check your connection and try again.");
        }
    }

    cemeteryMap = createCemeteryLeafletMap({
        onGraveClick: openGraveModal,
        containerSelector: "#adminMapContainer",
    });

    try {
        await reloadGraves();
        syncLocalGraves();
    } catch (error) {
        console.error(error);
        showErrorToast("Failed to load grave data.");
    }

    document.getElementById("statusSelect").addEventListener("change", toggleOccupiedFields);

    window.saveGraveData = saveGraveData;
    window.clearGraveData = clearGraveData;
});
