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

// Get registrations
$sqlRegistrations = "SELECT dk.MaDK, dk.NgayDK, COUNT(ctdk.MaHP) as SoHocPhan, 
                     SUM(hp.SoTinChi) as TongTinChi
                     FROM DangKy dk
                     JOIN ChiTietDangKy ctdk ON dk.MaDK = ctdk.MaDK
                     JOIN HocPhan hp ON ctdk.MaHP = hp.MaHP
                     WHERE dk.MaSV = ?
                     GROUP BY dk.MaDK, dk.NgayDK
                     ORDER BY dk.NgayDK DESC";
$stmtRegistrations = $conn->prepare($sqlRegistrations);
$stmtRegistrations->bind_param("s", $studentId);
$stmtRegistrations->execute();
$resultRegistrations = $stmtRegistrations->get_result();

// View details of a specific registration
$registrationDetails = null;
if (isset($_GET['view_details']) && !empty($_GET['view_details'])) {
    $maDK = $_GET['view_details'];
    
    $sqlDetails = "SELECT hp.MaHP, hp.TenHP, hp.SoTinChi
                  FROM ChiTietDangKy ctdk
                  JOIN HocPhan hp ON ctdk.MaHP = hp.MaHP
                  WHERE ctdk.MaDK = ?";
    $stmtDetails = $conn->prepare($sqlDetails);
    $stmtDetails->bind_param("i", $maDK);
    $stmtDetails->execute();
    $resultDetails = $stmtDetails->get_result();
    
    // Get registration date
    $sqlRegDate = "SELECT NgayDK FROM DangKy WHERE MaDK = ?";
    $stmtRegDate = $conn->prepare($sqlRegDate);
    $stmtRegDate->bind_param("i", $maDK);
    $stmtRegDate->execute();
    $resultRegDate = $stmtRegDate->get_result();
    $regDate = $resultRegDate->fetch_assoc();
    
    $registrationDetails = [
        'maDK' => $maDK,
        'ngayDK' => $regDate['NgayDK'],
        'courses' => []
    ];
    
    while ($course = $resultDetails->fetch_assoc()) {
        $registrationDetails['courses'][] = $course;
    }
}
?>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h2>Học Phần Đã Đăng Ký</h2>
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
        
        <a href="hocphan.php<?php echo isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>" class="btn btn-primary mb-3">
            <i class="fa fa-plus"></i> Đăng Ký Học Phần Mới
        </a>
        
        <?php if ($registrationDetails): ?>
            <!-- Registration Details View -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4>Chi Tiết Đăng Ký (Mã đăng ký: <?php echo $registrationDetails['maDK']; ?>)</h4>
                    <a href="dangky_info.php<?php echo isset($_GET['student_id']) ? '?student_id=' . $_GET['student_id'] : ''; ?>" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left"></i> Quay Lại Danh Sách
                    </a>
                </div>
                
                <div class="alert alert-secondary">
                    <strong>Ngày đăng ký:</strong> <?php echo date('d/m/Y', strtotime($registrationDetails['ngayDK'])); ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Mã Học Phần</th>
                                <th>Tên Học Phần</th>
                                <th>Số Tín Chỉ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrationDetails['courses'] as $course): ?>
                                <tr>
                                    <td><?php echo $course['MaHP']; ?></td>
                                    <td><?php echo $course['TenHP']; ?></td>
                                    <td><?php echo $course['SoTinChi']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right"><strong>Tổng số tín chỉ:</strong></td>
                                <td>
                                    <?php 
                                        $totalCredits = 0;
                                        foreach ($registrationDetails['courses'] as $course) {
                                            $totalCredits += $course['SoTinChi'];
                                        }
                                        echo $totalCredits;
                                    ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- Registrations List View -->
            <?php if ($resultRegistrations->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="thead-dark">
                            <tr>
                                <th>Mã Đăng Ký</th>
                                <th>Ngày Đăng Ký</th>
                                <th>Số Học Phần</th>
                                <th>Tổng Tín Chỉ</th>
                                <th width="150">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($registration = $resultRegistrations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $registration['MaDK']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($registration['NgayDK'])); ?></td>
                                    <td><?php echo $registration['SoHocPhan']; ?></td>
                                    <td><?php echo $registration['TongTinChi']; ?></td>
                                    <td>
                                        <a href="dangky_info.php?view_details=<?php echo $registration['MaDK']; ?><?php echo isset($_GET['student_id']) ? '&student_id=' . $_GET['student_id'] : ''; ?>" class="btn btn-info btn-sm">
                                            <i class="fa fa-eye"></i> Chi Tiết
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fa fa-info-circle"></i> Chưa có học phần nào được đăng ký!
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>