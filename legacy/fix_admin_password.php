<?php
// ONE-TIME FIX SCRIPT — delete this file after running it!
include("gabsdb.php");

$username = 'admin';
$plain_password = 'admin123';

// Check what's currently stored
$stmt = $conn->prepare("SELECT user_ID, password FROM accounts WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "No account found with username: $username";
    exit;
}

$user = $result->fetch_assoc();
$stored = $user['password'];

echo "<b>Current stored value:</b> " . htmlspecialchars($stored) . "<br><br>";

// Check if it's already a valid hash
if (password_verify($plain_password, $stored)) {
    echo "Password is already correctly hashed — login should work. Check your role/userroles table instead.";
    exit;
}

// It's plain text (or wrong hash), fix it
$new_hash = password_hash($plain_password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE accounts SET password = ? WHERE user_ID = ?");
$update->bind_param("si", $new_hash, $user['user_ID']);

if ($update->execute()) {
    echo "<b style='color:green'>Fixed!</b> Password for '$username' is now properly hashed.<br>";
    echo "New hash: " . htmlspecialchars($new_hash) . "<br><br>";
    echo "<b style='color:red'>Delete this file now!</b>";
} else {
    echo "Update failed: " . $conn->error;
}
