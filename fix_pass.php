<?php
require_once 'config/db_connect.php';

// รหัสผ่านที่เราต้องการตั้งคือ 123456
$password = '123456';
// ให้ PHP เข้ารหัสให้ถูกต้อง
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // อัปเดตรหัสผ่านใหม่ให้กับทุกคนในตาราง userscafe
    $stmt = $conn->prepare("UPDATE userscafe SET password = :password");
    $stmt->bindParam(':password', $hashed_password);

    if ($stmt->execute()) {
        echo "<h3>✅ อัปเดตรหัสผ่านสำเร็จ!</h3>";
        echo "<p>ตอนนี้รหัสผ่านของ admin และ user1 คือ: <strong>123456</strong></p>";
        echo "<a href='login.php'>คลิกที่นี่เพื่อกลับไปหน้า Login</a>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>