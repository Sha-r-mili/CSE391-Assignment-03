<?php
require_once 'config.php';

$conn = getDBConnection();
$message = '';
$messageType = '';

// Simple admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];
        
        // Simple check (username: admin, password: admin123)
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
        } else {
            $message = "Invalid username or password!";
            $messageType = "error";
        }
    }
    
    if (!isset($_SESSION['admin_logged_in'])) {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Car Workshop</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .login-container {
                    background: white;
                    padding: 40px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    max-width: 400px;
                    width: 100%;
                }

                h1 {
                    text-align: center;
                    color: #2c3e50;
                    margin-bottom: 30px;
                    font-size: 2em;
                }

                .form-group {
                    margin-bottom: 20px;
                }

                label {
                    display: block;
                    margin-bottom: 8px;
                    color: #34495e;
                    font-weight: 600;
                }

                input {
                    width: 100%;
                    padding: 12px 15px;
                    border: 2px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 1em;
                    transition: border-color 0.3s;
                }

                input:focus {
                    outline: none;
                    border-color: #667eea;
                }

                .btn {
                    width: 100%;
                    padding: 14px;
                    border: none;
                    border-radius: 8px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    font-size: 1.1em;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
                }

                .message {
                    padding: 15px;
                    border-radius: 8px;
                    margin-bottom: 20px;
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }

                .back-link {
                    text-align: center;
                    margin-top: 20px;
                }

                .back-link a {
                    color: #667eea;
                    text-decoration: none;
                    font-weight: 600;
                }

                .back-link a:hover {
                    text-decoration: underline;
                }

                .info-text {
                    margin-top: 20px;
                    text-align: center;
                    color: #7f8c8d;
                    font-size: 0.9em;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 8px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>🔐 Admin Login</h1>
                <?php if ($message): ?>
                    <div class="message"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>
                    <button type="submit" name="login" class="btn">Login</button>
                </form>
                <div class="back-link">
                    <a href="index.php">← Back to Booking Page</a>
                </div>
                <div class="info-text">
                    <strong>Default Credentials:</strong><br>
                    Username: admin<br>
                    Password: admin123
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle appointment updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $new_date = sanitize_input($_POST['new_date']);
    $new_mechanic = (int)$_POST['new_mechanic'];
    
    // Check availability
    if (checkMechanicAvailability($new_mechanic, $new_date, $conn)) {
        $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, mechanic_id = ? WHERE appointment_id = ?");
        $stmt->bind_param("sii", $new_date, $new_mechanic, $appointment_id);
        
        if ($stmt->execute()) {
            $message = "Appointment updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating appointment.";
            $messageType = "error";
        }
    } else {
        $message = "Mechanic is not available on the selected date.";
        $messageType = "error";
    }
}

// Handle appointment deletion/cancellation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $appointment_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    
    if ($stmt->execute()) {
        $message = "Appointment cancelled successfully!";
        $messageType = "success";
    }
}

// Get all appointments
$appointments = $conn->query("SELECT a.*, m.mechanic_name 
                             FROM appointments a 
                             JOIN mechanics m ON a.mechanic_id = m.mechanic_id 
                             WHERE a.status != 'cancelled'
                             ORDER BY a.appointment_date DESC, a.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Car Workshop Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 2em;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .logout-btn, .home-btn {
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .logout-btn {
            background: #e74c3c;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .home-btn {
            background: #3498db;
        }

        .home-btn:hover {
            background: #2980b9;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
        }

        .appointments-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .appointments-section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .status.confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background: white;
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 15px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 2em;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s;
        }

        .close:hover {
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
        }

        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }

        .no-appointments {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .header {
                text-align: center;
            }

            .header h1 {
                font-size: 1.5em;
            }

            table {
                font-size: 0.85em;
            }

            th, td {
                padding: 10px;
            }

            .stat-card .number {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Admin Dashboard</h1>
            <div class="header-actions">
                <a href="index.php" class="home-btn">🏠 Home</a>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <?php
            $total = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status != 'cancelled'");
            $total_count = $total ? $total->fetch_assoc()['count'] : 0;
            
            $today = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE() AND status != 'cancelled'");
            $today_count = $today ? $today->fetch_assoc()['count'] : 0;
            
            $upcoming = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date > CURDATE() AND status != 'cancelled'");
            $upcoming_count = $upcoming ? $upcoming->fetch_assoc()['count'] : 0;
            ?>
            <div class="stat-card">
                <h3>Total Appointments</h3>
                <div class="number"><?php echo $total_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="number"><?php echo $today_count; ?></div>
            </div>
            <div class="stat-card">
                <h3>Upcoming</h3>
                <div class="number"><?php echo $upcoming_count; ?></div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="appointments-section">
            <h2>All Appointments</h2>
            <?php if ($appointments && $appointments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Phone</th>
                                <th>Car License</th>
                                <th>Engine No.</th>
                                <th>Date</th>
                                <th>Mechanic</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['appointment_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['client_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_license']); ?></td>
                                    <td><?php echo htmlspecialchars($row['car_engine']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['appointment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['mechanic_name']); ?></td>
                                    <td><span class="status <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td>
                                        <div class="actions">
                                            <button class="btn btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'>Edit</button>
                                            <a href="?delete=<?php echo $row['appointment_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-appointments">
                    <p>No appointments found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Appointment</h2>
            <form method="POST" action="">
                <input type="hidden" name="appointment_id" id="edit_appointment_id">
                
                <div class="form-group">
                    <label>New Date</label>
                    <input type="date" name="new_date" id="edit_date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label>New Mechanic</label>
                    <select name="new_mechanic" id="edit_mechanic" required>
                        <?php
                        $mechanics = $conn->query("SELECT * FROM mechanics ORDER BY mechanic_name");
                        if ($mechanics) {
                            while ($mechanic = $mechanics->fetch_assoc()):
                        ?>
                            <option value="<?php echo $mechanic['mechanic_id']; ?>">
                                <?php echo htmlspecialchars($mechanic['mechanic_name']); ?>
                            </option>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" name="update_appointment" class="btn btn-edit" style="width: 100%;">Update Appointment</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(appointment) {
            document.getElementById('edit_appointment_id').value = appointment.appointment_id;
            document.getElementById('edit_date').value = appointment.appointment_date;
            document.getElementById('edit_mechanic').value = appointment.mechanic_id;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>