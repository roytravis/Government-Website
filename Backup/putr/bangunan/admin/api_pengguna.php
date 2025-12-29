<?php
// (admin/api_pengguna.php)
session_start();
header('Content-Type: application/json');

require "../includes/koneksi.php";

// Keamanan: Pastikan hanya admin yang bisa mengakses API ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_users':
        $result = $conn->query("SELECT id, nip, nama_lengkap, email, nomor_telepon, nama_instansi, jabatan, role FROM bg_users ORDER BY nama_lengkap ASC");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode($users);
        break;

    case 'get_user':
        $id = $_GET['id'] ?? 0;
        $stmt = $conn->prepare("SELECT id, nip, nama_lengkap, email, nomor_telepon, nama_instansi, jabatan, role FROM bg_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        echo json_encode($user);
        break;

    case 'add_user':
        // Validasi input dasar
        if (empty($_POST['nip']) || empty($_POST['nama_lengkap']) || empty($_POST['password']) || empty($_POST['role'])) {
            echo json_encode(['success' => false, 'message' => 'NIP, Nama Lengkap, Password, dan Role wajib diisi.']);
            exit;
        }
        // Cek duplikasi NIP
        $stmt_check = $conn->prepare("SELECT id FROM bg_users WHERE nip = ?");
        $stmt_check->bind_param("s", $_POST['nip']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'NIP sudah terdaftar. Silakan gunakan NIP lain.']);
            exit;
        }
        $stmt_check->close();

        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO bg_users (nip, nama_lengkap, email, nomor_telepon, nama_instansi, jabatan, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", 
            $_POST['nip'], $_POST['nama_lengkap'], $_POST['email'], $_POST['nomor_telepon'], 
            $_POST['nama_instansi'], $_POST['jabatan'], $_POST['role'], $password_hash
        );
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil ditambahkan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan pengguna.']);
        }
        break;

    case 'update_user':
        $id = $_POST['id'] ?? 0;
        if (empty($id) || empty($_POST['nip']) || empty($_POST['nama_lengkap']) || empty($_POST['role'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
            exit;
        }
        
        // Cek duplikasi NIP (kecuali untuk user itu sendiri)
        $stmt_check = $conn->prepare("SELECT id FROM bg_users WHERE nip = ? AND id != ?");
        $stmt_check->bind_param("si", $_POST['nip'], $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'NIP sudah digunakan oleh akun lain.']);
            exit;
        }
        $stmt_check->close();

        if (!empty($_POST['password'])) {
            // Jika password diisi, update password
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE bg_users SET nip=?, nama_lengkap=?, email=?, nomor_telepon=?, nama_instansi=?, jabatan=?, role=?, password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", 
                $_POST['nip'], $_POST['nama_lengkap'], $_POST['email'], $_POST['nomor_telepon'],
                $_POST['nama_instansi'], $_POST['jabatan'], $_POST['role'], $password_hash, $id
            );
        } else {
            // Jika password kosong, jangan update password
            $sql = "UPDATE bg_users SET nip=?, nama_lengkap=?, email=?, nomor_telepon=?, nama_instansi=?, jabatan=?, role=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", 
                $_POST['nip'], $_POST['nama_lengkap'], $_POST['email'], $_POST['nomor_telepon'],
                $_POST['nama_instansi'], $_POST['jabatan'], $_POST['role'], $id
            );
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Data pengguna berhasil diperbarui.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui data pengguna.']);
        }
        break;

    case 'delete_user':
        $id = $_POST['id'] ?? 0;
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID pengguna tidak valid.']);
            exit;
        }
        // Keamanan tambahan: jangan biarkan admin menghapus akunnya sendiri
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM bg_users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pengguna.']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

$conn->close();
?>
