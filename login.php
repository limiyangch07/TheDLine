<?php
include "koneksi.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $hashedPassword = md5($password);

    $sql = "SELECT * FROM tbl_user WHERE username='$username' AND password='$hashedPassword'";
    $result = $koneksi->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id']; 
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<script>alert('Password atau Username salah');</script>";
        echo "<script>window.location.href = 'login.html';</script>";
    }
}
?>
