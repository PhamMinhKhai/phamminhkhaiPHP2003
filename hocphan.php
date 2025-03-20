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

// Get available courses
$sqlCourses = "SELECT * FROM HocPhan ORDER BY MaHP";
$resultCourses = $conn->query($sqlCourses);

// Add to cart functionality
if (isset($_POST['add_to_cart']) && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];
    
    // Initialize cart array if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }
    
    // Check if course is already in cart
    if (!in_array($courseId, $_SESSION['cart'])) {
        $_SESSION['cart'][] = $courseId;
        $_SESSION['success'] = "Đã thêm học phần vào danh sách đăng ký!";
    } else {
        $_SESSION['error'] = "Học phần này đã có trong danh sách đăng ký!";
    }
    
    // Refresh the page to show updated cart
    header("Location: hocphan.php" . (isset($_GET['student_id']) ? "?student_id=" . $_GET['student_id'] : ""));
    exit();
}

// View cart functionality
if (isset($_GET['view_cart'])) {
    header("Location: dangky_cart.php" . (isset($_GET['student_id']) ? "?student_id=" . $_GET['student_id'] : ""));
    exit();
}
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h2>Đăng Ký Học Phần</h2>
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
        
        <div class="d-flex justify-content-between mb-3">
            <h4>Danh Sách Học Phần</h4>
            <div>
                <form method="get" class="d-inline">
                    <input type="hidden" name="view_cart" value="1">
                    <?php if(isset($_GET['student_id'])): ?>
                        <input type="hidden" name="student_id" value="<?php echo $_GET['student_id']; ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-shopping-cart"></i> Xem Danh Sách Đăng Ký
                        <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            (<?php echo count($_SESSION['cart']); ?>)
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>
        
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
                    <?php if ($resultCourses->num_rows > 0): ?>
                        <?php while($course = $resultCourses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $course['MaHP']; ?></td>
                                <td><?php echo $course['TenHP']; ?></td>
                                <td><?php echo $course['SoTinChi']; ?></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="course_id" value="<?php echo $course['MaHP']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-success btn-sm">
                                            <i class="fa fa-plus"></i> Đăng Ký
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Không có học phần nào!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>