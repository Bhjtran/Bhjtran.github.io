<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập hệ thống - CTU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-login { width: 400px; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-guest { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="card card-login p-4">
        <h3 class="text-center fw-bold text-primary mb-4">CTU SCORE</h3>
        <form action="xuly_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">MSSV</label>
                <input type="text" name="mssv" class="form-control" placeholder="Ví dụ: B2306595" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="******" required>
            </div>
            <button type="submit" name="btn_login" class="btn btn-primary w-100 py-2 fw-bold">ĐĂNG NHẬP</button>
        </form>
        
   
    </div>
</body>
</html>