<!-- admin_sidebar.php -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="../admin_side/index.php" class="logo">
            <div class="logo-icon">
                <i class="fa-solid fa-building"></i>
            </div>
            <div>
                <div class="logo-text">ITPH <span>Admin</span></div>
                <div class="logo-sub">Real Estate Management</div>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="nav-section">Main</div>
        
        <a href="../admin_side/index.php" class="nav-link <?= isActive('index.php') ?>">
            <i class="fa-solid fa-chart-line"></i>
            <span>Dashboard</span>
        </a>

        <a href="../admin_side/ad_account.php" class="nav-link <?= isActive('ad_account.php') ?>">
            <i class="fa-solid fa-user-cog"></i>
            <span>My Account</span>
        </a>

        <!-- Management Section -->
        <div class="nav-section">Management</div>

        <!-- Customers Dropdown -->
        <div class="nav-dropdown <?= isDropdownActive(['customer_ban.php', 'customer_appointments.php']) ?>">
            <button class="nav-link" onclick="toggleDropdown(this)">
                <i class="fa-solid fa-users"></i>
                <span>Manage Customers</span>
                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </button>
            <div class="dropdown-menu">
                <a href="../admin_side/customer_ban.php" class="dropdown-item <?= isActive('customer_ban.php') ?>">
                    Ban / Unban
                </a>
                <a href="../admin_side/customer_appointments.php" class="dropdown-item <?= isActive('customer_appointments.php') ?>">
                    Appointments History
                </a>
            </div>
        </div>

        <!-- Properties Dropdown -->
        <div class="nav-dropdown <?= isDropdownActive(['add-property.php', 'update_properties.php']) ?>">
            <button class="nav-link" onclick="toggleDropdown(this)">
                <i class="fa-solid fa-house"></i>
                <span>Properties</span>
                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </button>
            <div class="dropdown-menu">
                <a href="../backends/add-property.php" class="dropdown-item <?= isActive('add-property.php') ?>">
                    Add Property
                </a>
                <a href="../admin_side/update_properties.php" class="dropdown-item <?= isActive('update_properties.php') ?>">
                    Update Property
                </a>
            </div>
        </div>

        <a href="../admin_side/admin_blog_management.php" class="nav-link <?= isActive('admin_blog_management.php') ?>">
            <i class="fa-solid fa-newspaper"></i>
            <span>Blog & News</span>
        </a>

        <a href="../admin_side/transaction.php" class="nav-link <?= isActive('transaction.php') ?>">
            <i class="fa-solid fa-money-bill-transfer"></i>
            <span>Transactions</span>
        </a>

        <a href="../admin_side/admin_message.php" class="nav-link <?= isActive('admin_message.php') ?>">
            <i class="fa-solid fa-envelope"></i>
            <span>Messages</span>
        </a>

        <a href="../admin_side/manage_agent.php" class="nav-link <?= isActive('manage_agent.php') ?>">
            <i class="fa-solid fa-user-tie"></i>
            <span>Manage Agents</span>
        </a>

        <!-- System Section -->
        <div class="nav-section">System</div>

        <a href="../admin_side/audit_log.php" class="nav-link <?= isActive('audit_log.php') ?>">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Audit Log</span>
        </a>

        <a href="../admin_side/logout.php" class="nav-link logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<style>
    /* ===== SIDEBAR STYLES ===== */
    .sidebar {
        width: 260px;
        background: #1e293b;
        border-right: 1px solid #334155;
        position: fixed;
        height: 100vh;
        left: 0;
        top: 0;
        z-index: 100;
        overflow-y: auto;
        overflow-x: hidden;
        transition: transform 0.3s ease;
    }

    .sidebar-header {
        padding: 24px 20px;
        border-bottom: 1px solid #334155;
        position: sticky;
        top: 0;
        background: #1e293b;
        z-index: 10;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: inherit;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #3b82f6, #60a5fa);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
        flex-shrink: 0;
    }

    .logo-text {
        font-size: 18px;
        font-weight: 700;
        color: #f1f5f9;
        line-height: 1.2;
    }

    .logo-text span {
        color: #3b82f6;
    }

    .logo-sub {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Navigation */
    .sidebar-nav {
        padding: 16px 12px;
    }

    .nav-section {
        padding: 16px 16px 8px;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        margin-bottom: 4px;
        color: #94a3b8;
        text-decoration: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        background: none;
        width: 100%;
        cursor: pointer;
        font-family: inherit;
        text-align: left;
    }

    .nav-link:hover {
        background: rgba(59, 130, 246, 0.1);
        color: #f1f5f9;
    }

    .nav-link.active {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
    }

    .nav-link.logout {
        color: #ef4444;
    }

    .nav-link.logout:hover {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .nav-link i {
        width: 20px;
        text-align: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .nav-link i.dropdown-arrow {
        margin-left: auto;
        font-size: 12px;
        transition: transform 0.2s;
        width: auto;
    }

    /* Dropdown */
    .nav-dropdown {
        margin-bottom: 4px;
    }

    .nav-dropdown.open > .nav-link .dropdown-arrow {
        transform: rotate(180deg);
    }

    .nav-dropdown.open > .dropdown-menu {
        max-height: 200px;
        opacity: 1;
    }

    .dropdown-menu {
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: all 0.3s ease;
        padding-left: 48px;
    }

    .dropdown-item {
        display: block;
        padding: 10px 16px;
        color: #64748b;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        border-radius: 6px;
        transition: all 0.2s;
        margin-bottom: 2px;
    }

    .dropdown-item:hover {
        color: #f1f5f9;
        background: rgba(59, 130, 246, 0.05);
    }

    .dropdown-item.active {
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }

    /* Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 99;
        backdrop-filter: blur(4px);
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* Scrollbar */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 3px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.open {
            transform: translateX(0);
        }
    }

    @media (min-width: 769px) {
        .sidebar-overlay {
            display: none !important;
        }
    }
</style>

<script>
    // ===== DROPDOWN TOGGLE =====
    function toggleDropdown(button) {
        const dropdown = button.parentElement;
        const wasOpen = dropdown.classList.contains('open');
        
        // Close all other dropdowns
        document.querySelectorAll('.nav-dropdown.open').forEach(d => {
            if (d !== dropdown) d.classList.remove('open');
        });
        
        // Toggle current
        dropdown.classList.toggle('open', !wasOpen);
    }

    // ===== SIDEBAR TOGGLE (for mobile) =====
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    // ===== AUTO-OPEN ACTIVE DROPDOWN =====
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
            if (dropdown.querySelector('.dropdown-item.active')) {
                dropdown.classList.add('open');
            }
        });
    });
</script>

<?php
// ===== HELPER FUNCTIONS =====
function isActive($filename) {
    return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
}

function isDropdownActive($filenames) {
    foreach ($filenames as $filename) {
        if (basename($_SERVER['PHP_SELF']) === $filename) {
            return 'open';
        }
    }
    return '';
}
?>