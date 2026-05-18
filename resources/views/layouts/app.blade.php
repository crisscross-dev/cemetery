<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cemetery Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100dvh;
            background-color: #e8f5e9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .app-container {
            width: 100%;
            height: 100dvh;
            display: grid;
            grid-template-columns: 250px 1fr;
            grid-template-rows: 50px 1fr;
            grid-template-areas:
                "header header"
                "aside main";
        }

        header {
            grid-area: header;
            background: linear-gradient(to right, #2d5f2e 0%, #3a7d3c 100%);
            border-bottom: 3px solid #1e4620;
            display: grid;
            place-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
        }

        .hamburger {
            display: none;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            z-index: 1001;
        }

        .hamburger span {
            display: block;
            width: 25px;
            height: 3px;
            background-color: #ffffff;
            margin: 5px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }

        header h2,
        header h3 {
            color: #ffffff;
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        aside {
            grid-area: aside;
            background-color: #c8e6c9;
            border-right: 2px solid #a5d6a7;
            transition: transform 0.3s ease;
        }

        main {
            grid-area: main;
            overflow: auto;
        }

        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .app-container {
                grid-template-columns: 1fr;
                grid-template-rows: 50px 1fr;
                grid-template-areas:
                    "header"
                    "main";
            }

            .hamburger {
                display: block;
            }

            header h2,
            header h3 {
                font-size: 1.1rem;
            }

            aside {
                position: fixed;
                top: 50px;
                left: 0;
                width: 250px;
                height: calc(100vh - 50px);
                z-index: 1000;
                transform: translateX(-100%);
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
            }

            aside.active {
                transform: translateX(0);
            }

            main {
                overflow: auto;
                width: 100%;
                height: calc(100vh - 50px);
            }
        }

        @media (max-width: 480px) {

            header h2,
            header h3 {
                font-size: 1rem;
            }

            aside {
                width: 80%;
                max-width: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <header>
            <button class="hamburger" id="hamburger" onclick="toggleSidebar()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            @hasSection('header')
            @yield('header')
            @else
            <h2>Cemetery Management System</h2>
            @endif
        </header>
        <aside id="sidebar">
            @include('layouts.sidebar')
        </aside>
        <main>
            @yield('content')
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const hamburger = document.getElementById('hamburger');
            sidebar.classList.toggle('active');
            hamburger.classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const hamburger = document.getElementById('hamburger');
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnHamburger = hamburger.contains(event.target);

                if (!isClickInsideSidebar && !isClickOnHamburger && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            }
        });
    </script>
</body>

</html>