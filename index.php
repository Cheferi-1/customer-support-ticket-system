<?php

$host = "localhost";
$port = 3307;
$dbname = "lab06_support";
$username = "root";
$password = "";

$errors = [];
$success = "";

$customer_name = "";
$email = "";
$issue_category = "";
$priority = "";
$subject = "";
$issue_description = "";

try {
    // Connect to database
    $conn = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed. Check your database settings.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect form values
    $customer_name = trim($_POST["customer_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $issue_category = trim($_POST["issue_category"] ?? "");
    $priority = trim($_POST["priority"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $issue_description = trim($_POST["issue_description"] ?? "");
    $confirmation = isset($_POST["confirmation"]);

    // Available categories
    $categories = [
        "Technical Problem",
        "Payment Problem",
        "Account Problem",
        "Delivery Problem",
        "Other"
    ];

    // Available priorities
    $priorities = [
        "Low",
        "Medium",
        "High",
        "Urgent"
    ];

    // Validate customer name
    if ($customer_name == "" || strlen($customer_name) > 100) {
        $errors[] = "Customer name is required and must not exceed 100 characters.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Validate category
    if (!in_array($issue_category, $categories, true)) {
        $errors[] = "Please select a valid issue category.";
    }

    // Validate priority
    if (!in_array($priority, $priorities, true)) {
        $errors[] = "Please select a valid priority.";
    }

    // Validate subject
    if (strlen($subject) < 5 || strlen($subject) > 120) {
        $errors[] = "Subject must contain between 5 and 120 characters.";
    }

    // Validate description
    if (
        strlen($issue_description) < 20 ||
        strlen($issue_description) > 2000
    ) {
        $errors[] = "Description must contain between 20 and 2000 characters.";
    }

    // Validate confirmation
    if (!$confirmation) {
        $errors[] = "Please confirm that the details are accurate.";
    }

    // Validate screenshot
    $image_path = null;
    $uploaded_file = null;

    if (
        isset($_FILES["screenshot"]) &&
        $_FILES["screenshot"]["error"] != UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["screenshot"]["error"] != UPLOAD_ERR_OK) {
            $errors[] = "There was a problem uploading the screenshot.";

        } elseif ($_FILES["screenshot"]["size"] >= 2 * 1024 * 1024) {
            $errors[] = "Screenshot must be smaller than 2MB.";

        } else {

            // Check actual image type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES["screenshot"]["tmp_name"]);

            $allowed_types = [
                "image/png" => "png",
                "image/jpeg" => "jpg"
            ];

            if (!isset($allowed_types[$mime])) {
                $errors[] = "Only PNG and JPEG screenshots are allowed.";

            } elseif (
                getimagesize($_FILES["screenshot"]["tmp_name"]) === false
            ) {
                $errors[] = "The uploaded file is not a valid image.";

            } else {

                // Generate a safe filename
                $extension = $allowed_types[$mime];
                $filename = bin2hex(random_bytes(16)) . "." . $extension;

                $upload_directory = __DIR__ . "/uploads/";

                if (!is_dir($upload_directory)) {
                    mkdir($upload_directory, 0755, true);
                }

                $uploaded_file = $upload_directory . $filename;
                $image_path = "uploads/" . $filename;
            }
        }
    }

    // Save ticket if everything is valid
    if (empty($errors)) {

        try {

            // Move screenshot
            if ($uploaded_file !== null) {

                if (!move_uploaded_file(
                    $_FILES["screenshot"]["tmp_name"],
                    $uploaded_file
                )) {
                    throw new Exception("Screenshot upload failed.");
                }
            }

            // Insert ticket
            $sql = "INSERT INTO support_tickets
            (customer_name, email, issue_category, priority,
            subject, issue_description, screenshot)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $customer_name,
                $email,
                $issue_category,
                $priority,
                $subject,
                $issue_description,
                $image_path
            ]);

            // Get ticket reference
            $ticket_id = $conn->lastInsertId();

            $success = "Your support ticket was submitted successfully. Your ticket reference is TKT-" . $ticket_id;

            // Clear form
            $customer_name = "";
            $email = "";
            $issue_category = "";
            $priority = "";
            $subject = "";
            $issue_description = "";

        } catch (PDOException $e) {

            // Remove uploaded screenshot if database insertion fails
            if ($uploaded_file !== null && file_exists($uploaded_file)) {
                unlink($uploaded_file);
            }

            $errors[] = "Could not save your ticket. Please try again.";

        } catch (Exception $e) {

            $errors[] = "Could not upload your screenshot.";
        }
    }
}

// Get all tickets
$tickets = $conn->query(
    "SELECT * FROM support_tickets ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Support Ticket</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h1>Customer Support</h1>

    <p class="subtitle">
        Submit your issue and our support team will assist you.
    </p>

    <?php if (!empty($errors)): ?>

        <div class="error">

            <strong>Please correct the following errors:</strong>

            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>

        </div>

    <?php endif; ?>

    <?php if ($success != ""): ?>

        <div class="success">
            <?= htmlspecialchars($success) ?>
        </div>

    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>Customer Name *</label>

        <input type="text" name="customer_name"
            value="<?= htmlspecialchars($customer_name) ?>"
            required>

        <label>Email Address *</label>

        <input type="email" name="email"
            value="<?= htmlspecialchars($email) ?>"
            required>

        <label>Issue Category *</label>

        <select name="issue_category" required>

            <option value="">Select Category</option>

            <?php
            $categories = [
                "Technical Problem",
                "Payment Problem",
                "Account Problem",
                "Delivery Problem",
                "Other"
            ];

            foreach ($categories as $item):
            ?>

                <option value="<?= htmlspecialchars($item) ?>"
                    <?= $issue_category == $item ? "selected" : "" ?>>

                    <?= htmlspecialchars($item) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <label>Priority *</label>

        <select name="priority" required>

            <option value="">Select Priority</option>

            <?php
            $priorities = ["Low", "Medium", "High", "Urgent"];

            foreach ($priorities as $item):
            ?>

                <option value="<?= htmlspecialchars($item) ?>"
                    <?= $priority == $item ? "selected" : "" ?>>

                    <?= htmlspecialchars($item) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <label>Subject *</label>

        <input type="text" name="subject"
            minlength="5" maxlength="120"
            value="<?= htmlspecialchars($subject) ?>"
            required>

        <label>Issue Description *</label>

        <textarea name="issue_description"
            minlength="20" maxlength="2000"
            rows="6" required><?= htmlspecialchars($issue_description) ?></textarea>

        <label>Screenshot (Optional - PNG/JPEG, under 2MB)</label>

        <input type="file" name="screenshot"
            accept=".png,.jpg,.jpeg,image/png,image/jpeg">

        <div class="checkbox">

            <input type="checkbox" name="confirmation"
                value="1" required>

            <label>
                I confirm that the information provided is accurate. *
            </label>

        </div>

        <button type="submit">Submit Support Ticket</button>

    </form>

</div>

<div class="inventory">

    <h2>Support Tickets</h2>

    <?php if (empty($tickets)): ?>

        <p>No support tickets available yet.</p>

    <?php else: ?>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Created</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($tickets as $ticket): ?>

                        <tr>

                            <td>TKT-<?= (int)$ticket["id"] ?></td>

                            <td>
                                <?= htmlspecialchars($ticket["customer_name"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ticket["subject"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ticket["issue_category"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ticket["priority"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ticket["status"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ticket["created_at"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

</body>
</html>