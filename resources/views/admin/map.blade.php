@extends('layouts.app')

@section('header')
<h3>Cemetery Map Management</h3>
@endsection

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@vite(['resources/js/map/map.js', 'resources/css/map/map.css'])

<style>
    /* Mobile optimizations for admin map */
    @media (max-width: 768px) {
        .zoom-controls {
            right: 10px;
            top: 10px;
        }

        .zoom-btn {
            width: 44px !important;
            height: 44px !important;
            font-size: 1.2rem !important;
        }

        .zoom-level {
            padding: 8px 12px !important;
            font-size: 0.9rem !important;
        }

        .modal-dialog {
            margin: 0;
            max-width: 100%;
            height: 100vh;
        }

        .modal-content {
            height: 100%;
            border-radius: 0;
        }

        .modal-body {
            overflow-y: auto;
        }

        .form-control,
        .form-select {
            font-size: 16px !important;
            /* Prevent iOS zoom */
        }

        .btn {
            padding: 10px 16px;
            font-size: 1rem;
        }
    }

    /* Touch scrolling */
    .map-scroll {
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y;
    }

    .map-scroll * {
        user-select: none;
        -webkit-user-select: none;
    }
</style>

<div id="adminMapContainer" class="admin-map-container">
    <div class="zoom-controls">
        <button class="zoom-btn" id="zoomIn" title="Zoom In">+</button>
        <div class="zoom-level" id="zoomLevel">100%</div>
        <button class="zoom-btn" id="zoomOut" title="Zoom Out">−</button>
        <button class="zoom-btn" id="zoomReset" title="Reset">⟲</button>
    </div>

    <div class="map-scroll" id="mapScroll">
        <div class="map-content">
            <div class="svg-wrapper" id="svgWrapper">
                <svg id="houseGraveSVG" width="1498" height="1190" viewBox="0 0 1498 1190" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Cemetery outline/boundary paths -->
                    <g id="cemeterMap" opacity="0.7">
                        <path d="M325.15 569.166L624.119 227.779L698 162.409L1279.88 335.001L1267.18 384.299L1292.57 707.2L868 883.409L875 894.909L830 947.909L772 998.409L716 1044.91L655 1089.41L216.562 701.037L312.457 585.188" stroke="#2c5f2d" stroke-width="3" fill="#c8e6c9" fill-opacity="0.4" />
                        <path d="M310.5 584.909L330.274 595.217L354.765 596.279L534.5 596.279L385.5 763.909L393 770.409L417 742.909L446 711.409L492.5 659.409L537 607.409L635.5 650.409L654.5 644.909L666.5 633.409L703 586.409L714.5 577.409L723 580.909L982 797.909L999.5 814.409L1063.5 786.909L1224 711.909L1231 702.409L1207 532.909L1195 452.909L1189 385.409L1181 346.909L1175.5 335.909L1163.5 328.409L1124 319.909L1077 315.409L1053 313.909L1012 303.409L975.5 290.409L894.5 257.409L841.5 238.409L833.5 240.909L817.5 257.909L683.5 415.909L556.5 575.409L340.662 579.025L324 568.409" stroke="#1a3a1b" stroke-width="2" fill="#e8f5e9" fill-opacity="0.3" />
                        <path d="M833 258.409L637 490.409L743.5 579.409L987.5 306.909L933 286.409L888.5 267.909L856 253.409L845 250.909L833 258.409Z" stroke="#1a3a1b" stroke-width="1.5" fill="#c8e6c9" fill-opacity="0.4" />
                        <path d="M996.5 308.409L747.5 584.409L934.5 739.409L1184.5 441.909L1176.5 376.909L1164 346.909L1157.5 339.409L1142 333.909L1037.5 323.409L996.5 308.409Z" stroke="#1a3a1b" stroke-width="1.5" fill="#c8e6c9" fill-opacity="0.4" />
                        <path d="M1186.5 459.909L941.5 747.909L1003 803.409L1104.5 754.909L1200 711.409L1221 698.409V678.409L1186.5 459.909Z" stroke="#1a3a1b" stroke-width="1.5" fill="#c8e6c9" fill-opacity="0.4" />
                        <path d="M656.318 551.807L615.742 551.394L595.732 576.704L604.633 605.952L636.011 621.579L670.958 606.626L675.323 577.513L656.318 551.807Z" stroke="#115ab9ff" stroke-width="1.5" fill="#115ab9ff" fill-opacity="0.3" />
                    </g>

                    <g id="graveHouses">
                        @include('graves.gravehouse')
                        @include('graves.gravesetA')
                        @include('graves.gravesetB')
                        @include('graves.gravesetC')
                        @include('graves.gravesetD')
                        @include('graves.gravenearhouse')
                    </g>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Modal for grave details -->
<div class="modal fade" id="graveModal" tabindex="-1" aria-labelledby="graveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="graveModalLabel">Grave Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <p class="mb-2"><strong>Grave Number:</strong> <span id="graveNumber"></span></p>
                    <span class="badge bg-secondary" id="graveStatus"></span>
                </div>

                <div id="deceasedInfo" style="display: none;">
                    <div class="text-center mb-3">
                        <img id="deceasedImage" src="" alt="Deceased Photo" class="img-thumbnail mx-auto d-block" style="max-width: 100%; max-height: 300px; display: none;">
                        <p class="text-muted small mt-1" id="noImageText">No photo available</p>
                    </div>
                    <hr>
                    <p><strong>Name:</strong> <span id="deceasedName"></span></p>
                    <p><strong>Date of Birth:</strong> <span id="dateOfBirth"></span></p>
                    <p><strong>Date of Death:</strong> <span id="dateOfDeath"></span></p>
                </div>

                <hr>

                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Status:</label>
                    <select class="form-select" id="statusSelect">
                        <option value="vacant">Vacant</option>
                        <option value="occupied">Occupied</option>
                        <option value="reserved">Reserved</option>
                    </select>
                </div>

                <div id="occupiedFields" style="display: none;">
                    <div class="mb-3">
                        <label for="deceasedImageInput" class="form-label">Photo:</label>
                        <input type="file" class="form-control" id="deceasedImageInput" accept="image/*">
                        <small class="text-muted">Upload a photo of the deceased</small>
                    </div>

                    <div class="mb-3">
                        <label for="deceasedNameInput" class="form-label">Name:</label>
                        <input type="text" class="form-control" id="deceasedNameInput" placeholder="Enter full name">
                    </div>

                    <div class="mb-3">
                        <label for="dateOfBirthInput" class="form-label">Date of Birth:</label>
                        <input type="date" class="form-control" id="dateOfBirthInput">
                    </div>

                    <div class="mb-3">
                        <label for="dateOfDeathInput" class="form-label">Date of Death:</label>
                        <input type="date" class="form-control" id="dateOfDeathInput">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="clearDataBtn" onclick="clearGraveData()" style="display: none;">Clear Data</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveGraveData()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="toastMessage">Grave data saved successfully!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <span id="errorMessage">An error occurred.</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<style>
    .toast {
        min-width: 300px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .toast-body {
        font-size: 1rem;
        padding: 0.75rem;
    }

    /* Add icons using Unicode symbols if Bootstrap Icons not available */
    .toast-body i::before {
        font-style: normal;
    }

    .bg-success .toast-body i::before {
        content: "✓ ";
        font-size: 1.2rem;
    }

    .bg-danger .toast-body i::before {
        content: "⚠ ";
        font-size: 1.2rem;
    }
</style>
@endsection
