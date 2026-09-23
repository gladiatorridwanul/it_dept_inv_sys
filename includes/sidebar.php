<?php
// Sidebar navigation - Make sure logout points to root
?>
<div class="sidebar" id="sidebar">
    <div class="nav-header">MAIN NAVIGATION</div>
    <a href="../modules/dashboard.php" class="nav-link">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    
    <!-- Other menu items here -->
    
    <div class="nav-header">SYSTEM</div>
    <a href="/it-inventory/logout.php" class="nav-link text-danger" onclick="return confirmLogout();">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<script>
function confirmLogout() {
    return confirm('Are you sure you want to logout?\n\nYou will be redirected to the homepage.');
}
</script>