<?php
include 'config.php';
session_start();

// 1. Xóa sạch sẽ các Guest cũ và điểm của họ để dọn rác
// Nhờ lệnh ON DELETE CASCADE trong SQL của fen, bảng điểm sẽ tự sạch.
mysqli_query($conn, "DELETE FROM sinh_vien WHERE mssv LIKE 'GUEST%'");

// 2. Tạo lại đúng 1 tài khoản Guest duy nhất cho phiên này
$guest_id = "GUEST01";
$guest_name = "Người dùng Khách";

$sql_insert = "INSERT INTO sinh_vien (mssv, ho_ten, mat_khau) VALUES ('$guest_id', '$guest_name', '123456')";

if(mysqli_query($conn, $sql_insert)) {
    // 3. Thiết lập Session và vào trang chủ
    $_SESSION['mssv'] = $guest_id;
    header("Location: index.php");
    exit();
} else {
    echo "Lỗi hệ thống dọn dẹp Guest!";
}
?>