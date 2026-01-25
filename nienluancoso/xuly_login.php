<?php
include 'config.php';

if (isset($_POST['btn_login'])) {
    $mssv = mysqli_real_escape_string($conn, $_POST['mssv']);
    $pass = mysqli_real_escape_string($conn, $_POST['password']);

    $sql = "SELECT * FROM sinh_vien WHERE mssv = '$mssv' AND mat_khau = '$pass'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['mssv'] = $user['mssv'];
        $_SESSION['ho_ten'] = $user['ho_ten'];
        header("Location: index.php");
    } else {
        echo "<script>alert('Sai MSSV hoặc mật khẩu!'); window.location='login.php';</script>";
    }
}
?>