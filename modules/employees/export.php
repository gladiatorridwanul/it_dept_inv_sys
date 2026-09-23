<?php
require_once '../../includes/auth.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="employees_' . date('Y-m-d') . '.xls"');

$stmt = $pdo->query("SELECT pf_no, full_name, designation, job_location, department, phone, email, joining_date, 
                    CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as status 
                    FROM employees ORDER BY full_name");
$employees = $stmt->fetchAll();
?>

<table border="1">
    <thead>
        <tr>
            <th>PF No</th>
            <th>Full Name</th>
            <th>Designation</th>
            <th>Job Location</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Joining Date</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($employees as $emp): ?>
        <tr>
            <td><?php echo $emp['pf_no']; ?></td>
            <td><?php echo $emp['full_name']; ?></td>
            <td><?php echo $emp['designation']; ?></td>
            <td><?php echo $emp['job_location']; ?></td>
            <td><?php echo $emp['department']; ?></td>
            <td><?php echo $emp['phone']; ?></td>
            <td><?php echo $emp['email']; ?></td>
            <td><?php echo $emp['joining_date']; ?></td>
            <td><?php echo $emp['status']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>