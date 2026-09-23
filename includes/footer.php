<?php
// includes/footer.php
?>
    </div> <!-- Close main-content-wrapper -->
    
    <!-- Scroll to Top Button -->
    <div class="scroll-top" id="scrollTop">
        <i class="fas fa-arrow-up"></i>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Sidebar Toggle with localStorage
            let sidebarClosed = localStorage.getItem('sidebarClosed') === 'true';
            if(sidebarClosed) {
                $('body').addClass('sidebar-closed');
            }
            
            $('#sidebarToggleBtn').click(function() {
                $('body').toggleClass('sidebar-closed');
                localStorage.setItem('sidebarClosed', $('body').hasClass('sidebar-closed'));
            });
            
            // Scroll to top
            $(window).scroll(function() {
                if($(this).scrollTop() > 100) {
                    $('#scrollTop').fadeIn();
                } else {
                    $('#scrollTop').fadeOut();
                }
            });
            
            $('#scrollTop').click(function() {
                $('html, body').animate({scrollTop: 0}, 300);
            });
            
            // DataTable initialization
            if ($.fn.DataTable) {
                $('.datatable').DataTable({
                    "pageLength": 25,
                    "language": {
                        "search": "Search:",
                        "lengthMenu": "Show _MENU_ entries",
                        "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                        "emptyTable": "No data available"
                    }
                });
            }
            
            // Auto close sidebar on mobile when clicking a link
            if($(window).width() < 768) {
                $('.sidebar-nav .nav-link').click(function() {
                    $('#sidebarWrapper').removeClass('show');
                });
            }
        });
        
        // Confirm Logout function
        function confirmLogout() {
            return confirm('Are you sure you want to logout?');
        }
    </script>
</body>
</html>