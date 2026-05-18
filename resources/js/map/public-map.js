import { Modal } from "bootstrap";
import { createCemeteryLeafletMap } from "./leaflet-cemetery";

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

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
    const modalElement = document.getElementById("graveModal");
    const graveModal = new Modal(modalElement);
    const searchModalElement = document.getElementById("searchModal");
    const searchModal = new Modal(searchModalElement);

    let cemeteryMap = null;
    let graves = [];

    function openGraveModal(graveId) {
        const grave = graves.find((item) => Number(item.id) === Number(graveId));
        if (!grave) return;

        document.getElementById("deceasedInfo").style.display = "none";
        document.getElementById("vacantMessage").style.display = "none";
        document.getElementById("reservedMessage").style.display = "none";

        if (grave.status === "occupied") {
            document.getElementById("deceasedInfo").style.display = "block";
            document.getElementById("graveNumber").textContent = grave.grave_number;
            document.getElementById("deceasedName").textContent = grave.deceased_name || "N/A";
            document.getElementById("dateOfBirth").textContent = formatDate(grave.date_of_birth);
            document.getElementById("dateOfDeath").textContent = formatDate(grave.date_of_death);

            const deceasedImage = document.getElementById("deceasedImage");
            const noImageText = document.getElementById("noImageText");

            if (grave.image_url) {
                deceasedImage.onerror = function () {
                    this.style.display = "none";
                    if (noImageText) noImageText.style.display = "block";
                };
                deceasedImage.src = grave.image_url;
                deceasedImage.style.display = "block";
                if (noImageText) noImageText.style.display = "none";
            } else {
                deceasedImage.style.display = "none";
                if (noImageText) noImageText.style.display = "block";
            }
        } else if (grave.status === "vacant") {
            document.getElementById("vacantMessage").style.display = "block";
            document.getElementById("graveNumberVacant").textContent = grave.grave_number;
        } else if (grave.status === "reserved") {
            document.getElementById("reservedMessage").style.display = "block";
            document.getElementById("graveNumberReserved").textContent = grave.grave_number;
        }

        graveModal.show();
    }

    function searchGraves(searchTerm) {
        const results = graves.filter((grave) => {
            return (
                grave.deceased_name &&
                grave.deceased_name.toLowerCase().includes(searchTerm.toLowerCase())
            );
        });

        displaySearchResults(results);
    }

    function displaySearchResults(results) {
        const searchResults = document.getElementById("searchResults");

        if (results.length === 0) {
            searchResults.innerHTML = '<p class="text-muted text-center">No results found.</p>';
        } else {
            searchResults.innerHTML =
                '<div class="list-group">' +
                results
                    .map(
                        (grave) => `
                    <button type="button" class="list-group-item list-group-item-action search-result-item" data-grave-id="${grave.id}">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">${grave.deceased_name}</h6>
                            <small class="text-muted">${grave.grave_number}</small>
                        </div>
                        <small>${formatDate(grave.date_of_birth)} - ${formatDate(grave.date_of_death)}</small>
                    </button>
                `
                    )
                    .join("") +
                "</div>";

            document.querySelectorAll(".search-result-item").forEach((item) => {
                item.addEventListener("click", function () {
                    const graveId = Number(this.getAttribute("data-grave-id"));
                    searchModal.hide();
                    cemeteryMap.highlightGrave(graveId);
                    setTimeout(() => openGraveModal(graveId), 250);
                });
            });
        }

        searchModal.show();
    }

    cemeteryMap = createCemeteryLeafletMap({
        onGraveClick: openGraveModal,
    });

    try {
        graves = await cemeteryMap.loadGraves();
    } catch (error) {
        console.error(error);
    }

    const debouncedSearch = debounce((searchTerm) => {
        if (searchTerm) searchGraves(searchTerm);
    }, 300);

    document.getElementById("searchBtn").addEventListener("click", () => {
        const searchTerm = document.getElementById("searchInput").value.trim();
        if (searchTerm) searchGraves(searchTerm);
    });

    document.getElementById("searchInput").addEventListener("input", (event) => {
        const searchTerm = event.target.value.trim();
        if (searchTerm.length >= 2) debouncedSearch(searchTerm);
    });

    document.getElementById("searchInput").addEventListener("keypress", (event) => {
        if (event.key === "Enter") {
            const searchTerm = event.target.value.trim();
            if (searchTerm) searchGraves(searchTerm);
        }
    });
});
