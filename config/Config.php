<?php
/**
 * Application Configuration
 * Centralized configuration management
 */

class Config {
    // Database Configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'bebuss_db';
    const DB_USER = 'root';
    const DB_PASS = '';
    
    // Security Configuration
    const PASSWORD_MIN_LENGTH = 8;
    const PHONE_MIN_LENGTH = 10;
    const PHONE_MAX_LENGTH = 15;
    const NAME_MIN_LENGTH = 2;
    
    // Session Configuration
    const SESSION_LIFETIME = 3600; // 1 hour
    const EXPIRED_ORDER_MINUTES = 10;
    
    // Rate Limiting
    const LOGIN_MAX_ATTEMPTS = 5;
    const PROFILE_UPDATE_MAX_ATTEMPTS = 10;
    const RATE_LIMIT_WINDOW = 300; // 5 minutes
    
    // Application URLs
    const BASE_URL = '/Proyek%20PBW/BeBuss';
    
    // File Upload
    const MAX_UPLOAD_SIZE = 2097152; // 2MB
    const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validation Messages
    const VALIDATION_MESSAGES = [
        'email_empty' => 'Email tidak boleh kosong.',
        'email_invalid' => 'Format email tidak valid.',
        'password_empty' => 'Password tidak boleh kosong.',
        'password_min_length' => 'Password minimal ' . self::PASSWORD_MIN_LENGTH . ' karakter.',
        'name_empty' => 'Nama lengkap tidak boleh kosong.',
        'name_min_length' => 'Nama lengkap minimal ' . self::NAME_MIN_LENGTH . ' karakter.',
        'phone_empty' => 'Nomor HP tidak boleh kosong.',
        'phone_invalid' => 'Nomor HP harus berupa angka.',
        'phone_length' => 'Nomor HP harus antara ' . self::PHONE_MIN_LENGTH . ' sampai ' . self::PHONE_MAX_LENGTH . ' digit.',
        'gender_invalid' => 'Pilihan jenis kelamin tidak valid.',
        'password_mismatch' => 'Konfirmasi password tidak cocok.',
        'auth_required' => 'Anda harus login untuk mengakses halaman ini.',
        'rate_limit_exceeded' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 5 menit.',
        'system_error' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
    ];
    
    /**
     * Get validation message
     */
    public static function getMessage($key) {
        return self::VALIDATION_MESSAGES[$key] ?? 'Terjadi kesalahan validasi.';
    }
    
    /**
     * Get base URL with protocol
     */
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'] . self::BASE_URL;
    }
}
?>
