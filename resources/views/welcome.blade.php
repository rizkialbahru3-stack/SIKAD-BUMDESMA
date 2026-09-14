<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="Sistem Informasi BUMDESMA LKD TARUB — absensi, penggajian, dan layanan digital desa.">
    <title>BUMDESMA LKD TARUB</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *{box-sizing:border-box}html{scroll-behavior:smooth;overflow-x:clip}body{margin:0;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#fff;background:#2e4a75;overflow-x:clip}
        img{height:auto}
        a{text-decoration:none;color:inherit}
        a:focus-visible,.btn-masuk:focus-visible,.nav-toggle:focus-visible{outline:3px solid #3fb3f6;outline-offset:3px}
        .hero{min-height:100vh;min-height:100svh;display:flex;flex-direction:column;background:#2e4a75}
        .nav{width:min(1200px,calc(100% - 80px));margin:0 auto;display:flex;align-items:center;gap:36px;padding:34px 0}
        .nav-links{display:flex;align-items:center;gap:36px}
        .nav a{font-size:19px;color:#e8eef6;padding:10px 2px}
        .nav a.active{color:#4db8ff}
        .nav-cta{margin-left:auto;display:inline-block;padding:10px 26px;border:1px solid #3fb3f6;border-radius:999px;color:#fff;font-size:15px;font-weight:700;white-space:nowrap}
        .nav-toggle{display:none;margin-left:auto;background:none;border:1px solid rgba(255,255,255,.4);border-radius:10px;color:#fff;font-size:22px;padding:6px 12px;cursor:pointer}
        .hero-body{flex:1;width:min(1200px,calc(100% - 80px));margin:0 auto;display:grid;grid-template-columns:1.05fr .95fr;align-items:center;gap:40px;padding:20px 0 60px}
        .hero-title{margin:0;font-size:clamp(44px,11vw,110px);line-height:1;font-weight:800;letter-spacing:-.02em;overflow-wrap:break-word}
        .hero-sub{margin:26px 0 0;color:#a9bedd;font-size:clamp(16px,4.4vw,28px);line-height:1.5;max-width:560px}
        .hero-sub br{display:none}
        @media(min-width:700px){.hero-sub br{display:inline}}
        .btn-masuk{display:inline-block;margin-top:44px;padding:16px 46px;background:#3fb3f6;color:#fff;font-size:20px;font-weight:600;border-radius:999px;border:0;box-shadow:0 8px 20px rgba(63,179,246,.35);transition:.2s;min-height:56px}
        .btn-masuk:hover{background:#2aa5ef;transform:translateY(-2px)}
        .art{position:relative;display:flex;justify-content:center;min-width:0}
        .blob{background:#e9f6f0;border-radius:48px;padding:34px 30px;max-width:520px;width:100%;position:relative;box-shadow:0 20px 50px rgba(0,0,0,.25)}
        .blob img.logo{width:100%;max-height:300px;object-fit:contain;background:#fff;border-radius:24px;padding:24px}
        .chip{position:absolute;display:flex;align-items:center;gap:8px;background:#fff;color:#2e4a75;font-size:12px;font-weight:700;border-radius:12px;padding:10px 14px;box-shadow:0 10px 24px rgba(0,0,0,.22);white-space:nowrap;max-width:calc(100% - 16px)}
        .chip i{font-size:18px;color:#1e5aa8}
        .chip.c1{top:-18px;left:8px}.chip.c2{top:40%;right:8px}.chip.c3{bottom:-18px;left:30px}
        .cta-panel{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16);border-radius:20px;padding:28px}
        .cta-panel h3{margin:0 0 8px;font-size:20px}
        .cta-panel p{margin:0 0 4px;color:#a9bedd;font-size:14px}
        .strip{background:#f4f6f9;color:#8a97a5}
        .strip-inner{width:min(1200px,calc(100% - 80px));margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:28px;padding:26px 0;flex-wrap:wrap;filter:grayscale(1)}
        .strip img{height:64px;object-fit:contain}
        .strip-item{font-size:13px;font-weight:800;letter-spacing:.06em;display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
        .strip-item i{font-size:26px}
        .section{background:#fff;color:#2e4a75;padding:84px 0}
        .wrap{width:min(1200px,calc(100% - 80px));margin:0 auto}
        .wrap h2{margin:0;font-size:clamp(28px,4vw,44px)}
        .wrap>p{color:#64748b;max-width:640px}
        .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:36px}
        .card{border:1px solid #dbe5f0;border-radius:18px;padding:26px;background:#f8fbff}
        .card i{font-size:30px;color:#1e5aa8}
        .card h3{margin:16px 0 8px;font-size:17px}
        .card p{margin:0;color:#64748b;font-size:13px;line-height:1.6}
        .kontak{background:#243d63;padding:72px 0}
        .kontak-grid{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
        .kontak h2{margin:0 0 12px;font-size:clamp(26px,3.5vw,40px)}
        .kontak p{color:#a9bedd;margin:0 0 8px}
        .kontak .line{display:flex;align-items:center;gap:12px;margin-top:14px;color:#e8eef6}
        .kontak .line i{color:#3fb3f6;font-size:20px}
        footer{background:#1f3354;color:#8fa3c2;font-size:12px}
        footer .wrap{display:flex;justify-content:space-between;gap:12px;padding:18px 0;flex-wrap:wrap;width:min(1200px,calc(100% - 80px));margin:0 auto}
        @media(max-width:900px){.hero-body{grid-template-columns:1fr;gap:56px}.grid{grid-template-columns:1fr 1fr}.kontak-grid{grid-template-columns:1fr}}
        @media(max-width:680px){
            .nav{width:calc(100% - 40px);padding:20px 0;gap:0}
            .nav-toggle{display:block}
            .nav-cta{display:none}
            .nav-links{position:absolute;top:72px;left:20px;right:20px;display:none;flex-direction:column;align-items:stretch;gap:0;background:#243d63;border:1px solid rgba(255,255,255,.14);border-radius:14px;padding:8px 18px;box-shadow:0 18px 40px rgba(0,0,0,.35);z-index:30}
            .nav-links.open{display:flex}
            .nav-links a{padding:14px 4px;border-bottom:1px solid rgba(255,255,255,.1)}
            .nav-links a:last-child{border-bottom:0}
            .hero{min-height:auto}
            .hero-body{width:calc(100% - 40px);padding:8px 0 56px}
            .btn-masuk{margin-top:32px;padding:15px 30px;font-size:17px;width:100%;text-align:center}
            .blob{border-radius:28px;padding:22px 16px}
            .blob img.logo{max-height:220px;padding:16px}
            .chip{font-size:11px;padding:8px 10px}
            .strip-inner{width:calc(100% - 40px);display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:24px 0;justify-items:stretch}
            .strip-inner img{grid-column:1/-1;justify-self:center;height:56px;margin-bottom:6px}
            .strip-item{flex-direction:column;justify-content:center;gap:8px;text-align:center;font-size:11px;letter-spacing:.04em;white-space:normal;line-height:1.4;background:#fff;border:1px solid #e2e9f1;border-radius:14px;padding:16px 8px;min-height:104px}
            .strip-item i{font-size:28px}
            .section{padding:60px 0}
            .wrap{width:calc(100% - 40px)}
            .grid{grid-template-columns:1fr}
            .kontak{padding:56px 0}
            footer .wrap{width:calc(100% - 40px)}
        }
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.btn-masuk{transition:none}}
        @keyframes riseIn{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:none}}
        @keyframes popIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
        @keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
        .hero-anim{opacity:0;animation:riseIn .8s ease forwards}
        .hero-anim.d1{animation-delay:.1s}.hero-anim.d2{animation-delay:.25s}.hero-anim.d3{animation-delay:.4s}
        .blob{animation:popIn .9s ease .2s backwards}
        .chip.c1{animation:floatY 4s ease-in-out 1s infinite}
        .chip.c2{animation:floatY 4.6s ease-in-out .4s infinite}
        .chip.c3{animation:floatY 5.2s ease-in-out .8s infinite}
        .reveal{opacity:0;transform:translateY(28px);transition:opacity .7s ease,transform .7s ease}
        .reveal.visible{opacity:1;transform:none}
        .grid .card:nth-child(2){transition-delay:.1s}.grid .card:nth-child(3){transition-delay:.2s}.grid .card:nth-child(4){transition-delay:.3s}
        .strip-inner>*:nth-child(2){transition-delay:.05s}.strip-inner>*:nth-child(3){transition-delay:.12s}.strip-inner>*:nth-child(4){transition-delay:.19s}.strip-inner>*:nth-child(5){transition-delay:.26s}
        @media(prefers-reduced-motion:reduce){.hero-anim,.blob,.chip{animation:none;opacity:1}.reveal{opacity:1;transform:none;transition:none}}
        .hero{position:relative;overflow:hidden}
        .hero>*{position:relative;z-index:1}
        .orb{position:absolute;z-index:0;border-radius:50%;filter:blur(70px);opacity:.55;pointer-events:none}
        .orb.o1{width:420px;height:420px;left:-140px;top:-120px;background:#3fb3f6;animation:drift 11s ease-in-out infinite alternate}
        .orb.o2{width:360px;height:360px;right:-120px;bottom:-140px;background:#1e5aa8;animation:drift 13s ease-in-out -4s infinite alternate-reverse}
        @keyframes drift{from{transform:translate(0,0) scale(1)}to{transform:translate(60px,40px) scale(1.15)}}
        @keyframes glowPulse{0%,100%{box-shadow:0 8px 20px rgba(63,179,246,.35)}50%{box-shadow:0 8px 34px rgba(63,179,246,.65)}}
        .blob{animation:popIn .9s ease .2s backwards,floatY 6s ease-in-out 1.2s infinite}
        .chip.c1{animation:floatY 3.2s ease-in-out 1s infinite}
        .chip.c2{animation:floatY 3.7s ease-in-out .4s infinite}
        .chip.c3{animation:floatY 4.1s ease-in-out .8s infinite}
        @keyframes floatY{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
        .btn-masuk{animation:riseIn .8s ease .4s forwards,glowPulse 2.6s ease-in-out 3s infinite}
        @media(prefers-reduced-motion:reduce){.orb{display:none}.btn-masuk{animation:none}}
    </style>
</head>
<body>
    <div class="hero" id="home">
        <span class="orb o1" aria-hidden="true"></span><span class="orb o2" aria-hidden="true"></span>
        <nav class="nav"><div class="nav-links" id="navLinks"><a href="#home" class="active">Home</a><a href="#fitur">Fitur</a><a href="#kontak">Kontak Kami</a></div><button class="nav-toggle" type="button" aria-expanded="false" aria-controls="navLinks" id="navToggle"><i class="bi bi-list"></i><span class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)">Buka navigasi</span></button></nav>
        <div class="hero-body">
            <div>
                <h1 class="hero-title hero-anim d1">BUMDESMA</h1>
                <p class="hero-sub hero-anim d2">Selamat datang di Sistem Informasi BUMDESMA<br>LKD TARUB, Tarub — Kabupaten Tegal</p>
                <a class="btn-masuk hero-anim d3" href="{{ route('login') }}">Masuk</a>
            </div>
            <div class="art">
                <div class="blob">
                    <span class="chip c1"><i class="bi bi-fingerprint"></i> Absensi Digital</span>
                    <img class="logo" src="{{ asset('images/logo-bumdesma.png') }}" alt="Logo Kantor BUMDESMA LKD TARUB">
                    <span class="chip c2"><i class="bi bi-cash-stack"></i> Penggajian</span>
                    <span class="chip c3"><i class="bi bi-bar-chart"></i> Laporan & Cuti</span>
                </div>
            </div>
        </div>
    </div>
    <div class="strip">
        <div class="strip-inner">
            <img class="reveal" src="{{ asset('images/logo-bumdesma.png') }}" alt="Logo BUMDESMA LKD TARUB">
            <span class="strip-item reveal"><i class="bi bi-buildings"></i> PEMERINTAH DESA</span>
            <span class="strip-item reveal"><i class="bi bi-people"></i> MASYARAKAT TARUB</span>
            <span class="strip-item reveal"><i class="bi bi-globe2"></i> MITRA LOKAL</span>
            <span class="strip-item reveal"><i class="bi bi-stars"></i> UMKM BINAAN</span>
        </div>
    </div>
    <section class="section" id="fitur">
        <div class="wrap">
            <h2 class="reveal">Fitur Sistem</h2>
            <p class="reveal">Empat layanan digital utama untuk pengurus dan karyawan BUMDESMA LKD TARUB.</p>
            <div class="grid">
                <div class="card reveal"><i class="bi bi-fingerprint"></i><h3>Absensi Digital</h3><p>Catat kehadiran masuk dan pulang harian dengan rekap otomatis.</p></div>
                <div class="card reveal"><i class="bi bi-file-earmark-text"></i><h3>Cuti & Izin</h3><p>Ajukan cuti, izin, atau sakit dan pantau status persetujuan.</p></div>
                <div class="card reveal"><i class="bi bi-cash-stack"></i><h3>Penggajian</h3><p>Lihat slip gaji, tunjangan, reward, dan potongan secara transparan.</p></div>
                <div class="card reveal"><i class="bi bi-bar-chart"></i><h3>Laporan</h3><p>Rekap kehadiran, cuti, dan penggajian yang siap diunduh PDF/Excel.</p></div>
            </div>
        </div>
    </section>
    <section class="kontak" id="kontak">
        <div class="wrap kontak-grid">
            <div class="reveal">
                <h2>Kontak Kami</h2>
                <p>Terbuka untuk informasi, kolaborasi, dan cerita baru dari masyarakat Tarub.</p>
                <div class="line"><i class="bi bi-geo-alt"></i><span>Tarub, Kabupaten Tegal, Jawa Tengah</span></div>
                <div class="line"><i class="bi bi-clock"></i><span>Senin - Jumat, 08.00 - 15.00 WIB</span></div>
                <div class="line"><i class="bi bi-envelope"></i><a href="mailto:info@bumdesmatarub.id">info@bumdesmatarub.id</a></div>
                <div class="line"><i class="bi bi-whatsapp"></i><a href="https://wa.me/6281234567890">+62 812-3456-7890</a></div>
            </div>
    </section>
    <footer><div class="wrap"><span>© <span id="yr">2026</span> BUMDESMA LKD TARUB. All Rights Reserved.</span><span>Media informasi resmi BUMDESMA LKD TARUB</span></div></footer>
    <script>document.getElementById('yr').textContent = new Date().getFullYear();(function(){var t=document.getElementById('navToggle'),l=document.getElementById('navLinks');if(!t||!l)return;t.addEventListener('click',function(){var o=l.classList.toggle('open');t.setAttribute('aria-expanded',o?'true':'false')});l.addEventListener('click',function(e){if(e.target.closest('a')){l.classList.remove('open');t.setAttribute('aria-expanded','false')}})})();(function(){var els=document.querySelectorAll('.reveal');if(!els.length)return;if(!('IntersectionObserver' in window)||window.matchMedia('(prefers-reduced-motion: reduce)').matches){els.forEach(function(el){el.classList.add('visible')});return}var io=new IntersectionObserver(function(entries){entries.forEach(function(en){if(en.isIntersecting){en.target.classList.add('visible');io.unobserve(en.target)}})},{threshold:.12});els.forEach(function(el){io.observe(el)})})();</script>
</body>
</html>
