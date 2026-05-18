<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cemetery Map - Public View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite([
    'resources/js/map/public-map.js',
    'resources/css/map/map.css',
    'resources/css/index.css',
    ])

</head>

<body>
    <header class="public-header">
        <h1>St. John Memorial Garden</h1>
        <div class="header-controls">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search by name...">
                <button id="searchBtn">🔍</button>
            </div>
            <a href="{{ route('admin.login') }}" class="admin-link">Login</a>
        </div>
    </header>

    <div class="map-container">
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

    <!-- Read-only Modal for grave details -->
    <div class="modal fade" id="graveModal" tabindex="-1" aria-labelledby="graveModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" style="max-height: 95dvh; margin: 10.5dvh auto;">
            <div class="modal-content" style="height: auto;">
                <div class="modal-header" style="padding: 0.5rem 1rem;">
                    <h6 class="modal-title" id="graveModalLabel" style="margin: 0; font-size: 1rem;">Grave Information</h6>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="Close" style="padding: 0.25rem; font-size: 0.875rem;"></button>
                </div>
                <div class="modal-body" style="padding: 0.75rem;">
                    <div id="deceasedInfo" style="display: none;">
                        <div class="text-center mb-3">
                            <img id="deceasedImage" src="" alt="Deceased Photo" class="img-thumbnail mx-auto d-block" style="max-width: 100%; max-height: 250px; display: none;">
                            <p class="text-muted small mt-1" id="noImageText" style="display: none;">No photo available</p>
                        </div>
                        <hr>
                        <p><strong>Grave Number:</strong> <span id="graveNumber"></span></p>
                        <p><strong>Name:</strong> <span id="deceasedName"></span></p>
                        <p><strong>Date of Birth:</strong> <span id="dateOfBirth"></span></p>
                        <p><strong>Date of Death:</strong> <span id="dateOfDeath"></span></p>
                    </div>

                    <div id="vacantMessage" style="display: none;">
                        <div class="text-center mb-3">
                            <p class="mb-2"><strong>Grave Number:</strong> <span id="graveNumberVacant"></span></p>
                        </div>
                        <hr>
                        <p class="text-muted text-center mt-3">This plot is currently vacant.</p>
                    </div>

                    <div id="reservedMessage" style="display: none;">
                        <div class="text-center mb-3">
                            <p class="mb-2"><strong>Grave Number:</strong> <span id="graveNumberReserved"></span></p>
                        </div>
                        <hr>
                        <p class="text-warning text-center mt-3">This plot is reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results Modal -->
    <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-height: 90dvh; margin: 5dvh auto;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchModalLabel">Search Results</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="max-height: 60dvh; overflow-y: auto;">
                    <div id="searchResults"></div>
                </div>
                <div class="modal-footer" style="padding: 0.5rem 1rem;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
