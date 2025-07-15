<?php
/**
 * Configuration class for BeBuss application
 * Contains validation constants and error messages
 */
class Config {
    // Validation constants
    const NAME_MIN_LENGTH = 2;
    const PHONE_MIN_LENGTH = 10;
    const PHONE_MAX_LENGTH = 15;
    const PASSWORD_MIN_LENGTH = 6;
    
    // Error messages
    private static $messages = [
        'name_empty' => 'Nama tidak boleh kosong',
        'name_min_length' => 'Nama minimal 2 karakter',
        'phone_empty' => 'Nomor HP tidak boleh kosong',
        'phone_invalid' => 'Nomor HP hanya boleh berisi angka',
        'phone_length' => 'Nomor HP harus 10-15 digit',
        'gender_invalid' => 'Jenis kelamin tidak valid',
        'password_empty' => 'Password baru tidak boleh kosong',
        'password_min_length' => 'Password minimal 6 karakter',
        'password_mismatch' => 'Konfirmasi password tidak cocok'
    ];
    
    /**
     * Get error message by key
     * @param string $key
     * @return string
     */
    public static function getMessage($key) {
        return self::$messages[$key] ?? 'Error tidak dikenal';
    }
}
?>