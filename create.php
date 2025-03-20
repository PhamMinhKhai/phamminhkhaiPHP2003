<?php
session_start();
require_once 'config.php';
include 'header.php';

// Get all majors for dropdown
$sql = "SELECT * FROM NganhHoc ORDER BY TenNganh";
$majors = $conn->query($sql);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $maSV = $_POST['maSV'];
    $hoTen = $_POST['hoTen'];
    $gioiTinh = $_POST['gioiTinh'];
    $ngaySinh = $_POST['ngaySinh'];
    $maNganh = $_POST['maNganh'];
    
    // Handle file upload
    $targetDir = "Content/images/";
    $fileName = basename($_FILES["hinh"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Check if directory exists, if not create it
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Check if student ID already exists
    $checkSql = "SELECT MaSV FROM SinhVien WHERE MaSV = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $maSV);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $_SESSION['error'] = "Mã sinh viên đã tồn tại!";
    } else {
        // Allow certain file formats
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if(!empty($fileName)) {
            if(in_array($fileType, $allowTypes)){
                // Upload file to server
                if(move_uploaded_file($_FILES["hinh"]["tmp_name"], $targetFilePath)){
                    $hinhPath = "/" . $targetFilePath;
                    
                    // Insert student data
                    $sql = "INSERT INTO SinhVien (MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssss", $maSV, $hoTen, $gioiTinh, $ngaySinh, $hinhPath, $maNganh);
                    
                    if ($stmt->execute()) {
                        $_SESSION['message'] = "Thêm sinh viên thành công!";
                        header("Location: home.php");
                        exit();
                    } else {
                        $_SESSION['error'] = "Lỗi: " . $stmt->error;
                    }
                } else {
                    $_SESSION['error'] = "Lỗi khi tải lên hình ảnh!";
                }
            } else {
                $_SESSION['error'] = "Chỉ cho phép các định dạng JPG, JPEG, PNG & GIF!";
            }
        } else {
            // Insert without image
            $hinhPath = "";
            $sql = "INSERT INTO SinhVien (MaSV, HoTen, GioiTinh, NgaySinh, Hinh, MaNganh) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $maSV, $hoTen, $gioiTinh, $ngaySinh, $hinhPath, $maNganh);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = "Thêm sinh viên thành công!";
                header("Location: home.php");
                exit();
            } else {
                $_SESSION['error'] = "Lỗi: " . $stmt->error;
            }
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Thêm Sinh Viên Mới</h2>
    </div>
    <div class="card-body">
        <a href="index.php" class="btn btn-secondary mb-3">Quay Lại</a>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="maSV">Mã Sinh Viên:</label>
                <input type="text" class="form-control" id="maSV" name="maSV" required maxlength="10">
            </div>
            
            <div class="form-group">
                <label for="hoTen">Họ Tên:</label>
                <input type="text" class="form-control" id="hoTen" name="hoTen" required>
            </div>
            
            <div class="form-group">
                <label>Giới Tính:</label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gioiTinh" id="nam" value="Nam" checked>
                    <label class="form-check-label" for="nam">Nam</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gioiTinh" id="nu" value="Nữ">
                    <label class="form-check-label" for="nu">Nữ</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="ngaySinh">Ngày Sinh:</label>
                <input type="date" class="form-control" id="ngaySinh" name="ngaySinh" required>
            </div>
            
            <div class="form-group">
                <label for="hinh">Hình:</label>
                <input type="file" class="form-control-file" id="hinh" name="hinh">
            </div>
            
            <div class="form-group">
                <label for="maNganh">Ngành Học:</label>
                <select class="form-control" id="maNganh" name="maNganh" required>
                    <option value="">-- Chọn Ngành --</option>
                    <?php while($row = $majors->fetch_assoc()): ?>
                        <option value="<?php echo $row['MaNganh']; ?>"><?php echo $row['TenNganh']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Thêm Sinh Viên</button>
        </form>
    </div>
</div>

<?php
include 'footer.php';
$conn->close();
?>