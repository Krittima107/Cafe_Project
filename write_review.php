<?php
session_start();
require_once 'config/db_connect.php';

if (!isset($_GET['menu_id'])) {
    header("Location:index1.php");
    exit;
}

$menu_id = $_GET['menu_id'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เขียนรีวิว</title>

    <style>
        body {
            font-family: Prompt;
            background: #f5e6c8;
            padding: 50px;
        }

        /* กล่อง */

        .review-box {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        /* ดาว */

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            font-size: 35px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            color: #ccc;
            cursor: pointer;
            transition: 0.2s;
        }

        .star-rating input:checked~label {
            color: #ffc107;
        }

        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #ffc107;
        }

        /* textarea */

        textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        /* ปุ่ม */

        button {
            margin-top: 15px;
            padding: 10px 20px;
            background: #8b6b5a;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background: #6f5243;
        }
    </style>

</head>

<body>

    <div class="review-box">

        <h2>เขียนรีวิวเมนู</h2>

        <form action="save_review.php" method="POST">

            <input type="hidden" name="menu_id" value="<?php echo $menu_id; ?>">

            <label>ให้คะแนน</label>

            <div class="star-rating">

                <input type="radio" name="rating" value="5" id="star5">
                <label for="star5">★</label>

                <input type="radio" name="rating" value="4" id="star4">
                <label for="star4">★</label>

                <input type="radio" name="rating" value="3" id="star3">
                <label for="star3">★</label>

                <input type="radio" name="rating" value="2" id="star2">
                <label for="star2">★</label>

                <input type="radio" name="rating" value="1" id="star1">
                <label for="star1">★</label>

            </div>

            <br>

            <textarea name="comment" placeholder="เขียนความคิดเห็น"></textarea>

            <br>

            <button type="submit">ส่งรีวิว</button>
            <a class="cancel-btn" href="menu_detail.php?id=<?php echo $menu_id; ?>">ยกเลิก</a>

        </form>

    </div>

</body>

</html>