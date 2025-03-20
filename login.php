<?php
session_start();
require_once 'config.php';

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $maSV = $_POST['maSV'];
    $ngaySinh = $_POST['ngaySinh'];
    
    // Check credentials
    $sql = "SELECT * FROM SinhVien WHERE MaSV = ? AND NgaySinh = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $maSV, $ngaySinh);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        $_SESSION['user_id'] = $student['MaSV'];
        $_SESSION['user_name'] = $student['HoTen'];
        $_SESSION['message'] = "Đăng nhập thành công!";
        header("Location: home.php");
        exit();
    } else {
        $error = "Mã sinh viên hoặc ngày sinh không chính xác!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Website Đăng Ký Học Phần</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .card-body {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-login {
            background-color: #007bff;
            border-color: #007bff;
            width: 100%;
            padding: 10px;
        }
        .btn-login:hover {
            background-color: #0069d9;
            border-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="login-card card">
        <div class="card-header">
            <h2>Đăng Nhập</h2>
            <p class="mb-0">Website Đăng Ký Học Phần</p>
        </div>
        <div class="card-body">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="maSV"><strong>Mã Sinh Viên:</strong></label>
                    <input type="text" class="form-control" id="maSV" name="maSV" required>
                </div>
                
                <div class="form-group">
                    <label for="ngaySinh"><strong>Ngày Sinh:</strong></label>
                    <input type="date" class="form-control" id="ngaySinh" name="ngaySinh" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">Đăng Nhập</button>
            </form>
            
            <div class="text-center mt-3">
                <a href="index.php">Quay lại trang chủ</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>