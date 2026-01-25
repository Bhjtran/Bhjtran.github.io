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

// 1. LẤY DỮ LIỆU
function getStats($mssv, $conn) {
    $sql = "SELECT b.id, m.so_tc, b.diem_4, m.ten_hp, m.loai_hp 
            FROM bang_diem b 
            JOIN mon_hoc m ON b.ma_hp = m.ma_hp 
            WHERE b.mssv = '$mssv'";
    $res = mysqli_query($conn, $sql);
    $tong_diem = 0; $tong_tc = 0; $list = [];
    while($row = mysqli_fetch_assoc($res)) {
        if($row['loai_hp'] != 'DieuKien') {
            $tong_diem += (float)$row['diem_4'] * (int)$row['so_tc'];
            $tong_tc += (int)$row['so_tc'];
        }
        $list[] = $row;
    }
    return ['gpa' => ($tong_tc > 0 ? $tong_diem / $tong_tc : 0), 'tc' => $tong_tc, 'cum' => $tong_diem, 'list' => $list];
}

// 2. THUẬT TOÁN BÓC TÁCH TÍN CHỈ
function solveCreditsDetail($diem_can_dat, $tc_con_lai) {
    if ($diem_can_dat > 4.0) return null;
    $diem_target_tong = $diem_can_dat * $tc_con_lai;
    for ($tc_A = 0; $tc_A <= $tc_con_lai; $tc_A++) {
        $tc_con_lai_sau_A = $tc_con_lai - $tc_A;
        for ($tc_B_plus = 0; $tc_B_plus <= $tc_con_lai_sau_A; $tc_B_plus++) {
            $tc_B = $tc_con_lai_sau_A - $tc_B_plus;
            if (($tc_A * 4.0) + ($tc_B_plus * 3.5) + ($tc_B * 3.0) >= $diem_target_tong) {
                return ['A' => $tc_A, 'B_plus' => $tc_B_plus, 'B' => $tc_B];
            }
        }
    }
    return ['A' => $tc_con_lai, 'B_plus' => 0, 'B' => 0];
}

$real = getStats($mssv, $conn);
$target = $_POST['target'] ?? ""; 
$input_cai_thien = $_POST['cai_thien'] ?? [];

$temp_cum = $real['cum'];
foreach ($input_cai_thien as $id => $diem_moi) {
    if ($diem_moi !== "") {
        foreach ($real['list'] as $m) {
            if ($m['id'] == $id) {
                $temp_cum = $temp_cum - ((float)$m['diem_4'] * (int)$m['so_tc']) + ((float)$diem_moi * (int)$m['so_tc']);
            }
        }
    }
}
$gpa_ao = ($real['tc'] > 0) ? ($temp_cum / $real['tc']) : 0;

function suggestStrategies($current_cum, $current_tc, $target_gpa, $list_mon) {
    $tc_ra_truong = 140; 
    $tc_con = $tc_ra_truong - $current_tc;
    $diem_target = $target_gpa * $tc_ra_truong;
    $strategies = [];
    $bad = array_values(array_filter($list_mon, function($m) { return $m['diem_4'] < 2.5; }));
    usort($bad, function($a, $b) { return $a['diem_4'] <=> $b['diem_4']; });

    $avg1 = ($diem_target - $current_cum) / $tc_con;
    $strategies[] = ['tag' => 'Siêu nhân', 'color' => 'danger', 'todo' => 'Giữ nguyên bảng điểm.', 'avg' => round($avg1, 2), 'detail' => solveCreditsDetail($avg1, $tc_con)];

    if (count($bad) >= 1) {
        $new_cum = $current_cum - ($bad[0]['diem_4'] * $bad[0]['so_tc']) + (4.0 * $bad[0]['so_tc']);
        $avg2 = ($diem_target - $new_cum) / $tc_con;
        $strategies[] = ['tag' => 'Cân bằng', 'color' => 'warning', 'todo' => 'Cải thiện môn: '.$bad[0]['ten_hp'], 'avg' => round($avg2, 2), 'detail' => solveCreditsDetail($avg2, $tc_con)];
    }

    if (count($bad) >= 2) {
        $new_cum = $current_cum - ($bad[0]['diem_4']*$bad[0]['so_tc'] + $bad[1]['diem_4']*$bad[1]['so_tc']) + (4.0*($bad[0]['so_tc'] + $bad[1]['so_tc']));
        $avg3 = ($diem_target - $new_cum) / $tc_con;
        $strategies[] = ['tag' => 'An toàn', 'color' => 'success', 'todo' => 'Học lại: '.$bad[0]['ten_hp'].' & '.$bad[1]['ten_hp'], 'avg' => round($avg3, 2), 'detail' => solveCreditsDetail($avg3, $tc_con)];
    }
    return $strategies;
}

$ai_options = ($target != "") ? suggestStrategies($temp_cum, $real['tc'], (float)$target, $real['list']) : [];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>DỰ TOÁN GPA - CTU SCORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --primary-blue: #0061f2; --dark-blue: #1b2a4e; }
        body { background-color: #f2f5f9; font-family: 'Inter', sans-serif; color: var(--dark-blue); }
        
        /* Đồng bộ nút Quay về */
        .btn-back { 
            display: inline-flex; align-items: center; justify-content: center;
            background: #fff; color: var(--dark-blue); font-weight: 600;
            padding: 10px 20px; border-radius: 50px; border: 1px solid #ddd;
            transition: 0.3s; text-decoration: none;
        }
        .btn-back:hover { background: #f8f9fa; transform: translateX(-5px); color: var(--primary-blue); }

        .card { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .bg-gradient-blue { background: linear-gradient(135deg, #0061f2 0%, #00cfd5 100%); color: white; }
        
        /* Đồng bộ Table và Form */
        .form-label { font-weight: 700; font-size: 0.85rem; color: #69707a; text-transform: uppercase; }
        .table thead { background-color: #f8f9fc; color: #4e73df; font-size: 0.8rem; text-transform: uppercase; }
        
        /* Cân bằng Card AI */
        .strategy-card { border-top: 5px solid; transition: 0.3s; height: 100%; display: flex; flex-direction: column; }
        .strategy-card:hover { transform: translateY(-5px); }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #edf2f9; font-size: 0.9rem; }
        .tc-val { font-weight: 800; color: var(--primary-blue); }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="index.php" class="btn-back shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Quay về bảng điểm
        </a>
        <h4 class="fw-bold m-0 text-primary"><i class="fas fa-robot me-2"></i>AI STRATEGY PRO</h4>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card p-4 mb-4">
                <form method="POST">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Mục tiêu GPA ra trường</label>
                            <input type="number" step="0.01" name="target" class="form-control form-control-lg border-2" value="<?= $target ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Thêm môn học cải thiện</label>
                            <div class="input-group input-group-lg">
                                <select id="select-hp" class="form-select">
                                    <option value="">-- Chọn môn --</option>
                                    <?php foreach($real['list'] as $m): ?>
                                        <option value="<?= $m['id'] ?>" data-ten="<?= $m['ten_hp'] ?>" data-tc="<?= $m['so_tc'] ?>" data-goc="<?= $m['diem_4'] ?>">
                                            <?= $m['ten_hp'] ?> (<?= $m['diem_4'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="addSubject()" class="btn btn-primary px-3"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>

                    <table class="table align-middle">
                        <thead><tr><th>Học phần</th><th class="text-center">TC</th><th class="text-center">Dự kiến</th><th></th></tr></thead>
                        <tbody id="sim-list">
                            <?php foreach($input_cai_thien as $id => $val): 
                                foreach($real['list'] as $m) { if($m['id'] == $id) { ?>
                                    <tr id="r-<?= $id ?>">
                                        <td class="small fw-bold text-dark"><?= $m['ten_hp'] ?></td>
                                        <td class="text-center small"><?= $m['so_tc'] ?> TC</td>
                                        <td width="100"><input type="number" step="0.1" name="cai_thien[<?= $id ?>]" class="form-control form-control-sm text-center fw-bold border-primary text-primary" value="<?= $val ?>"></td>
                                        <td class="text-end"><button type="button" onclick="this.closest('tr').remove()" class="btn btn-link text-danger p-0"><i class="fas fa-times-circle"></i></button></td>
                                    </tr>
                            <?php } } endforeach; ?>
                        </tbody>
                    </table>
                    <button class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-lg mt-3">XÁC NHẬN PHÂN TÍCH LỘ TRÌNH</button>
                </form>
            </div>

            <div class="row g-3 align-items-stretch">
                <?php foreach($ai_options as $opt): ?>
                <div class="col-md-4">
                    <div class="card strategy-card border-<?= $opt['color'] ?> p-3 shadow-sm">
                        <span class="badge bg-<?= $opt['color'] ?> mb-3 rounded-pill"><?= $opt['tag'] ?></span>
                        <div class="small text-muted mb-3 flex-grow-1" style="min-height: 45px;">
                            <i class="fas fa-info-circle me-1"></i> <?= $opt['todo'] ?>
                        </div>
                        
                        <div class="text-center border-top border-bottom py-3 mb-3">
                            <small class="text-muted d-block small uppercase mb-1">GPA môn mới cần đạt</small>
                            <h3 class="fw-bold <?= $opt['avg'] > 4 ? 'text-danger' : 'text-dark' ?> m-0"><?= $opt['avg'] > 4 ? '!' : $opt['avg'] ?></h3>
                        </div>

                        <?php if($opt['detail']): ?>
                            <div>
                                <div class="detail-row"><span>Điểm A:</span><span class="tc-val"><?= $opt['detail']['A'] ?> TC</span></div>
                                <div class="detail-row"><span>Điểm B+:</span><span class="tc-val"><?= $opt['detail']['B_plus'] ?> TC</span></div>
                                <div class="detail-row" style="border:none"><span>Điểm B:</span><span class="tc-val"><?= $opt['detail']['B'] ?> TC</span></div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger py-1 small m-0">Không khả thi</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

       <div class="col-lg-5">
    <div class="card bg-gradient-blue p-4 text-center mb-4 shadow-lg border-0">
        <h6 class="text-white-50 small fw-bold text-uppercase mb-2" style="letter-spacing: 1px;">GPA Dự Toán (Giả sử cải thiện)</h6>
        <h1 class="display-1 fw-bold m-0 text-white"><?= number_format($gpa_ao, 2) ?></h1>
        
        <div class="mt-4 p-3 bg-white bg-opacity-10 rounded-4 shadow-inner">
            <div class="row g-0">
                <div class="col-4 border-end border-white border-opacity-25">
                    <small class="d-block text-white-50" style="font-size: 0.7rem;">GPA HIỆN TẠI</small>
                    <span class="fw-bold fs-5 text-white"><?= number_format($real['gpa'], 2) ?></span>
                </div>
                <div class="col-4 border-end border-white border-opacity-25">
                    <small class="d-block text-white-50" style="font-size: 0.7rem;">TÍN CHỈ GỐC</small>
                    <span class="fw-bold fs-5 text-white"><?= $real['tc'] ?></span>
                </div>
                <div class="col-4">
                    <small class="d-block text-white-50" style="font-size: 0.7rem;">CẢI THIỆN</small>
                    <span class="fw-bold fs-5 text-warning">+<?= number_format(max(0, $gpa_ao - $real['gpa']), 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-4 border-0 shadow-sm">
        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-robot me-2"></i>Phân tích từ AI</h6>
        <p class="small text-muted mb-0">
            Dựa trên <strong><?= $real['tc'] ?></strong> tín chỉ tích lũy, AI đã mô phỏng việc nâng điểm các môn bạn chọn lên mức dự kiến. 
            <?php if($target): ?>
                Để đạt mục tiêu <strong><?= $target ?></strong>, bạn cần tập trung vào lộ trình <strong><?= $ai_options[0]['tag'] ?? '' ?></strong> bên dưới.
            <?php else: ?>
                Hãy nhập mục tiêu GPA để AI tính toán số lượng điểm A, B+ bạn cần đạt được trong tương lai.
            <?php endif; ?>
        </p>
    </div>
</div>
    </div>
</div>

<script>
function addSubject() {
    const s = document.getElementById('select-hp');
    const o = s.options[s.selectedIndex];
    if (!o.value || document.getElementById('r-' + o.value)) return;
    const row = `<tr id="r-${o.value}">
        <td class="small fw-bold text-dark">${o.dataset.ten}</td>
        <td class="text-center small">${o.dataset.tc} TC</td>
        <td width="100"><input type="number" step="0.1" name="cai_thien[${o.value}]" class="form-control form-control-sm text-center fw-bold border-primary text-primary" value="4.0"></td>
        <td class="text-end"><button type="button" onclick="this.closest('tr').remove()" class="btn btn-link text-danger p-0"><i class="fas fa-times-circle"></i></button></td>
    </tr>`;
    document.getElementById('sim-list').insertAdjacentHTML('beforeend', row);
}
</script>
</body>
</html>