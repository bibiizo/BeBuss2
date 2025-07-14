# BeBuss - Bus Ticketing System 🚌

Sistem pemesanan tiket bus online yang dibuat dengan PHP dan MySQL.

## 🚀 Fitur Utama

### ✅ Authentication System
- **Login & Register** - Sistem autentikasi user
- **Profile Management** - Update profil dan password
- **Session Management** - Keamanan session user

### ✅ Booking System
- **Search Bus** - Pencarian bus dengan autocomplete kota
- **Route Selection** - Pilih rute perjalanan
- **Seat Booking** - Pemilihan kursi bus
- **Payment Integration** - Sistem pembayaran

### ✅ User Experience
- **Responsive Design** - UI yang mobile-friendly
- **Modern Interface** - Design yang clean dan user-friendly
- **Real-time Search** - Autocomplete untuk pencarian kota

## 🛠️ Teknologi

- **Backend**: PHP 8+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache/Nginx

## 📁 Struktur Project

```
BeBuss/
├── api/                    # API endpoints
│   └── api_saran_kota.php # Autocomplete API
├── assets/                # Static files
│   ├── css/
│   ├── js/
│   └── images/
├── config/                # Configuration
│   ├── database.php       # Database config
│   └── Config.php         # App config
├── controller/            # Business logic
│   ├── AuthController.php
│   ├── LoginController.php
│   └── ProfileController.php
├── model/                 # Data models
│   └── User.php
├── view/                  # Templates
│   ├── auth/             # Authentication views
│   ├── booking/          # Booking views
│   ├── history/          # History views
│   └── home/             # Dashboard views
├── index.php             # Entry point
└── logout.php            # Logout handler
```

## 🔧 Instalasi

1. **Clone repository**
   ```bash
   git clone [repository-url]
   cd BeBuss
   ```

2. **Setup Database**
   - Import database schema
   - Update `config/database.php` dengan kredensial database Anda

3. **Setup Web Server**
   - Pastikan PHP 8+ dan MySQL terinstall
   - Arahkan document root ke folder BeBuss
   - Atau gunakan built-in PHP server: `php -S localhost:8000`

4. **Konfigurasi**
   - Update `config/Config.php` sesuai environment Anda

## 🔒 Keamanan

- **Input Sanitization** - Semua input user dibersihkan
- **Rate Limiting** - Pembatasan request untuk mencegah spam
- **Authentication** - Sistem login yang aman
- **XSS Protection** - Perlindungan dari serangan XSS

## 🎯 Fitur yang Dikembangkan

- ✅ **Working Autocomplete** - Sistem autocomplete yang responsif
- ✅ **Profile Management** - Update profil user yang aman
- ✅ **Modern UI** - Interface yang clean dan modern
- ✅ **Security** - Implementasi keamanan yang robust

## 📱 Screenshots

[Tambahkan screenshots aplikasi di sini]

## 🤝 Kontribusi

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📄 Lisensi

Project ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail.

## 📞 Kontak

- **Developer**: [Your Name]
- **Email**: [Your Email]
- **Project Link**: [GitHub Repository URL]

---

**BeBuss** - Solusi modern untuk pemesanan tiket bus online! 🚌✨
