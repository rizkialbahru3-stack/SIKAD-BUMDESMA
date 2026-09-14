<?php

namespace App\Http\Controllers;

use App\Models\Content;
use Illuminate\Database\QueryException;

class HomeController extends Controller
{
    public function index()
    {
        $content = $this->contentByType();

        return view('welcome', [
            'slides' => $content['slide'],
            'programs' => $content['program'],
            'activities' => $content['activity'],
            'products' => $content['product'],
            'news' => $content['news'],
            'gallery' => $content['gallery'],
        ]);
    }

    public function page(string $page)
    {
        $page = ['tentang' => 'profil', 'profil-lkd' => 'profil'][$page] ?? $page;
        $content = $this->contentByType();
        $pageContent = collect($content['page'])->firstWhere('slug', $page);

        abort_unless($pageContent, 404);

        return view('content', ['page' => $pageContent, 'pageKey' => $page]);
    }

    private function contentByType(): array
    {
        try {
            return Content::published()->get()->groupBy('type')->map(
                fn ($items) => $items->map(fn (Content $item) => $item->toArray())->values()->all()
            )->all() + $this->defaults();
        } catch (QueryException) {
            return $this->defaults();
        }
    }

    private function defaults(): array
    {
        return [
            'slide' => [
                ['title' => 'Bersama membangun desa, menuju kemandirian.', 'excerpt' => 'Mengenal lebih dekat program, kegiatan, dan potensi lokal BUMDESMA LKD TARUB untuk ekonomi masyarakat yang tumbuh berkelanjutan.', 'image_url' => 'https://images.unsplash.com/photo-1500076656116-558758c991c1?auto=format&fit=crop&w=2200&q=90'],
                ['title' => 'Bergerak bersama, berdampak nyata.', 'excerpt' => 'Setiap pelatihan, pertemuan, dan kolaborasi menjadi langkah untuk memperkuat usaha masyarakat.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=2200&q=90'],
                ['title' => 'Dari karya warga, lahir masa depan.', 'excerpt' => 'Mengenalkan produk unggulan dan potensi lokal sebagai bagian dari ekonomi desa yang berdaya.', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=2200&q=90'],
            ],
            'program' => [
                ['title' => 'Pemberdayaan masyarakat', 'excerpt' => 'Mendukung peningkatan kemampuan dan potensi masyarakat agar semakin mandiri.'],
                ['title' => 'Pengembangan ekonomi desa', 'excerpt' => 'Mendorong tumbuhnya usaha dan ekonomi lokal melalui peluang yang nyata.'],
                ['title' => 'Kerja sama & kemitraan', 'excerpt' => 'Membangun kolaborasi yang saling menguatkan dengan berbagai pihak.'],
                ['title' => 'Pengembangan potensi lokal', 'excerpt' => 'Mempromosikan kekayaan wilayah dan karya usaha masyarakat.'],
            ],
            'activity' => [['title' => 'Pelatihan Pengembangan Usaha Masyarakat', 'excerpt' => 'BUMDESMA LKD TARUB terus mendorong peningkatan kapasitas dan pengembangan potensi ekonomi masyarakat.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1400&q=85']],
            'product' => [['title' => 'Hasil bumi Tarub', 'excerpt' => 'Produk unggulan dari tanah sendiri.', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=85'], ['title' => 'Karya usaha warga', 'excerpt' => 'Karya UMKM lokal Tarub.', 'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=900&q=85']],
            'news' => [['title' => 'Membuka ruang baru untuk kolaborasi masyarakat', 'excerpt' => 'Informasi terbaru BUMDESMA LKD TARUB.']],
            'gallery' => [['title' => 'Kebersamaan masyarakat', 'image_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1000&q=85']],
            'page' => [
                ['slug' => 'profil', 'label' => 'Tentang BUMDESMA', 'title' => 'Tumbuh dari desa, untuk desa.', 'intro' => 'Mengenal peran, arah, dan semangat BUMDESMA LKD TARUB dalam mendukung kemandirian masyarakat.', 'image_url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'kegiatan', 'label' => 'Cerita dari lapangan', 'title' => 'Kegiatan yang menggerakkan.', 'intro' => 'Dokumentasi kegiatan, pelatihan, dan kolaborasi yang memperkuat ekonomi masyarakat desa.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'program', 'label' => 'Yang kami kerjakan', 'title' => 'Program unggulan BUMDESMA.', 'intro' => 'Inisiatif yang dirancang untuk membuka peluang, menumbuhkan kemampuan, dan menguatkan desa.', 'image_url' => 'https://images.unsplash.com/photo-1556761175-4b46a572b786?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'potensi', 'label' => 'Dari tanah sendiri', 'title' => 'Potensi lokal, cerita kita.', 'intro' => 'Mengenalkan produk, UMKM, hasil bumi, dan kekayaan wilayah Tarub kepada lebih banyak orang.', 'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'berita', 'label' => 'Tetap terhubung', 'title' => 'Informasi terkini dari Tarub.', 'intro' => 'Berita, pengumuman, dan kabar terbaru seputar kegiatan serta perkembangan BUMDESMA.', 'image_url' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'galeri', 'label' => 'Momen yang berarti', 'title' => 'Jejak langkah kebersamaan.', 'intro' => 'Kumpulan dokumentasi kegiatan lapangan, pelatihan, pertemuan, dan kerja sama desa.', 'image_url' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=1800&q=85'],
                ['slug' => 'kontak', 'label' => 'Mari berbincang', 'title' => 'Mari bergerak bersama.', 'intro' => 'Hubungi kami untuk informasi, kolaborasi, atau berbagi cerita tentang potensi desa.', 'image_url' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1800&q=85'],
            ],
        ];
    }
}
