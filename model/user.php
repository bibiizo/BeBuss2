<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($email, $password) {
        $stmt = $this->pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        return $stmt->execute([$email, $password]);
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($id, $nama, $hp, $jk) {
        $stmt = $this->pdo->prepare("UPDATE users SET nama_lengkap = ?, no_hp = ?, jenis_kelamin = ? WHERE id = ?");
        return $stmt->execute([$nama, $hp, $jk, $id]);
    }

    /**
     * Memperbarui password pengguna di database.
     *
     * @param int $id ID pengguna.
     * @param string $hashedPassword Password baru yang sudah di-hash.
     * @return bool True jika berhasil update, false jika gagal.
     */
    public function updatePassword($id, $hashedPassword) {
        $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }
}
?>