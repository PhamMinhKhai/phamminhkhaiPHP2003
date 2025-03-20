<?php
session_start();
require_once 'config.php';
include 'header.php';

// Check if user is logged in (you can implement proper login check)
// For now, we'll simulate by using session variable or set a default student ID
if (!isset($_SESSION['user_id'])) {
    // For demo purposes, you could redirect to login or use a default ID
    // Redirect to login page if authentication is required
    // header("Location: login.php");
    // exit();
    
    // Or use a default student ID for demonstration
    $studentId = isset($_GET['student_id']) ? $_GET['student_id'] : '0123456789';
} else {
    $studentId = $_SESSION['user_id'];
}

// Get student info
$sqlStudent = "SELECT * FROM SinhVien WHERE MaSV = ?";
$stmtStudent = $conn->prepare($sqlStudent);
$stmtStudent->bind_param("s", $studentId);
$stmtStudent->execute();
$resultStudent = $stmtStudent->get_result();
$student = $resultStudent->fetch_assoc();

// Remove from cart
if (isset($_POST['remove']) && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];
    
    if (isset($_SESSION['cart']) && in_array($courseId, $_SESSION['cart'])) {
        $key = array_search($courseId, $_SESSION['cart']);
        unset($_SESSION['cart'][$key]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
        $_SESSION['success'] = "Đã xóa học phần khỏi danh sách đăng ký!";
    }
    
    // Refresh the page
    header("Location: dangky_cart.php" . (isset($_GET['student_id']) ? "?student_id=" . $_GET['student_id'] : ""));
    exit();
}

// Remove all items from cart
if (isset($_POST['clear_cart'])) {
    unset($_SESSION['cart']);
    $_SESSION['success'] = "Đã xóa tất cả học phần khỏi danh sách đăng ký!";
    
    // Refresh the page
    header("Location: dangky_cart.php" . (isset($_GET['student_id']) ? "?student_id=" . $_GET['student_id'] : ""));
    exit();
}

// Save registration
if (isset($_POST['save_registration'])) {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into DangKy table
            $sqlDangKy = "INSERT INTO DangKy (NgayDK, MaSV) VALUES (?, ?)";
            $stmtDangKy = $conn->prepare($sqlDangKy);
            $currentDate = date('Y-m-d');
            $stmtDangKy->bind_param("ss", $currentDate, $studentId);
            $stmtDangKy->execute();
            
            // Get the registration ID
            $maDK = $conn->insert_id;
            
            // Insert into ChiTietDangKy table for each course
            $sqlChiTiet = "INSERT INTO ChiTietDangKy (MaDK, MaHP) VALUES (?, ?)";
            $stmtChiTiet = $conn->prepare($sqlChiTiet);
            
            foreach ($_SESSION['cart'] as $courseId) {
                $stmtChiTiet->bind_param("is", $maDK, $courseId);
                $stmtChiTiet->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear the cart
            unset($_SESSION['cart']);
            
            $_SESSION['success'] = "Đăng ký học phần thành công!";
            header("Location: dangky_info.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback in case of error
            $conn->rollback();
            $_SESSION['error'] = "Lỗi: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Chưa có học phần nào trong danh sách đăng ký!";
    }
    
    // Refresh the page
    header("Location: dangky_cart.php" . (isset($_GET['student_id']) ? "?student_id=" . $_GET['student_id'] : ""));
    exit();
}

// Get course details for items in cart
$cartCourses = [];
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $courseIds = implode("','", $_SESSION['cart']);
    $sqlCartCourses = "SELECT * FROM HocPhan WHERE MaHP IN ('$courseIds')";
    $resultCartCourses = $conn->query($sqlCartCourses);
    
    while ($course = $resultCartCourses->fetch_assoc()) {
        $cartCourses[] = $course;
    }
}
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h2>Danh Sách Học Phần Đăng Ký</h2>
    </div>
    <div class="card-body">
        <?php if (isset($student)): ?>
            <div class="alert alert-info">
                <strong>Sinh viên:</strong> <?php echo $student['HoTen']; ?> (<?php echo $student['MaSV']; ?>)
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <a href="hocphan.php<?php echo isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>" class="btn btn-secondary mb-3">
            <i class="fa fa-arrow-left"></i> Quay Lại Danh Sách Học Phần
        </a>
        
        <?php if (!empty($cartCourses)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Mã Học Phần</th>
                            <th>Tên Học Phần</th>
                            <th>Số Tín Chỉ</th>
                            <th width="150">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartCourses as $course): ?>
                            <tr>
                                <td><?php echo $course['MaHP']; ?></td>
                                <td><?php echo $course['TenHP']; ?></td>
                                <td><?php echo $course['SoTinChi']; ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="course_id" value="<?php echo $course['MaHP']; ?>">
                                        <button type="submit" name="remove" class="btn btn-danger btn-sm">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-right"><strong>Tổng số tín chỉ:</strong></td>
                            <td colspan="2">
                                <?php 
                                    $totalCredits = 0;
                                    foreach ($cartCourses as $course) {
                                        $totalCredits += $course['SoTinChi'];
                                    }
                                    echo $totalCredits;
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="d-flex justify-content-end mt-3">
                <form method="post" class="mr-2">
                    <button type="submit" name="clear_cart" class="btn btn-warning" onclick="return confirm('Bạn có chắc chắn muốn xóa tất cả học phần đã chọn?');">
                        <i class="fa fa-trash"></i> Xóa Tất Cả
                    </button>
                </form>
                
                <form method="post">
                    <button type="submit" name="save_registration" class="btn btn-success">
                        <i class="fa fa-save"></i> Lưu Đăng Ký
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fa fa-info-circle"></i> Chưa có học phần nào trong danh sách đăng ký!
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>