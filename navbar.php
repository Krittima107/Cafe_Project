<?php
// โค้ดอัจฉริยะ 1: เช็คว่าไฟล์นี้ถูกเรียกใช้จากโฟลเดอร์ admin หรือไม่ ถ้าใช่ให้เติม ../ หน้าลิงก์
$path = (isset($in_admin) && $in_admin) ? '../' : '';

// โค้ดอัจฉริยะ 2: เช็คชื่อไฟล์ปัจจุบันว่าคือหน้าอะไร
$current_page = basename($_SERVER['PHP_SELF']);

// 1. นับจำนวนสินค้าในตะกร้า
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cart_count += $qty;
    }
}

// 2. นับจำนวนเมนูโปรด (ดึงข้อมูลจากฐานข้อมูล)
$fav_count = 0;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $stmt_fav = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = :uid");
    $stmt_fav->execute([':uid' => $_SESSION['user_id']]);
    $fav_count = $stmt_fav->fetchColumn();
}
?>
<style>
    .navbar {
        background-color: var(--color-brown-light);
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        font-family: 'Prompt', sans-serif;
    }

    .navbar h2 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .navbar a {
        color: white !important;
        text-decoration: none !important;
        padding: 8px 15px;
        background-color: var(--color-brown-dark);
        border-radius: 4px;
        font-size: 14px;
        transition: 0.3s;
    }

    .navbar a:hover {
        opacity: 0.8;
    }

    .nav-logo-img {
        width: 45px;
        height: 45px;
        object-fit: cover;
        background-color: white;
        border-radius: 50%;
        padding: 2px;
    }

    /* สไตล์ปุ่มที่มีตัวเลขแจ้งเตือน */
    .nav-btn-box {
        position: relative;
        display: inline-block;
    }

    .nav-badge {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ff4d4f;
        color: white;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 50%;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        border: 2px solid white;
    }

    /* สไตล์ใหม่สำหรับกล่องโปรไฟล์ที่กดได้ */
    .profile-pill {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.15) !important;
        padding: 5px 15px !important;
        border-radius: 30px !important;
        border: 1px solid rgba(255, 255, 255, 0.3);
        white-space: nowrap;
        transition: 0.3s !important;
    }

    .profile-pill:hover {
        background: rgba(255, 255, 255, 0.25) !important;
        /* สว่างขึ้นเมื่อเอาเมาส์ชี้ */
        opacity: 1 !important;
        transform: translateY(-2px);
    }
</style>

<div class="navbar">
    <a href="<?php echo $path; ?>index1.php" style="background: none; padding: 0; display: flex; align-items: center;">
        <h2 style="transition: opacity 0.3s; color: white;" onmouseover="this.style.opacity='0.8'"
            onmouseout="this.style.opacity='1'">
            <img src="<?php echo $path; ?>assets/logo.png" alt="Moom Marm Cafe Logo" class="nav-logo-img">
            Moom Marm Cafe
        </h2>
    </a>

    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">

        <?php if (!isset($is_admin) || !$is_admin): ?>

            <a href="<?php echo $path; ?>cart.php" style="background-color: var(--color-green);" class="nav-btn-box">
                🛒 ตะกร้า
                <?php if ($cart_count > 0): ?>
                    <span class="nav-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

            <?php if (isset($current_user) && $current_user): ?>
                <a href="<?php echo $path; ?>favorite_menu.php" style="background-color: #ff5c5c; white-space: nowrap;"
                    class="nav-btn-box">
                    ❤️ เมนูโปรด
                    <?php if ($fav_count > 0): ?>
                        <span class="nav-badge"><?php echo $fav_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

        <?php endif; ?>

        <?php if (isset($current_user) && $current_user): ?>

            <?php
            // เช็คว่าต้องลิงก์ไปหน้าแก้ไขโปรไฟล์ของใคร (Admin หรือ User)
            $edit_link = (isset($is_admin) && $is_admin) ? $path . 'admin/edit_profile.php' : $path . 'user_edit_profile.php';
            ?>

            <a href="<?php echo $edit_link; ?>" class="profile-pill" title="คลิกเพื่อแก้ไขโปรไฟล์">
                <?php if (!empty($current_user['profile_image'])): ?>
                    <img src="<?php echo $path; ?>uploads/profiles/<?php echo $current_user['profile_image']; ?>"
                        style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <div
                        style="width: 35px; height: 35px; border-radius: 50%; background: #ccc; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                        👤</div>
                <?php endif; ?>

                <div style="line-height: 1.2;">
                    <div style="font-weight: bold; color: white;"><?php echo htmlspecialchars($current_user['username']); ?>
                    </div>
                    <div style="font-size: 12px; color: #a5d6a7; display: flex; align-items: center; gap: 4px;">
                        <span
                            style="display: inline-block; width: 8px; height: 8px; background-color: #4CAF50; border-radius: 50%; box-shadow: 0 0 4px #4CAF50; flex-shrink: 0;"></span>
                        ออนไลน์
                    </div>
                </div>
            </a>

            <?php if (isset($is_admin) && $is_admin): ?>
                <?php if ($current_page !== 'dashboard.php'): ?>
                    <a href="<?php echo $path; ?>admin/dashboard.php"
                        style="background-color: var(--color-brown-dark); white-space: nowrap;">⚙️ Dashboard</a>
                <?php endif; ?>
            <?php endif; ?>

            <a href="<?php echo $path; ?>logout.php" style="background-color: #d9534f; white-space: nowrap;">ออกจากระบบ</a>

        <?php else: ?>
            <a href="<?php echo $path; ?>login.php"
                style="background-color: var(--color-brown-dark); white-space: nowrap;">☕ เข้าสู่ระบบ</a>
        <?php endif; ?>
    </div>
</div>