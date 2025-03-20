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

// Get all majors for dropdown
$sqlMajors = "SELECT * FROM NganhHoc ORDER BY TenNganh";
$majors = $conn->query($sqlMajors);

// Get student data
$sql = "SELECT * FROM SinhVien WHERE MaSV = ?";
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

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hoTen = $_POST['hoTen'];
    $gioiTinh = $_POST['gioiTinh'];
    $ngaySinh = $_POST['ngaySinh'];
    $maNganh = $_POST['maNganh'];
    $oldHinh = $student['Hinh'];
    
    // Handle file upload
    if(!empty($_FILES["hinh"]["name"])) {
        $targetDir = "Content/images/";
        $fileName = basename($_FILES["hinh"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        // Check if directory exists, if not create it
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if(in_array($fileType, $allowTypes)){
            // Upload file to server
            if(move_uploaded_file($_FILES["hinh"]["tmp_name"], $targetFilePath)){
                $hinhPath = "/" . $targetFilePath;
                
                // Update student data with new image
                $sql = "UPDATE SinhVien SET HoTen=?, GioiTinh=?, NgaySinh=?, Hinh=?, MaNganh=? WHERE MaSV=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $hoTen, $gioiTinh, $ngaySinh, $hinhPath, $maNganh, $maSV);
            } else {
                $_SESSION['error'] = "Lỗi khi tải lên hình ảnh!";
            }
        } else {
            $_SESSION['error'] = "Chỉ cho phép các định dạng JPG, JPEG, PNG & GIF!";
        }
    } else {
        // Update without changing image
        $sql = "UPDATE SinhVien SET HoTen=?, GioiTinh=?, NgaySinh=?, MaNganh=? WHERE MaSV=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $hoTen, $gioiTinh, $ngaySinh, $maNganh, $maSV);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Cập nhật sinh viên thành công!";
        header("Location: home.php");
        exit();
    } else {
        $_SESSION['error'] = "Lỗi: " . $stmt->error;
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Sửa Thông Tin Sinh Viên</h2>
    </div>
    <div class="card-body">
        <a href="home.php" class="btn btn-secondary mb-3">Quay Lại</a>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $maSV; ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="maSV">Mã Sinh Viên:</label>
                <input type="text" class="form-control" id="maSV" value="<?php echo $student['MaSV']; ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="hoTen">Họ Tên:</label>
                <input type="text" class="form-control" id="hoTen" name="hoTen" value="<?php echo $student['HoTen']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Giới Tính:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gioiTinh" id="nam" value="Nam" <?php echo ($student['GioiTinh'] == 'Nam') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="nam">Nam</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gioiTinh" id="nu" value="Nữ" <?php echo ($student['GioiTinh'] == 'Nữ') ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="nu">Nữ</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="ngaySinh">Ngày Sinh:</label>
                <input type="date" class="form-control" id="ngaySinh" name="ngaySinh" value="<?php echo $student['NgaySinh']; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="hinh">Hình Hiện Tại:</label>
                <?php if(!empty($student['Hinh'])): ?>
                    <img src="<?php echo $student['Hinh']; ?>" class="d-block mb-2 student-image" alt="<?php echo $student['HoTen']; ?>">
                <?php else: ?>
                    <span>Không có hình</span>
                <?php endif; ?>
                <input type="file" class="form-control-file mt-2" id="hinh" name="hinh">
                <small class="form-text text-muted">Để trống nếu không muốn thay đổi hình ảnh.</small>
            </div>
            
            <div class="form-group">
                <label for="maNganh">Ngành Học:</label>
                <select class="form-control" id="maNganh" name="maNganh" required>
                    <?php 
                    // Reset the result pointer
                    $majors->data_seek(0);
                    while($row = $majors->fetch_assoc()): ?>
                        <option value="<?php echo $row['MaNganh']; ?>" <?php echo ($student['MaNganh'] == $row['MaNganh']) ? 'selected' : ''; ?>>
                            <?php echo $row['TenNganh']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Cập Nhật</button>
        </form>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>