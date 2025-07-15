# 📁 Assets Images Structure

Struktur folder untuk menyimpan gambar dalam aplikasi BeBuss:

## 📂 Folder Structure:
```
assets/images/
├── hero/           # Gambar untuk hero section (halaman utama)
├── icons/          # Icon dan logo aplikasi
└── general/        # Gambar umum lainnya
```

## 🎯 Penggunaan:

### 1. **Hero Section Image:**
- **Lokasi:** `assets/images/hero/`
- **Nama file yang disarankan:** `bus-hero.jpg` atau `bus-hero.png`
- **Ukuran optimal:** 800x600px atau 1200x800px
- **Format:** JPG atau PNG

### 2. **Logo & Icons:**
- **Lokasi:** `assets/images/icons/`
- **Logo utama:** `logo.png`
- **Favicon:** `favicon.ico`

### 3. **Cara Menggunakan dalam CSS:**
```css
.hero-image img {
    background-image: url('../../assets/images/hero/bus-hero.jpg');
}
```

### 4. **Cara Menggunakan dalam HTML:**
```html
<img src="../../assets/images/hero/bus-hero.jpg" alt="BeBuss Hero">
```

## 📋 Tips:
- Gunakan format WebP untuk performa lebih baik
- Kompres gambar untuk loading yang cepat
- Berikan nama file yang deskriptif
- Ukuran file maksimal 2MB per gambar

## 🚀 Ready to Use!
Silakan upload gambar bus Anda ke folder `assets/images/hero/`
