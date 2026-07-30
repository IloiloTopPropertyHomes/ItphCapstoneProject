<div class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div>
            <div class="brand-wordmark">IT<span>PH</span></div>
            <div class="brand-sub">Agent Panel</div>
        </div>
    </div>

    <div class="sidebar-nav">

        <div class="nav-item ">
            <a href="agent_dashboard.php">
                <i class="fa fa-chart-line nav-icon"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
    <a href="agent_account.php">
        <i class="fa fa-user-cog"></i> My Account
    </a>
</div>




   <div class="nav-item">
    <a href="my_transactions.php">
        <i class="fa fa-user-cog"></i> My Transactions
    </a>
</div>
    <div class="nav-item">
    <a href="upload_requirements.php">
        <span class="nav-icon">
            <i class="fas fa-file-upload"></i>
        </span>
        Upload Requirements
    </a>
</div>
       <div class="nav-item">
            <a href="#">
                <i class="fa fa-blog nav-icon"></i> Messages
            </a>
        </div>

        <div class="nav-item">
            <a href="logout.php">
                <i class="fa fa-sign-out nav-icon"></i> Logout
            </a>
        </div>

    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div><script>
function toggleDropdown() {
    document.getElementById("propertyMenu").classList.toggle("show");
}
</script>