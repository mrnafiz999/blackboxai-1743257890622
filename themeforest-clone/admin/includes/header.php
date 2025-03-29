<?php
// Admin-specific header that includes the main header
require_once '../../includes/header.php';

// Check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../public/login.php");
    exit();
}
?>

<!-- Admin-specific header content -->
<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">ThemeForest Clone Admin</a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
            data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
            <a class="nav-link" href="../../public/logout.php">
                <i class="fas fa-sign-out-alt me-1"></i>Sign out
            </a>
        </li>
    </ul>
</nav>

<!-- Include admin CSS -->
<link href="css/admin.css" rel="stylesheet">

<!-- Admin sidebar toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar collapse
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.querySelector('.navbar-toggler');
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
    });
    
    // Active link highlighting
    const currentPage = location.pathname.split('/').pop();
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });
});
</script>