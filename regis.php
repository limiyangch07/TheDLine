<?php
include "koneksi.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $hashedPassword = md5($password);

    $sql = "SELECT * FROM tbl_user WHERE username='$username' OR email='$email'";
    $result = $koneksi->query($sql);

    if ($result->num_rows > 0) {
        $existingUser   = $result->fetch_assoc();
        if ($existingUser ['username'] === $username) {
            echo "<script>alert('Username telah digunakan');</script>";
        }
        if ($existingUser ['email'] === $email) {
            echo "<script>alert('Email telah digunakan');</script>";
            echo "<script>window.location.href = 'register.html';</script>";
        }
    } else {
        $sql = "INSERT INTO tbl_user (username, email, password) VALUES ('$username', '$email', '$hashedPassword')";
        if ($koneksi->query($sql) === TRUE) {
            echo "<script>alert('Anda berhasil daftar');</script>";
            echo "<script>window.location.href = 'login.html';</script>";
        } else {
            echo "<script>alert('Pendaftaran gagal: " . $koneksi->error . "');</script>";
        }
    }
}
?>