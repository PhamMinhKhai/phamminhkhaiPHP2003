<?php
session_start();
require_once 'config.php';
include 'header.php';

// Check if ID parameter exists
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: home.php");
    exit();
}

$maSV = $_GET['id'];

// Get student data with major name
$sql = "SELECT sv.*, nh.TenNganh 
        FROM SinhVien sv 
        LEFT JOIN NganhHoc nh ON sv.MaNganh = nh.MaNganh 
        WHERE sv.MaSV = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $maSV);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    $_SESSION['error'] = "Không tìm thấy sinh viên!";
    header("Location: home.php");
    exit();
}

$student = $result->fetch_assoc();

// Get registered courses
$sqlCourses = "SELECT hp.MaHP, hp.TenHP, hp.SoTinChi, dk.NgayDK
               FROM DangKy dk
               JOIN ChiTietDangKy ctdk ON dk.MaDK = ctdk.MaDK
               JOIN HocPhan hp ON ctdk.MaHP = hp.MaHP
               WHERE dk.MaSV = ?
               ORDER BY dk.NgayDK DESC";
$stmtCourses = $conn->prepare($sqlCourses);
$stmtCourses->bind_param("s", $maSV);
$stmtCourses->execute();
$resultCourses = $stmtCourses->get_result();
?>

<div class="card">
    <div class="card-header">
        <h2>Thông Tin Chi Tiết Sinh Viên</h2>
    </div>
    <div class="card-body">
        <a href="home.php" class="btn btn-secondary mb-4">Quay Lại</a>
        
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <?php if(!empty($student['Hinh'])): ?>
                    <img src="<?php echo $student['Hinh']; ?>" class="img-fluid rounded" style="max-height: 300px;" alt="<?php echo $student['HoTen']; ?>">
                <?php else: ?>
                    <div class="p-5 bg-light text-center">
                        <span>Không có hình</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-8">
                <table class="table table-bordered">
                    <tr>
                        <th width="30%">Mã Sinh Viên</th>
                        <td><?php echo $student['MaSV']; ?></td>
                    </tr>
                    <tr>
                        <th>Họ Tên</th>
                        <td><?php echo $student['HoTen']; ?></td>
                    </tr>
                    <tr>
                        <th>Giới Tính</th>
                        <td><?php echo $student['GioiTinh']; ?></td>
                    </tr>
                    <tr>
                        <th>Ngày Sinh</th>
                        <td><?php echo date('d/m/Y', strtotime($student['NgaySinh'])); ?></td>
                    </tr>
                    <tr>
                        <th>Ngành Học</th>
                        <td><?php echo $student['TenNganh']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <h3 class="mt-4">Học Phần Đã Đăng Ký</h3>
        <?php if($resultCourses->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Mã Học Phần</th>
                        <th>Tên Học Phần</th>
                        <th>Số Tín Chỉ</th>
                        <th>Ngày Đăng Ký</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($course = $resultCourses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $course['MaHP']; ?></td>
                            <td><?php echo $course['TenHP']; ?></td>
                            <td><?php echo $course['SoTinChi']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($course['NgayDK'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">Sinh viên chưa đăng ký học phần nào.</div>
        <?php endif; ?>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>