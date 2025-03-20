<?php
session_start();
require_once 'config.php';
include 'header.php';

// Get all students
$sql = "SELECT sv.*, nh.TenNganh 
        FROM SinhVien sv 
        LEFT JOIN NganhHoc nh ON sv.MaNganh = nh.MaNganh 
        ORDER BY sv.MaSV";
$result = $conn->query($sql);
?>

<div class="card">
    <div class="card-header">
        <h2>Danh Sách Sinh Viên</h2>
    </div>
    <div class="card-body">
        <a href="create.php" class="btn btn-success mb-3">Thêm Sinh Viên</a>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>
        
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã SV</th>
                    <th>Họ Tên</th>
                    <th>Giới Tính</th>
                    <th>Ngày Sinh</th>
                    <th>Hình</th>
                    <th>Ngành Học</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['MaSV']; ?></td>
                            <td><?php echo $row['HoTen']; ?></td>
                            <td><?php echo $row['GioiTinh']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['NgaySinh'])); ?></td>
                            <td>
                                <?php if(!empty($row['Hinh'])): ?>
                                    <img src="<?php echo $row['Hinh']; ?>" class="student-image" alt="<?php echo $row['HoTen']; ?>">
                                <?php else: ?>
                                    <span>Không có hình</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['TenNganh']; ?></td>
                            <td>
                                <a href="detail.php?id=<?php echo $row['MaSV']; ?>" class="btn btn-info btn-sm">Chi Tiết</a>
                                <a href="edit.php?id=<?php echo $row['MaSV']; ?>" class="btn btn-primary btn-sm">Sửa</a>
                                <a href="delete.php?id=<?php echo $row['MaSV']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sinh viên này?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Không có dữ liệu sinh viên</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>