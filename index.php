<?php
require_once 'config.php';

$conn = getDBConnection();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $client_name = sanitize_input($_POST['client_name']);
    $client_address = sanitize_input($_POST['client_address']);
    $client_phone = sanitize_input($_POST['client_phone']);
    $car_license = sanitize_input($_POST['car_license']);
    $car_engine = sanitize_input($_POST['car_engine']);
    $mechanic_id = (int)$_POST['mechanic_id'];
    $appointment_date = sanitize_input($_POST['appointment_date']);
    
    // Validation
    if (empty($client_name) || empty($client_address) || empty($client_phone) || 
        empty($car_license) || empty($car_engine) || empty($mechanic_id) || empty($appointment_date)) {
        $message = "All fields are required!";
        $messageType = "error";
    } 
    // Check if date is in the future
    elseif (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $message = "Appointment date must be today or in the future!";
        $messageType = "error";
    }
    // Check if client already has appointment on this date
    elseif (checkClientAppointment($client_phone, $appointment_date, $conn)) {
        $message = "You already have an appointment on this date!";
        $messageType = "error";
    }
    // Check mechanic availability
    elseif (!checkMechanicAvailability($mechanic_id, $appointment_date, $conn)) {
        $message = "This mechanic is fully booked on the selected date. Please choose another mechanic or date.";
        $messageType = "error";
    }
    else {
        // Insert appointment
        $stmt = $conn->prepare("INSERT INTO appointments (client_name, client_address, client_phone, car_license, car_engine, mechanic_id, appointment_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')");
        $stmt->bind_param("sssssis", $client_name, $client_address, $client_phone, $car_license, $car_engine, $mechanic_id, $appointment_date);
        
        if ($stmt->execute()) {
            $message = "Appointment booked successfully! Your appointment ID is: " . $stmt->insert_id;
            $messageType = "success";
        } else {
            $message = "Error booking appointment. Please try again.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Get all mechanics with availability for selected date
$selected_date = isset($_POST['check_date']) ? $_POST['check_date'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Workshop - Appointment Booking System</title>
    <meta name="description" content="Book your car service appointment online with our expert mechanics">
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
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
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

        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8em;
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

        input[type="text"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .mechanics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .mechanic-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .mechanic-card:hover {
            border-color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .mechanic-card h4 {
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .mechanic-card p {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .available {
            color: #27ae60 !important;
            font-weight: 600;
        }

        .admin-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .admin-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
        }

        .admin-link a:hover {
            text-decoration: underline;
        }

        .required {
            color: #e74c3c;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }
            
            .content {
                padding: 20px;
            }
            
            .form-section {
                padding: 20px;
            }
            
            .mechanics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Elite Car Workshop</h1>
            <p>Book Your Appointment with Expert Mechanics</p>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Check Availability Section -->
            <div class="form-section">
                <h2>Check Mechanic Availability</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Select Date to Check Availability:</label>
                        <input type="date" name="check_date" value="<?php echo htmlspecialchars($selected_date); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Check Availability</button>
                </form>

                <div class="mechanics-grid">
                    <?php
                    $mechanics = getAvailableMechanics($selected_date, $conn);
                    if ($mechanics && $mechanics->num_rows > 0) {
                        while ($mechanic = $mechanics->fetch_assoc()):
                    ?>
                        <div class="mechanic-card">
                            <h4><?php echo htmlspecialchars($mechanic['mechanic_name']); ?></h4>
                            <p class="available"><?php echo $mechanic['free_slots']; ?> slots available</p>
                            <p>Total capacity: <?php echo $mechanic['max_appointments']; ?></p>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo '<p style="color: #e74c3c;">No mechanics available on this date. Please select another date.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="form-section">
                <h2>Book Your Appointment</h2>
                <form method="POST" action="" onsubmit="return validateForm()">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="client_name" id="client_name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-group">
                        <label>Address <span class="required">*</span></label>
                        <textarea name="client_address" id="client_address" placeholder="Enter your address" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" name="client_phone" id="client_phone" placeholder="e.g., 01712345678" pattern="[0-9]{11}" required>
                        <small style="color: #7f8c8d;">Enter 11-digit phone number</small>
                    </div>

                    <div class="form-group">
                        <label>Car License Number <span class="required">*</span></label>
                        <input type="text" name="car_license" id="car_license" placeholder="e.g., DHA-1234" required>
                    </div>

                    <div class="form-group">
                        <label>Car Engine Number <span class="required">*</span></label>
                        <input type="text" name="car_engine" id="car_engine" placeholder="Enter engine number" required>
                    </div>

                    <div class="form-group">
                        <label>Appointment Date <span class="required">*</span></label>
                        <input type="date" name="appointment_date" id="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Select Mechanic <span class="required">*</span></label>
                        <select name="mechanic_id" id="mechanic_id" required>
                            <option value="">-- Choose a mechanic --</option>
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

                    <button type="submit" name="book_appointment" class="btn btn-primary">Book Appointment</button>
                </form>
            </div>

            <div class="admin-link">
                <a href="admin.php">🔐 Admin Login</a>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            const phone = document.getElementById('client_phone').value;
            const date = document.getElementById('appointment_date').value;
            const name = document.getElementById('client_name').value;
            
            if (name.trim().length < 3) {
                alert('Please enter a valid name (minimum 3 characters)');
                return false;
            }
            
            if (phone.length !== 11 || !/^\d+$/.test(phone)) {
                alert('Please enter a valid 11-digit phone number');
                return false;
            }
            
            if (new Date(date) < new Date().setHours(0,0,0,0)) {
                alert('Please select today or a future date');
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>