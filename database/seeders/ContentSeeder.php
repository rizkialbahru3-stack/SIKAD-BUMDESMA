<?php

namespace Database\Seeders;

use App\Models\Content;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['type' => 'slide', 'slug' => 'slide-1', 'title' => 'Bersama membangun desa, menuju kemandirian.', 'excerpt' => 'Mengenal lebih dekat program, kegiatan, dan potensi lokal BUMDESMA LKD TARUB untuk ekonomi masyarakat yang tumbuh berkelanjutan.', 'image_url' => 'https://images.unsplash.com/photo-1500076656116-558758c991c1?auto=format&fit=crop&w=2200&q=90'],
            ['type' => 'slide', 'slug' => 'slide-2', 'title' => 'Bergerak bersama, berdampak nyata.', 'excerpt' => 'Setiap pelatihan, pertemuan, dan kolaborasi menjadi langkah untuk memperkuat usaha masyarakat.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=2200&q=90'],
            ['type' => 'slide', 'slug' => 'slide-3', 'title' => 'Dari karya warga, lahir masa depan.', 'excerpt' => 'Mengenalkan produk unggulan dan potensi lokal sebagai bagian dari ekonomi desa yang berdaya.', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=2200&q=90'],
            ['type' => 'program', 'slug' => 'pemberdayaan', 'title' => 'Pemberdayaan masyarakat', 'excerpt' => 'Mendukung peningkatan kemampuan dan potensi masyarakat agar semakin mandiri.'],
            ['type' => 'program', 'slug' => 'ekonomi-desa', 'title' => 'Pengembangan ekonomi desa', 'excerpt' => 'Mendorong tumbuhnya usaha dan ekonomi lokal melalui peluang yang nyata.'],
            ['type' => 'program', 'slug' => 'kemitraan', 'title' => 'Kerja sama & kemitraan', 'excerpt' => 'Membangun kolaborasi yang saling menguatkan dengan berbagai pihak.'],
            ['type' => 'program', 'slug' => 'potensi-lokal', 'title' => 'Pengembangan potensi lokal', 'excerpt' => 'Mempromosikan kekayaan wilayah dan karya usaha masyarakat.'],
            ['type' => 'activity', 'slug' => 'pelatihan-usaha', 'title' => 'Pelatihan Pengembangan Usaha Masyarakat', 'excerpt' => 'BUMDESMA LKD TARUB terus mendorong peningkatan kapasitas dan pengembangan potensi ekonomi masyarakat.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1400&q=85'],
            ['type' => 'product', 'slug' => 'hasil-bumi', 'title' => 'Hasil bumi Tarub', 'excerpt' => 'Produk unggulan dari tanah sendiri.', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=85'],
            ['type' => 'product', 'slug' => 'karya-warga', 'title' => 'Karya usaha warga', 'excerpt' => 'Karya UMKM lokal Tarub.', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=900&q=85'],
            ['type' => 'news', 'slug' => 'kolaborasi-masyarakat', 'title' => 'Membuka ruang baru untuk kolaborasi masyarakat', 'excerpt' => 'Informasi terbaru BUMDESMA LKD TARUB.'],
            ['type' => 'gallery', 'slug' => 'kebersamaan', 'title' => 'Kebersamaan masyarakat', 'image_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1000&q=85'],
        ];

        $pages = [
            ['slug' => 'profil', 'label' => 'Tentang BUMDESMA', 'title' => 'Tumbuh dari desa, untuk desa.', 'excerpt' => 'Mengenal peran, arah, dan semangat BUMDESMA LKD TARUB dalam mendukung kemandirian masyarakat.', 'image_url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1800&q=85'],
            ['slug' => 'kegiatan', 'label' => 'Cerita dari lapangan', 'title' => 'Kegiatan yang menggerakkan.', 'excerpt' => 'Dokumentasi kegiatan, pelatihan, dan kolaborasi yang memperkuat ekonomi masyarakat desa.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1800&q=85'],
            ['slug' => 'program', 'label' => 'Yang kami kerjakan', 'title' => 'Program unggulan BUMDESMA.', 'excerpt' => 'Inisiatif yang dirancang untuk membuka peluang, menumbuhkan kemampuan, dan menguatkan desa.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1800&q=85'],
            ['slug' => 'potensi', 'label' => 'Dari tanah sendiri', 'title' => 'Potensi lokal, cerita kita.', 'excerpt' => 'Mengenalkan produk, UMKM, hasil bumi, dan kekayaan wilayah Tarub kepada lebih banyak orang.', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85'],
            ['slug' => 'berita', 'label' => 'Tetap terhubung', 'title' => 'Informasi terkini dari Tarub.', 'excerpt' => 'Berita, pengumuman, dan kabar terbaru seputar kegiatan serta perkembangan BUMDESMA.', 'image_url' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1800&q=85'],
            ['slug' => 'galeri', 'label' => 'Momen yang berarti', 'title' => 'Jejak langkah kebersamaan.', 'excerpt' => 'Kumpulan dokumentasi kegiatan lapangan, pelatihan, pertemuan, dan kerja sama desa.', 'image_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1800&q=85'],
            ['slug' => 'kontak', 'label' => 'Mari berbincang', 'title' => 'Mari bergerak bersama.', 'excerpt' => 'Hubungi kami untuk informasi, kolaborasi, atau berbagi cerita tentang potensi desa.', 'image_url' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1800&q=85'],
        ];

        foreach (array_merge($items, array_map(fn ($page) => ['type' => 'page'] + $page, $pages)) as $item) {
            Content::updateOrCreate(['type' => $item['type'], 'slug' => $item['slug']], $item);
        }
    }
}
