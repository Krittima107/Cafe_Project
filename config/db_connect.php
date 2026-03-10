<?php
$host = 'localhost';
$dbname = 'it67040233127';
$username = 'it67040233127'; // ปกติ XAMPP จะใช้ root
$password = 'V1I5Z7A5';     // ปกติ XAMPP จะไม่มีรหัสผ่าน

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // ตั้งค่าให้แสดง Error หาก Query ผิดพลาด
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
?>