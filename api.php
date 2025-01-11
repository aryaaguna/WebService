<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'includes/config.php';

// Fungsi untuk sanitasi input
function sanitize($conn, $data) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Fungsi untuk response JSON
function sendResponse($status, $message, $data = null) {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Mendapatkan method dan endpoint dari request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '','/'));
$endpoint = $request[0] ?? '';

// Router untuk endpoint
switch($endpoint) {
    case 'guru':
        handleGuru($method);
        break;
    case 'siswa':
        handleSiswa($method);
        break;
    case 'kelas':
        handleKelas($method);
        break;
    case 'mapel':
        handleMapel($method);
        break;
    case 'nilai':
        handleNilai($method);
        break;
    default:
        sendResponse(404, 'Endpoint tidak ditemukan');
}

// Handler untuk Guru
function handleGuru($method) {
    global $conn;
    
    switch($method) {
        case 'GET':
            if(isset($_GET['nip'])) {
                $nip = sanitize($conn, $_GET['nip']);
                $query = "SELECT * FROM guru WHERE nip = '$nip'";
                $result = mysqli_query($conn, $query);
                $data = mysqli_fetch_assoc($result);
                
                if($data) {
                    sendResponse(200, 'Data guru ditemukan', $data);
                } else {
                    sendResponse(404, 'Data guru tidak ditemukan');
                }
            } else {
                $query = "SELECT * FROM guru";
                $result = mysqli_query($conn, $query);
                $data = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                sendResponse(200, 'Data guru berhasil diambil', $data);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validasi input
            if(!isset($input['nip'], $input['nama_guru'], $input['jenis_kelamin'])) {
                sendResponse(400, 'Data tidak lengkap');
            }
            
            $nip = sanitize($conn, $input['nip']);
            $nama = sanitize($conn, $input['nama_guru']);
            $jk = sanitize($conn, $input['jenis_kelamin']);
            $alamat = sanitize($conn, $input['alamat'] ?? '');
            $telp = sanitize($conn, $input['no_telp'] ?? '');
            $mapel = sanitize($conn, $input['mata_pelajaran'] ?? '');
            
            $query = "INSERT INTO guru (nip, nama_guru, jenis_kelamin, alamat, no_telp, mata_pelajaran) 
                     VALUES ('$nip', '$nama', '$jk', '$alamat', '$telp', '$mapel')";
            
            if(mysqli_query($conn, $query)) {
                sendResponse(201, 'Data guru berhasil ditambahkan');
            } else {
                sendResponse(500, 'Gagal menambahkan data guru: ' . mysqli_error($conn));
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if(!isset($input['nip'])) {
                sendResponse(400, 'NIP diperlukan');
            }
            
            $nip = sanitize($conn, $input['nip']);
            $nama = sanitize($conn, $input['nama_guru'] ?? '');
            $jk = sanitize($conn, $input['jenis_kelamin'] ?? '');
            $alamat = sanitize($conn, $input['alamat'] ?? '');
            $telp = sanitize($conn, $input['no_telp'] ?? '');
            $mapel = sanitize($conn, $input['mata_pelajaran'] ?? '');
            
            $updates = [];
            if($nama) $updates[] = "nama_guru = '$nama'";
            if($jk) $updates[] = "jenis_kelamin = '$jk'";
            if($alamat) $updates[] = "alamat = '$alamat'";
            if($telp) $updates[] = "no_telp = '$telp'";
            if($mapel) $updates[] = "mata_pelajaran = '$mapel'";
            
            if(empty($updates)) {
                sendResponse(400, 'Tidak ada data yang diupdate');
            }
            
            $query = "UPDATE guru SET " . implode(', ', $updates) . " WHERE nip = '$nip'";
            
            if(mysqli_query($conn, $query)) {
                sendResponse(200, 'Data guru berhasil diupdate');
            } else {
                sendResponse(500, 'Gagal mengupdate data guru: ' . mysqli_error($conn));
            }
            break;
            
        case 'DELETE':
            if(!isset($_GET['nip'])) {
                sendResponse(400, 'NIP diperlukan');
            }
            
            $nip = sanitize($conn, $_GET['nip']);
            
            // Cek referensi di tabel lain
            $check_query = "SELECT COUNT(*) as count FROM kelas WHERE wali_kelas = '$nip'";
            $check_result = mysqli_query($conn, $check_query);
            $count = mysqli_fetch_assoc($check_result)['count'];
            
            if($count > 0) {
                sendResponse(400, 'Guru tidak dapat dihapus karena masih menjadi wali kelas');
            }
            
            $query = "DELETE FROM guru WHERE nip = '$nip'";
            if(mysqli_query($conn, $query)) {
                sendResponse(200, 'Data guru berhasil dihapus');
            } else {
                sendResponse(500, 'Gagal menghapus data guru: ' . mysqli_error($conn));
            }
            break;
            
        default:
            sendResponse(405, 'Method tidak diizinkan');
    }
}

// Handler untuk Siswa
function handleSiswa($method) {
    global $conn;
    
    switch($method) {
        case 'GET':
            if(isset($_GET['nis'])) {
                $nis = sanitize($conn, $_GET['nis']);
                $query = "SELECT s.*, k.nama_kelas 
                         FROM siswa s 
                         JOIN kelas k ON s.id_kelas = k.id_kelas 
                         WHERE nis = '$nis'";
                $result = mysqli_query($conn, $query);
                $data = mysqli_fetch_assoc($result);
                
                if($data) {
                    sendResponse(200, 'Data siswa ditemukan', $data);
                } else {
                    sendResponse(404, 'Data siswa tidak ditemukan');
                }
            } else {
                $query = "SELECT s.*, k.nama_kelas 
                         FROM siswa s 
                         JOIN kelas k ON s.id_kelas = k.id_kelas";
                $result = mysqli_query($conn, $query);
                $data = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                sendResponse(200, 'Data siswa berhasil diambil', $data);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validasi input
            if(!isset($input['nis'], $input['nama_siswa'], $input['id_kelas'])) {
                sendResponse(400, 'Data tidak lengkap');
            }
            
            $nis = sanitize($conn, $input['nis']);
            $nama = sanitize($conn, $input['nama_siswa']);
            $jk = sanitize($conn, $input['jenis_kelamin'] ?? '');
            $tgl_lahir = sanitize($conn, $input['tanggal_lahir'] ?? null);
            $alamat = sanitize($conn, $input['alamat'] ?? '');
            $id_kelas = sanitize($conn, $input['id_kelas']);
            
            $query = "INSERT INTO siswa (nis, nama_siswa, jenis_kelamin, tanggal_lahir, alamat, id_kelas) 
                     VALUES ('$nis', '$nama', '$jk', " . ($tgl_lahir ? "'$tgl_lahir'" : "NULL") . ", '$alamat', '$id_kelas')";
            
            if(mysqli_query($conn, $query)) {
                // Update jumlah siswa di kelas
                mysqli_query($conn, "UPDATE kelas SET jumlah_siswa = jumlah_siswa + 1 WHERE id_kelas = '$id_kelas'");
                sendResponse(201, 'Data siswa berhasil ditambahkan');
            } else {
                sendResponse(500, 'Gagal menambahkan data siswa: ' . mysqli_error($conn));
            }
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if(!isset($input['nis'])) {
                sendResponse(400, 'NIS diperlukan');
            }
            
            $nis = sanitize($conn, $input['nis']);
            $nama = sanitize($conn, $input['nama_siswa'] ?? '');
            $jk = sanitize($conn, $input['jenis_kelamin'] ?? '');
            $tgl_lahir = sanitize($conn, $input['tanggal_lahir'] ?? null);
            $alamat = sanitize($conn, $input['alamat'] ?? '');
            $id_kelas = sanitize($conn, $input['id_kelas'] ?? '');
            
            $updates = [];
            if($nama) $updates[] = "nama_siswa = '$nama'";
            if($jk) $updates[] = "jenis_kelamin = '$jk'";
            if($tgl_lahir) $updates[] = "tanggal_lahir = '$tgl_lahir'";
            if($alamat) $updates[] = "alamat = '$alamat'";
            if($id_kelas) {
                // Ambil kelas lama
                $query_old = "SELECT id_kelas FROM siswa WHERE nis = '$nis'";
                $result_old = mysqli_query($conn, $query_old);
                $old_kelas = mysqli_fetch_assoc($result_old)['id_kelas'];
                
                // Update jumlah siswa di kelas lama (-1)
                if($old_kelas) {
                    mysqli_query($conn, "UPDATE kelas SET jumlah_siswa = jumlah_siswa - 1 WHERE id_kelas = '$old_kelas'");
                }
                
                // Update jumlah siswa di kelas baru (+1)
                mysqli_query($conn, "UPDATE kelas SET jumlah_siswa = jumlah_siswa + 1 WHERE id_kelas = '$id_kelas'");
                
                $updates[] = "id_kelas = '$id_kelas'";
            }
            
            if(empty($updates)) {
                sendResponse(400, 'Tidak ada data yang diupdate');
            }
            
            $query = "UPDATE siswa SET " . implode(', ', $updates) . " WHERE nis = '$nis'";
            
            if(mysqli_query($conn, $query)) {
                sendResponse(200, 'Data siswa berhasil diupdate');
            } else {
                sendResponse(500, 'Gagal mengupdate data siswa: ' . mysqli_error($conn));
            }
            break;
            
        case 'DELETE':
            if(!isset($_GET['nis'])) {
                sendResponse(400, 'NIS diperlukan');
            }
            
            $nis = sanitize($conn, $_GET['nis']);
            
            // Cek referensi di tabel nilai
            $check_query = "SELECT COUNT(*) as count FROM nilai WHERE nis = '$nis'";
            $check_result = mysqli_query($conn, $check_query);
            $count = mysqli_fetch_assoc($check_result)['count'];
            
            if($count > 0) {
                sendResponse(400, 'Siswa tidak dapat dihapus karena masih memiliki data nilai');
            }
            
            // Ambil id_kelas sebelum menghapus siswa
            $query_kelas = "SELECT id_kelas FROM siswa WHERE nis = '$nis'";
            $result_kelas = mysqli_query($conn, $query_kelas);
            $id_kelas = mysqli_fetch_assoc($result_kelas)['id_kelas'];
            
            // Hapus siswa
            $query = "DELETE FROM siswa WHERE nis = '$nis'";
            if(mysqli_query($conn, $query)) {
                // Update jumlah siswa di kelas
                mysqli_query($conn, "UPDATE kelas SET jumlah_siswa = jumlah_siswa - 1 WHERE id_kelas = '$id_kelas'");
                sendResponse(200, 'Data siswa berhasil dihapus');
            } else {
                sendResponse(500, 'Gagal menghapus data siswa: ' . mysqli_error($conn));
            }
            break;
            
        // Implementasi PUT dan DELETE untuk siswa
    }
}

// Handler untuk Nilai
function handleNilai($method) {
    global $conn;
    
    switch($method) {
        case 'GET':
            if(isset($_GET['id'])) {
                $id = sanitize($conn, $_GET['id']);
                $query = "SELECT n.*, s.nama_siswa, m.nama_mapel 
                         FROM nilai n
                         JOIN siswa s ON n.nis = s.nis
                         JOIN mata_pelajaran m ON n.id_mapel = m.id_mapel
                         WHERE id_nilai = '$id'";
                $result = mysqli_query($conn, $query);
                $data = mysqli_fetch_assoc($result);
                
                if($data) {
                    sendResponse(200, 'Data nilai ditemukan', $data);
                } else {
                    sendResponse(404, 'Data nilai tidak ditemukan');
                }
            } else {
                $query = "SELECT n.*, s.nama_siswa, m.nama_mapel 
                         FROM nilai n
                         JOIN siswa s ON n.nis = s.nis
                         JOIN mata_pelajaran m ON n.id_mapel = m.id_mapel
                         ORDER BY n.id_nilai DESC";
                $result = mysqli_query($conn, $query);
                $data = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                sendResponse(200, 'Data nilai berhasil diambil', $data);
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validasi input
            if(!isset($input['nis'], $input['id_mapel'], $input['nilai_tugas'], $input['nilai_uts'], $input['nilai_uas'])) {
                sendResponse(400, 'Data tidak lengkap');
            }
            
            $nis = sanitize($conn, $input['nis']);
            $id_mapel = sanitize($conn, $input['id_mapel']);
            $nilai_tugas = floatval($input['nilai_tugas']);
            $nilai_uts = floatval($input['nilai_uts']);
            $nilai_uas = floatval($input['nilai_uas']);
            $semester = sanitize($conn, $input['semester'] ?? '1');
            $tahun_ajaran = sanitize($conn, $input['tahun_ajaran'] ?? '');
            
            // Hitung nilai akhir
            $nilai_akhir = ($nilai_tugas + $nilai_uts + $nilai_uas) / 3;
            
            $query = "INSERT INTO nilai (nis, id_mapel, nilai_tugas, nilai_uts, nilai_uas, nilai_akhir, semester, tahun_ajaran) 
                     VALUES ('$nis', '$id_mapel', $nilai_tugas, $nilai_uts, $nilai_uas, $nilai_akhir, '$semester', '$tahun_ajaran')";
            
            if(mysqli_query($conn, $query)) {
                sendResponse(201, 'Data nilai berhasil ditambahkan');
            } else {
                sendResponse(500, 'Gagal menambahkan data nilai: ' . mysqli_error($conn));
            }
            break;
            
        // Implementasi PUT dan DELETE untuk nilai
    }
}

// Handler untuk Kelas dan Mapel bisa diimplementasikan dengan cara yang sama
?> 