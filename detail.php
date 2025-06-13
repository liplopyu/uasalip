<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "berita";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

if (!isset($_GET['slug'])) {
    header("Location: index.php");
    exit();
}

$slug = $_GET['slug'];
$berita = null;
$page_title = 'Berita Tidak Ditemukan';

$sql = "SELECT b.judul, b.isi, b.gambar_unggulan, b.tanggal_publikasi, k.nama_kategori, p.nama_lengkap AS nama_penulis
        FROM berita b 
        JOIN kategori k ON b.id_kategori = k.id
        JOIN penulis p ON b.id_penulis = p.id
        WHERE b.slug = ? AND b.status = 'published' LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $berita = $result->fetch_assoc();
    $page_title = $berita['judul'];
} else {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - BeritaNet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="navbar-left">
            <a href="index.php" class="nav-logo">BeritaNet</a>
        </div>
        <ul class="nav-menu">
             <li><a href="index.php" class="nav-link">Home</a></li>
            <?php
            $kategori_nav_result_detail = $conn->query("SELECT nama_kategori, slug_kategori FROM kategori ORDER BY nama_kategori ASC");
            while($kategori_nav = $kategori_nav_result_detail->fetch_assoc()):
            ?>
                <li>
                    <a href="index.php?kategori=<?php echo $kategori_nav['slug_kategori']; ?>" class="nav-link">
                        <?php echo htmlspecialchars($kategori_nav['nama_kategori']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
        <div class="navbar-right">
            <div class="search-box">
                <form action="index.php" method="GET">
                    <input type="text" name="q" placeholder="Cari berita..." value="">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="theme-switch-wrapper">
                <i class="fas fa-sun"></i>
                <label class="theme-switch" for="checkbox">
                    <input type="checkbox" id="checkbox" />
                    <div class="slider round"></div>
                </label>
                <i class="fas fa-moon"></i>
            </div>
            <div class="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container" style="padding-top: 60px; padding-bottom: 60px;">
        <?php if ($berita): ?>
            <article>
                <header class="article-header">
                    <span class="news-category"><?php echo htmlspecialchars($berita['nama_kategori']); ?></span>
                    <h1><?php echo htmlspecialchars($berita['judul']); ?></h1>
                    <div class="news-meta">
                        <span>Oleh: <?php echo htmlspecialchars($berita['nama_penulis']); ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('d F Y', strtotime($berita['tanggal_publikasi'])); ?></span>
                    </div>
                </header>
                <img src="<?php echo htmlspecialchars($berita['gambar_unggulan']); ?>" alt="<?php echo htmlspecialchars($berita['judul']); ?>" class="article-image">
                <div class="article-content">
                    <?php echo nl2br($berita['isi']); // Gunakan nl2br tapi tidak htmlspecialchars agar tag HTML dari editor bisa render ?>
                </div>
            </article>
        <?php else: ?>
            <div class="article-header">
                <h1>404 - Berita Tidak Ditemukan</h1>
                <p>Maaf, berita yang Anda cari tidak ada atau telah dipindahkan.</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer>
    <div class="container footer-content">
         <a href="index.php" class="nav-logo">BeritaNet</a>
         <div class="contact-info">
             <p>Jalan Bunga Cempaka, Medan, Indonesia</p>
             <p>Email: alifaditya398@gmail.com | Telp: (+62) 815-3639-7713</p>
         </div>
        <p>&copy; <?php echo date('Y'); ?> All Right Reserved BeritaNet.</p>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. LOGIKA THEME SWITCH (DARK MODE)
    const themeSwitch = document.getElementById('checkbox');
    const applyTheme = (theme) => {
        if (theme === 'dark-mode') {
            document.body.classList.add('dark-mode');
            if (themeSwitch) themeSwitch.checked = true;
        } else {
            document.body.classList.remove('dark-mode');
            if (themeSwitch) themeSwitch.checked = false;
        }
    };
    const savedTheme = localStorage.getItem('theme');
    applyTheme(savedTheme || 'light-mode');
    if (themeSwitch) {
        themeSwitch.addEventListener('change', function() {
            let theme = this.checked ? 'dark-mode' : 'light-mode';
            applyTheme(theme);
            localStorage.setItem('theme', theme);
        });
    }

    // 2. LOGIKA HAMBURGER NAVIGASI UTAMA (UNTUK MOBILE)
    const mainHamburger = document.querySelector('.navbar .hamburger');
    const navMenu = document.querySelector('.navbar .nav-menu');
    if(mainHamburger && navMenu) {
        mainHamburger.addEventListener('click', () => {
            mainHamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
});
</script>
</body>
</html>
<?php $conn->close(); ?>