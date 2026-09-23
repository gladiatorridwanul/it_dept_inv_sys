<?php
require_once '../../includes/auth.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vendor_id = $_POST['vendor_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $description = $_POST['description'] ?? '';
    
    $errors = [];
    if(empty($vendor_id)) $errors[] = "Please select a vendor";
    if($amount <= 0) $errors[] = "Amount must be greater than 0";
    if(empty($payment_date)) $errors[] = "Payment date is required";
    
    if(empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            // Create a direct payment record (without bill_id)
            $stmt = $pdo->prepare("INSERT INTO bill_payments (bill_id, amount, payment_date, payment_mode, notes, created_by, created_at) 
                                   VALUES (NULL, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$amount, $payment_date, $payment_mode, $description, $_SESSION['user_id']]);
            $payment_id = $pdo->lastInsertId();
            
            $pdo->commit();
            
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
            echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Quick Payment Recorded!",
                        text: "Payment of ৳' . number_format($amount, 2) . ' recorded successfully.",
                        confirmButtonColor: "#3085d6"
                    }).then(() => {
                        window.location.href = "make_payment.php";
                    });
                  </script>';
        } catch(Exception $e) {
            $pdo->rollBack();
            echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "' . addslashes($e->getMessage()) . '",
                        confirmButtonColor: "#3085d6"
                    }).then(() => {
                        window.location.href = "make_payment.php";
                    });
                  </script>';
        }
    } else {
        $error_msg = implode("\\n", $errors);
        echo '<script>
                alert("' . $error_msg . '");
                window.location.href = "make_payment.php";
              </script>';
    }
} else {
    header('Location: make_payment.php');
    exit();
}
?>