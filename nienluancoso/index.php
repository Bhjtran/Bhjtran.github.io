<?php 
session_start();
// Nếu không tồn tại session mssv (chưa đăng nhập), đuổi ngay về trang login
if (!isset($_SESSION['mssv'])) {
    header("Location: login.php");
    exit();
}
// Nếu đã có session, tiếp tục lấy dữ liệu như bình thường
include 'config.php';
$mssv = $_SESSION['mssv'];

// 1. XỬ LÝ XÓA MÔN HỌC
if(isset($_GET['delete_id'])){
    $del_id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM bang_diem WHERE id = '$del_id' AND mssv = '$mssv'");
    header("Location: index.php");
    exit();
}

// 2. XỬ LÝ CẬP NHẬT TOÀN BỘ THÔNG TIN (SỬA FULL)
if(isset($_POST['btn_update'])){
    $id_edit = $_POST['id_edit'];
    $ten_moi = mysqli_real_escape_string($conn, $_POST['ten_moi']);
    $tc_moi = $_POST['tc_moi'];
    $loai_moi = $_POST['loai_moi'];
    $diem_moi = $_POST['diem_4_moi'];
    
    $res_ma = mysqli_query($conn, "SELECT ma_hp FROM bang_diem WHERE id = '$id_edit'");
    $row_ma = mysqli_fetch_assoc($res_ma);
    $ma_hp = $row_ma['ma_hp'];

    mysqli_query($conn, "UPDATE mon_hoc SET ten_hp = '$ten_moi', so_tc = '$tc_moi', loai_hp = '$loai_moi' WHERE ma_hp = '$ma_hp'");
    mysqli_query($conn, "UPDATE bang_diem SET diem_4 = '$diem_moi' WHERE id = '$id_edit' AND mssv = '$mssv'");
    
    header("Location: index.php");
    exit();
}

// 3. XỬ LÝ LƯU MÔN HỌC MỚI
if(isset($_POST['btn_save'])){
    $ma = mysqli_real_escape_string($conn, $_POST['ma_hp']);
    $ten = mysqli_real_escape_string($conn, $_POST['ten_hp']);
    $tc = $_POST['so_tc'];
    $d4 = $_POST['diem_4'];
    $loai = $_POST['loai_hp'];
    
    if($mssv == 'GUEST'){
        mysqli_query($conn, "INSERT IGNORE INTO sinh_vien (mssv, ho_ten, mat_khau) VALUES ('GUEST', 'Khách', '123')");
    }

    mysqli_query($conn, "INSERT IGNORE INTO mon_hoc (ma_hp, ten_hp, so_tc, loai_hp) VALUES ('$ma', '$ten', '$tc', '$loai')");
    mysqli_query($conn, "INSERT INTO bang_diem (mssv, ma_hp, diem_4) VALUES ('$mssv', '$ma', '$d4')");
    header("Location: index.php");
    exit();
}

// 4. XỬ LÝ CẬP NHẬT B1 (SWITCH)
if(isset($_POST['update_b1'])) {
    $_SESSION['dat_b1_'.$mssv] = isset($_POST['has_b1']);
    header("Location: index.php");
    exit();
}

function tinhGPAChuan($mssv) {
    global $conn;
    $sql = "SELECT m.so_tc, b.diem_4 FROM bang_diem b JOIN mon_hoc m ON b.ma_hp = m.ma_hp WHERE b.mssv = '$mssv' AND m.loai_hp != 'DieuKien'";
    $res = mysqli_query($conn, $sql);
    $tong_diem = 0; $tong_tc = 0;
    while($row = mysqli_fetch_assoc($res)) {
        $tong_diem += $row['diem_4'] * $row['so_tc'];
        $tong_tc += $row['so_tc'];
    }
    return ($tong_tc > 0) ? round($tong_diem / $tong_tc, 2) : 0;
}

function convertDiemSoSangChu($diem4) {
    if ($diem4 >= 4.0) return "A";
    if ($diem4 >= 3.5) return "B+";
    if ($diem4 >= 3.0) return "B";
    if ($diem4 >= 2.5) return "C+";
    if ($diem4 >= 2.0) return "C";
    if ($diem4 >= 1.5) return "D+";
    if ($diem4 >= 1.0) return "D";
    return "F";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>CTU SCORE - HỆ THỐNG QUẢN LÝ ĐIỂM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Arial, sans-serif; }
        .navbar { background: white; padding: 15px 0; border-bottom: 1px solid #eee; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .btn-save { background-color: #4e73df; color: white; border-radius: 8px; font-weight: bold; transition: 0.3s; }
        .btn-save:hover { background-color: #2e59d9; }
        .form-control, .form-select { border-radius: 8px; padding: 10px; border: 1px solid #ddd; }
        .gpa-card { background: #2e59d9; color: white; border-radius: 15px; text-align: center; padding: 30px; }
        .gpa-val { font-size: 5rem; font-weight: 800; line-height: 1; }
        .table thead th { font-weight: 800; color: #555; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid #f4f6f9; }
        .badge-loai { font-size: 0.75rem; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>

<nav class="navbar mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h4 class="text-primary fw-bold m-0 me-3">
                <i class="fas fa-graduation-cap me-2"></i>CTU SCORE
            </h4>
            <a href="du_toan.php" class="btn btn-sm btn-primary px-3 shadow-sm" style="border-radius: 20px;">
                <i class="fas fa-magic me-1"></i> Dự đoán điểm
            </a>
        </div>
        
        <div class="d-flex align-items-center">
            <span class="fw-bold me-3 text-dark d-none d-md-inline">
                <i class="fas fa-user-circle me-1"></i> <?php echo $mssv; ?>
            </span>
            <a href="login.php" class="btn btn-sm btn-outline-danger px-3" style="border-radius: 20px;">Thoát</a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="card p-4 mb-4">
        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-plus-circle me-2"></i>CẬP NHẬT ĐIỂM MỚI</h6>
        <form method="POST" class="row g-2">
            <div class="col-md-2"><input type="text" name="ma_hp" class="form-control" placeholder="Mã HP" required></div>
            <div class="col-md-4"><input type="text" name="ten_hp" class="form-control" placeholder="Tên môn học" required></div>
            <div class="col-md-1">
                <select name="so_tc" class="form-select" required>
                    <option value="">TC</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                    <option value="8">8</option>
                    <option value="10">10</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="loai_hp" class="form-select">
                    <option value="ChuyenNganh">Chuyên ngành</option>
                    <option value="DieuKien">Điều kiện</option>
                </select>
            </div>
            <div class="col-md-1">
                <select name="diem_4" class="form-select" required>
                    <option value="">Điểm</option>
                    <option value="4.0">A</option>
                    <option value="3.5">B+</option>
                    <option value="3.0">B</option>
                    <option value="2.5">C+</option>
                    <option value="2.0">C</option>
                    <option value="1.5">D+</option>
                    <option value="1.0">D</option>
                    <option value="0">F</option>
                </select>
            </div>
            <div class="col-md-2"><button name="btn_save" class="btn btn-save w-100 h-100">Lưu dữ liệu</button></div>
        </form>
        <div class="mt-3 small text-muted">
            <i class="fas fa-info-circle me-1 text-primary"></i> <strong>Lưu ý:</strong> "Anh văn", "Quốc phòng", "Thể dục" chọn <b>"Điều kiện"</b>. Các môn khác chọn <b>"Chuyên ngành"</b>.
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card p-4 h-100">
                <h5 class="fw-bold mb-4">Kết quả học tập</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Môn học</th>
                                <th class="text-center">Tín chỉ</th>
                                <th class="text-center">Phân loại</th>
                                <th class="text-center">Hệ 4</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = mysqli_query($conn, "SELECT b.id, b.diem_4, m.ten_hp, m.so_tc, m.loai_hp FROM bang_diem b JOIN mon_hoc m ON b.ma_hp = m.ma_hp WHERE b.mssv = '$mssv'");
                            if(mysqli_num_rows($res) == 0) echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Chưa có dữ liệu.</td></tr>";
                            
                            while($row = mysqli_fetch_assoc($res)) {
                                ?>
                                <tr>
                                    <td class='fw-bold text-dark'><?php echo $row['ten_hp']; ?></td>
                                    <td class='text-center'><?php echo $row['so_tc']; ?></td>
                                    <td class='text-center'><span class='badge bg-light text-dark border badge-loai'><?php echo ($row['loai_hp']=='DieuKien'?'Điều kiện':'Chuyên ngành'); ?></span></td>
                                    <td class='text-center fw-bold'>
                                        <span class="text-primary" style="font-size: 1.2rem;">
                                            <?php echo convertDiemSoSangChu($row['diem_4']); ?>
                                        </span>
                                        
                                        <span class="text-primary" style="font-size: 1.2rem;">
                                            (<?php echo number_format($row['diem_4'], 1); ?>)
                                        </span>
                                    </td>

                                  <td class='text-center'>
                                        <button class='btn btn-sm text-warning' data-bs-toggle='modal' data-bs-target='#edit<?php echo $row['id']; ?>'><i class='fas fa-edit'></i></button>
                                        <a href='index.php?delete_id=<?php echo $row['id']; ?>' class='btn btn-sm text-danger' onclick='return confirm("Xóa môn này?")'><i class='fas fa-trash'></i></a>
                                    </td>
                                </tr>

                                <div class='modal fade' id='edit<?php echo $row['id']; ?>' tabindex='-1' aria-hidden='true'>
                                  <div class='modal-dialog modal-dialog-centered'>
                                    <div class='modal-content' style='border-radius:15px;'>
                                      <form method='POST'>
                                        <div class='modal-header border-0'><h6 class='fw-bold m-0'>Chỉnh sửa học phần</h6><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div>
                                        <div class='modal-body pt-0'>
                                            <input type='hidden' name='id_edit' value='<?php echo $row['id']; ?>'>
                                            <div class='mb-2'><label class='small fw-bold'>Tên môn học:</label><input type='text' name='ten_moi' class='form-control' value='<?php echo $row['ten_hp']; ?>' required></div>
                                            <div class='row g-2 mb-2'>
                                            <div class='col-6'>
                                                <label class='small fw-bold'>Số tín chỉ:</label>
                                                <select name='tc_moi' class='form-select' required>
                                                    <?php 
                                                    $tc_options = [1, 2, 3, 4, 5, 8, 10];
                                                    foreach($tc_options as $tc_val) {
                                                        $selected = ($row['so_tc'] == $tc_val) ? 'selected' : '';
                                                        echo "<option value='$tc_val' $selected>$tc_val</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>           

                                             <div class='col-6'>
                                                    <label class='small fw-bold'>Điểm hệ 4:</label>
                                                    <select name='diem_4_moi' class='form-select text-primary fw-bold'>
                                                        <?php 
                                                        $diem_options = ['4.0'=>'A', '3.5'=>'B+', '3.0'=>'B', '2.5'=>'C+', '2.0'=>'C', '1.5'=>'D+', '1.0'=>'D', '0'=>'F'];
                                                        foreach($diem_options as $val => $lbl) {
                                                            $sel = ($row['diem_4'] == $val) ? 'selected' : '';
                                                            echo "<option value='$val' $sel>$lbl ($val)</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>                                            </div>
                                            <div><label class='small fw-bold'>Phân loại:</label><select name='loai_moi' class='form-select'><option value='ChuyenNganh' <?php echo ($row['loai_hp']=='ChuyenNganh'?'selected':''); ?>>Chuyên ngành</option><option value='DieuKien' <?php echo ($row['loai_hp']=='DieuKien'?'selected':''); ?>>Điều kiện</option></select></div>
                                        </div>
                                        <div class='modal-footer border-0'><button name='btn_update' class='btn btn-primary w-100 fw-bold'>LƯU THAY ĐỔI</button></div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="gpa-card mb-4 shadow-sm">
                <?php $gpa = tinhGPAChuan($mssv); ?>
                <p class="small text-uppercase mb-1 fw-bold opacity-75">GPA TÍCH LŨY</p>
                <div class="gpa-val"><?php echo $gpa; ?></div>
                <p class="fw-bold mt-2 mb-0">Xếp loại: 
                    <?php 
                    if($gpa >= 3.6) echo "Xuất sắc"; elseif($gpa >= 3.2) echo "Giỏi"; elseif($gpa >= 2.5) echo "Khá"; elseif($gpa >= 2.0) echo "Trung bình"; else echo "Yếu/Kém";
                    ?>
                </p>
            </div>

            <div class="card p-4 shadow-sm">
                <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">ĐIỀU KIỆN TỐT NGHIỆP</h6>
                <?php
                                    // --- LOGIC KIỂM TRA THEO QUY CHẾ 3266/QĐ-ĐHCT ---

                // 1. Tín chỉ chuyên ngành (Chỉ tính môn có điểm >= 1.0)
                $sql_tc = "SELECT SUM(m.so_tc) as tong_tc FROM bang_diem b JOIN mon_hoc m ON b.ma_hp = m.ma_hp 
                        WHERE b.mssv = '$mssv' AND m.loai_hp != 'DieuKien' AND b.diem_4 >= 1.0";
                $tong_tc = mysqli_fetch_assoc(mysqli_query($conn, $sql_tc))['tong_tc'] ?? 0;
                $gpa = tinhGPAChuan($mssv); 
                $check_gpa_tot_nghiep = ($gpa >= 2.0); // Điều 32, mục 1a

                // 2. Kiểm tra khối GDQP&AN (Phải đạt từ 5.0 trở lên hệ 10)
                $res_qp = mysqli_query($conn, "SELECT SUM(m.so_tc) as tc, AVG(b.diem_4 * 2.5) as avg_10 FROM bang_diem b JOIN mon_hoc m ON b.ma_hp = m.ma_hp WHERE b.mssv = '$mssv' AND b.ma_hp LIKE 'QP%' AND b.diem_4 >= 1.0");
                $row_qp = mysqli_fetch_assoc($res_qp);
                $tc_qp = $row_qp['tc'] ?? 0;
                $avg_qp_10 = $row_qp['avg_10'] ?? 0;
                $check_qp = ($tc_qp >= 8 && $avg_qp_10 >= 5.0); // Điều 32, mục 1b

                // 3. Thể dục (Mã TC)
                $tc_td = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(m.so_tc) as tc FROM bang_diem b JOIN mon_hoc m ON b.ma_hp = m.ma_hp WHERE b.mssv = '$mssv' AND b.ma_hp LIKE 'TC%' AND b.diem_4 >= 1.0"))['tc'] ?? 0;
                $check_td = ($tc_td >= 3); 

                // 4. Anh văn (Mã XH) - ĐÂY LÀ CHỖ BỊ LỖI NÈ
                $tc_av = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(m.so_tc) as tc FROM bang_diem b JOIN mon_hoc m ON b.ma_hp = m.ma_hp WHERE b.mssv = '$mssv' AND b.ma_hp LIKE 'XH%' AND b.diem_4 >= 1.0"))['tc'] ?? 0;
                $da_co_b1 = $_SESSION['dat_b1_'.$mssv] ?? false;
                $check_av = ($da_co_b1 || $tc_av >= 10); // Đạt nếu có B1 hoặc đủ 10 chỉ

                // TỔNG HỢP
                $du_dieu_kien = ($tong_tc >= 140 && $check_gpa_tot_nghiep && $check_qp && $check_td && $check_av);
                                    ?>

                <div class="mb-3 small">
                    <div class="d-flex justify-content-between"><b>1. Tín chỉ tích lũy:</b> <span><?php echo $tong_tc; ?>/140</span></div>
                    <div class="progress mt-1" style="height: 8px;"><div class="progress-bar bg-success" style="width: <?php echo min(($tong_tc/140)*100, 100); ?>%"></div></div>
                </div>

                <div class="mb-3 small">
                    <b>2. Điểm sàn (GPA >= 2.0):</b>
                    <div class="mt-1 p-2 border rounded <?php echo ($gpa >= 2.0) ? 'text-success' : 'text-danger'; ?> fw-bold">
                        <i class="fas <?php echo ($gpa >= 2.0) ? 'fa-check-circle' : 'fa-times-circle'; ?> me-2"></i><?php echo ($gpa >= 2.0) ? "Đạt chuẩn ($gpa)" : "Chưa đạt ($gpa)"; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <b class="small">3. Môn điều kiện:</b>
                    <div class="p-2 border rounded bg-light mt-1">
                        <div class="d-flex justify-content-between align-items-center mb-1 small"><span>Quốc phòng (<?php echo $tc_qp; ?>/8 chỉ)</span><i class="fas <?php echo $check_qp ? 'fa-check text-success' : 'fa-times text-danger'; ?>"></i></div>
                        <div class="d-flex justify-content-between align-items-center mb-1 small"><span>Thể dục (<?php echo $tc_td; ?>/3 chỉ)</span><i class="fas <?php echo $check_td ? 'fa-check text-success' : 'fa-times text-danger'; ?>"></i></div>
                        <div class="d-flex justify-content-between align-items-center small"><span>Anh văn (<?php echo $tc_av; ?>/10 chỉ hoặc B1)</span><i class="fas <?php echo $check_av ? 'fa-check text-success' : 'fa-times text-danger'; ?>"></i></div>
                    </div>
                </div>

                <form method="POST" class="mb-3">
                    <input type="hidden" name="update_b1" value="1">
                    <div class="form-check form-switch p-2 border rounded bg-white shadow-sm small" style="padding-left: 2.5rem !important;">
                        <input class="form-check-input" type="checkbox" name="has_b1" id="swB1" onchange="this.form.submit()" <?php echo $da_co_b1 ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="swB1">Đã có chứng chỉ B1 (tương đương)</label>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <?php if ($du_dieu_kien): ?>
                        <div class="alert alert-success border-0 py-2 small fw-bold"><i class="fas fa-check-double me-2"></i>ĐỦ ĐIỀU KIỆN TỐT NGHIỆP</div>
                    <?php else: ?>
                        <div class="alert alert-danger border-0 py-2 small fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>CHƯA ĐỦ ĐIỀU KIỆN TỐT NGHIỆP</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>