<?php
session_start();
require_once '../backends/config.php';
$conn = get_db_connection();

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$message = "";

$stmt = $conn->prepare("SELECT fullname, email, gender, location, status, phone, profile_completed FROM customers WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $gender = trim($_POST['gender'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($gender) || empty($location) || empty($status) || empty($phone)) {
        $message = "Please complete all fields.";
    } else {

        $update = $conn->prepare("UPDATE customers SET gender=?, location=?, status=?, phone=?, profile_completed=1 WHERE id=?");
        $update->bind_param("ssssi", $gender, $location, $status, $phone, $user_id);

        if ($update->execute()) {
            header("Location: account.php?completed=1");
            exit();
        } else {
            $message = "Failed to update account.";
        }
    }
}
if (!empty($_SESSION['reservation_redirect'])) {
    $redirect = $_SESSION['reservation_redirect'];
    unset($_SESSION['reservation_redirect']);
    header("Location: " . $redirect);
    exit();
}

header("Location: account.php?completed=1");
exit();

?>

<?php if (isset($_GET['incomplete'])): ?>
    <div class="alert alert-warning text-center">
        Please complete your profile before booking an appointment.
    </div>
<?php endif; ?>
<div class="auth-card">
    <h3 class="auth-title">Complete Your Account</h3>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="text" class="form-control mb-3" value="<?= htmlspecialchars($user['fullname']) ?>" readonly>

        <input type="email" class="form-control mb-3" value="<?= htmlspecialchars($user['email']) ?>" readonly>

        <input type="text" name="phone" class="form-control mb-3" placeholder="Phone Number" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

        <select name="gender" class="form-select mb-3" required>
            <option value="">Select Gender</option>
            <option value="Male" <?= ($user['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= ($user['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= ($user['gender'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
        </select>

        <select name="location" class="form-select mb-3" required>
            <option value="">Select Location</option>
            <option value="Northern Iloilo" <?= ($user['location'] ?? '') == 'Northern Iloilo' ? 'selected' : '' ?>>Northern Iloilo</option>
            <option value="Central Iloilo" <?= ($user['location'] ?? '') == 'Central Iloilo' ? 'selected' : '' ?>>Central Iloilo</option>
            <option value="Southern Iloilo" <?= ($user['location'] ?? '') == 'Southern Iloilo' ? 'selected' : '' ?>>Southern Iloilo</option>
        </select>

        <select name="status" class="form-select mb-3" required>
            <option value="">Select Status</option>
            <option value="Local" <?= ($user['status'] ?? '') == 'Local' ? 'selected' : '' ?>>Local</option>
            <option value="OFW" <?= ($user['status'] ?? '') == 'OFW' ? 'selected' : '' ?>>OFW</option>
        </select>

        <button type="submit" class="btn btn-gold w-100">
            Save Account Information
        </button>
    </form>
</div>