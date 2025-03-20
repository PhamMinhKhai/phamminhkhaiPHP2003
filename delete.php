<?php
session_start();
require_once 'config.php';

// Check if ID parameter exists
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$maSV = $_GET['id'];

// Lấy thông tin sinh viên trước khi xóa để hiển thị
$infoSql = "SELECT * FROM SinhVien WHERE MaSV = ?";
$infoStmt = $conn->prepare($infoSql);
$infoStmt->bind_param("s", $maSV);
$infoStmt->execute();
$infoResult = $infoStmt->get_result();
$sinhVien = $infoResult->fetch_assoc();

// Nếu đã xác nhận xóa
if(isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes') {
    // Check if student has registrations
    $checkSql = "SELECT * FROM DangKy WHERE MaSV = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $maSV);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Student has registrations, delete them first
        while ($row = $checkResult->fetch_assoc()) {
            $maDK = $row['MaDK'];
            
            // Delete registration details
            $delDetailSql = "DELETE FROM ChiTietDangKy WHERE MaDK = ?";
            $delDetailStmt = $conn->prepare($delDetailSql);
            $delDetailStmt->bind_param("i", $maDK);
            $delDetailStmt->execute();
            
            // Delete registration
            $delRegSql = "DELETE FROM DangKy WHERE MaDK = ?";
            $delRegStmt = $conn->prepare($delRegSql);
            $delRegStmt->bind_param("i", $maDK);
            $delRegStmt->execute();
        }
    }

    // Now delete the student
    $sql = "DELETE FROM SinhVien WHERE MaSV = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $maSV);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Xóa sinh viên thành công!";
    } else {
        $_SESSION['error'] = "Lỗi khi xóa sinh viên: " . $stmt->error;
    }

    header("Location: home.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận xóa sinh viên</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 50px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .confirm-card {
            max-width: 550px;
            margin: 0 auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            background-color: #dc3545;
            color: white;
            font-weight: bold;
            padding: 15px 20px;
        }
        .card-body {
            padding: 25px;
        }
        .student-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #6c757d;
        }
        .btn-outline-secondary {
            border-color: #6c757d;
            color: #6c757d;
        }
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .warning-icon {
            font-size: 24px;
            margin-right: 8px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card confirm-card">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-exclamation-triangle warning-icon">⚠️</i>
                <span class="ms-2">Xác nhận xóa sinh viên</span>
            </div>
            <div class="card-body">
                <?php if($sinhVien): ?>
                    <div class="alert alert-warning">
                        <strong>Cảnh báo:</strong> Bạn đang chuẩn bị xóa sinh viên. Hành động này không thể hoàn tác!
                    </div>
                    
                    <div class="student-info">
                        <h5 class="mb-3">Thông tin sinh viên:</h5>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Mã sinh viên:</strong></div>
                            <div class="col-md-8"><?php echo htmlspecialchars($sinhVien['MaSV']); ?></div>
                        </div>
                        <?php if(isset($sinhVien['HoTen'])): ?>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Họ tên:</strong></div>
                            <div class="col-md-8"><?php echo htmlspecialchars($sinhVien['HoTen']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($sinhVien['Email'])): ?>
                        <div class="row mb-2">
                            <div class="col-md-4"><strong>Email:</strong></div>
                            <div class="col-md-8"><?php echo htmlspecialchars($sinhVien['Email']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($sinhVien['Lop'])): ?>
                        <div class="row">
                            <div class="col-md-4"><strong>Lớp:</strong></div>
                            <div class="col-md-8"><?php echo htmlspecialchars($sinhVien['Lop']); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" class="mt-4">
                        <input type="hidden" name="confirm_delete" value="yes">
                        <div class="d-flex justify-content-between">
                            <a href="home.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash"></i> Xác nhận xóa
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        Không tìm thấy thông tin sinh viên!
                    </div>
                    <div class="text-center">
                        <a href="home.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>