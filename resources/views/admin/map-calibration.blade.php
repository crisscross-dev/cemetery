<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Map Calibration</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @vite([
        'resources/js/map/calibration.js',
        'resources/css/map/calibration.css',
    ])
</head>

<body class="calibration-body-root">
    <div class="calibration-shell">
        <header class="calibration-header">
            <button class="calibration-menu-button" id="calibrationMenuButton" type="button" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <h1>Map Calibration</h1>
        </header>

        <div class="calibration-body">
            <aside class="calibration-sidebar" id="calibrationSidebar">
                @include('layouts.sidebar')
            </aside>

            <main class="calibration-main">
                <div class="calibration-toolbar">
                    <div class="calibration-point-readout">
                        <strong>Clicked SVG Point</strong>
                        <span>SVG X: <output id="clickedSvgX">-</output></span>
                        <span>SVG Y: <output id="clickedSvgY">-</output></span>
                    </div>
                    <div class="calibration-actions">
                        <button type="button" id="useTopLeftPoint">Use as Top-left SVG Point</button>
                        <button type="button" id="useBottomRightPoint">Use as Bottom-right SVG Point</button>
                    </div>
                </div>

                <div id="calibrationMapContainer" class="calibration-map-container">
                    <svg id="houseGraveSVG" width="1498" height="1190" viewBox="0 0 1498 1190" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            </main>

            <aside class="calibration-panel">
                <div id="calibrationWarning" class="calibration-warning" hidden></div>
                <div id="calibrationSuccess" class="calibration-success" hidden></div>

                <form id="calibrationForm">
                    @csrf
                    <section class="calibration-section">
                        <h2>Top-left Anchor Point</h2>
                        <label>Real Latitude
                            <input type="number" step="0.00000001" name="top_left_lat" id="topLeftLat" value="{{ old('top_left_lat', optional($calibration)->top_left_lat) }}" required>
                        </label>
                        <label>Real Longitude
                            <input type="number" step="0.00000001" name="top_left_lng" id="topLeftLng" value="{{ old('top_left_lng', optional($calibration)->top_left_lng) }}" required>
                        </label>
                        <label>SVG X
                            <input type="number" step="0.01" name="top_left_svg_x" id="topLeftSvgX" value="{{ old('top_left_svg_x', optional($calibration)->top_left_svg_x) }}" required>
                        </label>
                        <label>SVG Y
                            <input type="number" step="0.01" name="top_left_svg_y" id="topLeftSvgY" value="{{ old('top_left_svg_y', optional($calibration)->top_left_svg_y) }}" required>
                        </label>
                    </section>

                    <section class="calibration-section">
                        <h2>Bottom-right Anchor Point</h2>
                        <label>Real Latitude
                            <input type="number" step="0.00000001" name="bottom_right_lat" id="bottomRightLat" value="{{ old('bottom_right_lat', optional($calibration)->bottom_right_lat) }}" required>
                        </label>
                        <label>Real Longitude
                            <input type="number" step="0.00000001" name="bottom_right_lng" id="bottomRightLng" value="{{ old('bottom_right_lng', optional($calibration)->bottom_right_lng) }}" required>
                        </label>
                        <label>SVG X
                            <input type="number" step="0.01" name="bottom_right_svg_x" id="bottomRightSvgX" value="{{ old('bottom_right_svg_x', optional($calibration)->bottom_right_svg_x) }}" required>
                        </label>
                        <label>SVG Y
                            <input type="number" step="0.01" name="bottom_right_svg_y" id="bottomRightSvgY" value="{{ old('bottom_right_svg_y', optional($calibration)->bottom_right_svg_y) }}" required>
                        </label>
                    </section>

                    <div class="calibration-form-actions">
                        <button type="submit" class="calibration-save-btn">Save Calibration</button>
                        <button type="button" class="calibration-reset-btn" id="resetCalibration">Reset Calibration</button>
                    </div>
                </form>

                <section class="calibration-section">
                    <h2>Preview GPS Point</h2>
                    <label>Test Latitude
                        <input type="number" step="0.00000001" id="testLat">
                    </label>
                    <label>Test Longitude
                        <input type="number" step="0.00000001" id="testLng">
                    </label>
                    <button type="button" id="previewGpsPoint">Show Test Marker</button>
                    <p class="calibration-preview-output">
                        SVG X: <output id="previewSvgX">-</output><br>
                        SVG Y: <output id="previewSvgY">-</output>
                    </p>
                </section>
            </aside>
        </div>
    </div>

    <script>
        document.getElementById('calibrationMenuButton').addEventListener('click', function() {
            document.getElementById('calibrationSidebar').classList.toggle('active');
        });
    </script>
</body>

</html>
