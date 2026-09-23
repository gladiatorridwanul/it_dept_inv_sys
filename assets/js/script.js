// IT Inventory Management System - Custom JavaScript

$(document).ready(function() {
    
    // Initialize all tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize all popovers
    $('[data-toggle="popover"]').popover();
    
    // Sidebar toggle
    $('#sidebarToggle').click(function() {
        $('#sidebar').toggleClass('show');
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert:not(.alert-permanent)').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
    
    // Delete confirmation
    $('.delete-confirm').click(function(e) {
        if(!confirm('Are you sure you want to delete this item?\n\nThis action cannot be undone!')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Status change confirmation
    $('.status-change').click(function(e) {
        if(!confirm('Are you sure you want to change the status?')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Number formatting for currency inputs
    $('.currency-input').on('blur', function() {
        var value = parseFloat($(this).val());
        if(!isNaN(value)) {
            $(this).val(value.toFixed(2));
        }
    });
    
    // Search functionality with debounce
    var searchTimeout;
    $('.search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        searchTimeout = setTimeout(function() {
            if(searchTerm.length >= 2 || searchTerm.length === 0) {
                $('.search-form').submit();
            }
        }, 500);
    });
    
    // Print function
    window.printDiv = function(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    };
    
    // Export to Excel
    window.exportToExcel = function(tableId, filename) {
        var table = document.getElementById(tableId);
        var html = table.outerHTML;
        var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename + '.xls';
        link.click();
    };
    
    // Export to CSV
    window.exportToCSV = function(tableId, filename) {
        var table = document.getElementById(tableId);
        var rows = table.querySelectorAll('tr');
        var csv = [];
        
        for(var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll('td, th');
            for(var j = 0; j < cols.length; j++) {
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            }
            csv.push(row.join(','));
        }
        
        var csvContent = csv.join('\n');
        var link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
        link.download = filename + '.csv';
        link.click();
    };
    
    // Form validation
    $('.validate-form').on('submit', function(e) {
        var isValid = true;
        $(this).find('[required]').each(function() {
            if($(this).val() === '' || $(this).val() === null) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if(!isValid) {
            e.preventDefault();
            alert('Please fill all required fields!');
        }
        
        return isValid;
    });
    
    // Dynamic item selection for stock in
    $('#itemSelect').on('change', function() {
        var price = $(this).find(':selected').data('price');
        if(price) {
            $('#unitPrice').val(price);
            calculateTotal();
        }
    });
    
    // Calculate total amount
    window.calculateTotal = function() {
        var qty = parseFloat($('#quantity').val()) || 0;
        var price = parseFloat($('#unitPrice').val()) || 0;
        var total = qty * price;
        $('#totalAmount').val(total.toFixed(2));
    };
    
    // Date picker initialization
    if($('.datepicker').length) {
        $('.datepicker').attr('type', 'date');
    }
    
    // Select2 initialization for better dropdowns
    if($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select an option'
        });
    }
    
    // DataTables customization
    if($.fn.DataTable) {
        $('.datatable').DataTable({
            pageLength: 25,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
                zeroRecords: "No matching records found",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i class="fas fa-copy"></i> Copy'
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> CSV'
                },
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Print'
                }
            ]
        });
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl + S to save
        if(e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('form').first().submit();
        }
        // Alt + N for new
        if(e.altKey && e.key === 'n') {
            window.location.href = window.location.pathname.replace(/\/[^\/]*$/, '/add.php');
        }
        // Alt + L for list
        if(e.altKey && e.key === 'l') {
            window.location.href = window.location.pathname.replace(/\/[^\/]*$/, '/list.php');
        }
    });
    
    // Confirm before leaving unsaved form
    var formChanged = false;
    $('form input, form select, form textarea').change(function() {
        formChanged = true;
    });
    
    window.addEventListener('beforeunload', function(e) {
        if(formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    $('form').on('submit', function() {
        formChanged = false;
    });
    
    console.log('IT Inventory System Loaded Successfully!');
});

// Utility Functions
function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

function formatCurrency(amount) {
    return '₹ ' + formatNumber(parseFloat(amount).toFixed(2));
}

function showNotification(message, type = 'success') {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show fixed-top" style="top: 70px; right: 20px; left: auto; z-index: 9999; max-width: 400px;" role="alert">' +
        '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    $('body').append(notification);
    setTimeout(function() {
        notification.fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
}