<style>
    .sidebar {
        display: flex;
        flex-direction: column;
        height: calc(100dvh - 50px);
        justify-content: space-between;
    }

    nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem;
    }

    .lout {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem;
    }

    .sidebar nav a,
    .sidebar div a {
        text-decoration: none;
        background-color: #81c784;
        padding: 10px 15px;
        border-radius: 10px;
        color: #1e4620;
        font-weight: 600;
        transition: all 0.3s;
    }

    .sidebar a:hover {
        background-color: #66bb6a;
        transform: translateX(5px);
    }

    .logout {
        background-color: #d32f2f;
        color: white;
    }

    .logout:hover {
        background-color: #c62828;
        transform: translateX(0);
    }

    /* Logout confirmation modal styles */
    .logout-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        animation: fadeIn 0.3s ease;
    }

    .logout-modal-overlay.active {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .logout-modal {
        background: white;
        border-radius: 15px;
        padding: 30px;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: slideDown 0.3s ease;
        text-align: center;
    }

    .logout-modal-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #ff6b6b, #d32f2f);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 30px;
        color: white;
    }

    .logout-modal h3 {
        color: #1e4620;
        margin-bottom: 10px;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .logout-modal p {
        color: #666;
        margin-bottom: 25px;
        font-size: 1rem;
        line-height: 1.5;
    }

    .logout-modal-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .logout-modal-btn {
        padding: 12px 30px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        font-family: inherit;
    }

    .logout-modal-btn.cancel {
        background-color: #e0e0e0;
        color: #333;
    }

    .logout-modal-btn.cancel:hover {
        background-color: #d0d0d0;
        transform: translateY(-2px);
    }

    .logout-modal-btn.confirm {
        background: linear-gradient(135deg, #d32f2f, #c62828);
        color: white;
    }

    .logout-modal-btn.confirm:hover {
        background: linear-gradient(135deg, #c62828, #b71c1c);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(211, 47, 47, 0.4);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @media (max-width: 480px) {
        .logout-modal {
            padding: 20px;
        }

        .logout-modal h3 {
            font-size: 1.2rem;
        }

        .logout-modal p {
            font-size: 0.9rem;
        }

        .logout-modal-buttons {
            flex-direction: column;
        }

        .logout-modal-btn {
            width: 100%;
        }
    }
</style>

<!-- Logout Confirmation Modal - Moved outside sidebar for full screen coverage -->
<div class="logout-modal-overlay" id="logoutModal">
    <div class="logout-modal">
        <div class="logout-modal-icon">
            ⚠️
        </div>
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to logout? You will need to login again to access the admin panel.</p>
        <div class="logout-modal-buttons">
            <button type="button" class="logout-modal-btn cancel" onclick="closeLogoutModal()">Cancel</button>
            <button type="button" class="logout-modal-btn confirm" onclick="proceedLogout()">Yes, Logout</button>
        </div>
    </div>
</div>

<div class="sidebar">
    <nav>
        <a href="{{ route('admin.map') }}">Cemetery Map</a>
        <a href="{{ route('admin.map-calibration') }}">Map Calibration</a>
        <a href="#">Grave Records</a>
        <a href="#">Reports</a>
        <a href="#">Settings</a>
    </nav>
    <div class="lout">
        <form method="POST" action="{{ route('admin.logout') }}" id="logoutForm" style="margin: 0;">
            @csrf
            <button type="button" onclick="confirmLogout()" style="width: 100%; text-align: left; border: none; cursor: pointer; text-decoration: none; background-color: #d32f2f; padding: 10px 15px; border-radius: 10px; color: white; font-weight: 600; transition: all 0.3s; font-family: inherit; font-size: inherit;">Logout</button>
        </form>
    </div>
</div>

<script>
    function confirmLogout() {
        document.getElementById('logoutModal').classList.add('active');
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').classList.remove('active');
    }

    function proceedLogout() {
        document.getElementById('logoutForm').submit();
    }

    // Close modal when clicking outside
    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogoutModal();
        }
    });
</script>
