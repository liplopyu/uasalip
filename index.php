<?php
// Koneksi dan Logika Utama
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "berita";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// Query untuk Slider
$sql_slider = "SELECT judul, slug, ringkasan, gambar_unggulan FROM berita WHERE status = 'published' AND gambar_unggulan IS NOT NULL AND gambar_unggulan != '' ORDER BY tanggal_publikasi DESC LIMIT 5";
$slider_result = $conn->query($sql_slider);

// Logika Paginasi, Pencarian, dan Kategori
$page_title = 'Beranda';
$articles_per_page = 5; // Sesuai permintaan: 5 berita per halaman
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $articles_per_page;
$query_string = '';
$base_sql = "SELECT b.judul, b.slug, b.ringkasan, b.gambar_unggulan, b.tanggal_publikasi, k.nama_kategori FROM berita b JOIN kategori k ON b.id_kategori = k.id";
$count_sql = "SELECT COUNT(b.id) as total FROM berita b JOIN kategori k ON b.id_kategori = k.id";
$where_clause = " WHERE b.status = 'published'";
$params = [];
$types = '';

if (!empty($_GET['q'])) {
    $search_term = '%' . $_GET['q'] . '%';
    $page_title = 'Hasil Pencarian: "' . htmlspecialchars($_GET['q']) . '"';
    $query_string = '&q=' . urlencode($_GET['q']);
    $where_clause .= " AND b.judul LIKE ?";
    $params[] = $search_term;
    $types .= 's';
} else if (!empty($_GET['kategori'])) {
    $kategori_slug = $_GET['kategori'];
    $page_title = 'Kategori: ' . ucfirst(htmlspecialchars($kategori_slug));
    $query_string = '&kategori=' . urlencode($kategori_slug);
    $where_clause .= " AND k.slug_kategori = ?";
    $params[] = $kategori_slug;
    $types .= 's';
}

$stmt_count = $conn->prepare($count_sql . $where_clause);
if (!empty($params)) $stmt_count->bind_param($types, ...$params);
$stmt_count->execute();
$total_articles = $stmt_count->get_result()->fetch_assoc()['total'];

$sql = $base_sql . $where_clause . " ORDER BY b.tanggal_publikasi DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $articles_per_page;
$params[] = $offset;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$total_pages = ceil($total_articles / $articles_per_page);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - BeritaNet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="navbar">
    <div class="container">
        <div class="navbar-left">
            <button id="category-toggle" class="category-toggle-btn">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            <a href="index.php" class="nav-logo">BeritaNet</a>
        </div>

        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link <?php echo (empty($_GET['kategori']) && empty($_GET['q'])) ? 'active' : ''; ?>">Home</a></li>
            <?php
            $kategori_nav_result = $conn->query("SELECT nama_kategori, slug_kategori FROM kategori ORDER BY nama_kategori ASC");
            while($kategori_nav = $kategori_nav_result->fetch_assoc()):
            ?>
                <li>
                    <a href="index.php?kategori=<?php echo $kategori_nav['slug_kategori']; ?>" class="nav-link <?php echo (($_GET['kategori'] ?? '') == $kategori_nav['slug_kategori']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($kategori_nav['nama_kategori']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>

        <div class="navbar-right">
            <div class="search-box">
                <form action="index.php" method="GET">
                    <input type="text" name="q" placeholder="Cari berita..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
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
    <aside id="category-sidebar" class="category-sidebar">
        <h3>Kategori Berita</h3>
        <ul class="category-list">
             <li><a href="index.php">Semua Kategori</a></li>
            <?php
            $kategori_sidebar_result = $conn->query("SELECT nama_kategori, slug_kategori FROM kategori ORDER BY nama_kategori ASC");
            if ($kategori_sidebar_result->num_rows > 0) {
                while($kategori_sidebar = $kategori_sidebar_result->fetch_assoc()):
            ?>
                <li>
                    <a href="index.php?kategori=<?php echo $kategori_sidebar['slug_kategori']; ?>">
                        <?php echo htmlspecialchars($kategori_sidebar['nama_kategori']); ?>
                    </a>
                </li>
            <?php endwhile; } ?>
        </ul>
    </aside>
    <div id="sidebar-overlay" class="sidebar-overlay"></div>

    <section class="hero-slider-section">
        <div class="swiper heroSwiper">
            <div class="swiper-wrapper">
                <?php if ($slider_result->num_rows > 0) { $slider_result->data_seek(0); while($slide = $slider_result->fetch_assoc()): ?>
                    <div class="swiper-slide" style="background-image: url('<?php echo htmlspecialchars($slide['gambar_unggulan']); ?>');">
                        <div class="hero-content">
                            <h1><?php echo htmlspecialchars($slide['judul']); ?></h1>
                            <p><?php echo htmlspecialchars($slide['ringkasan']); ?></p>
                            <a href="detail.php?slug=<?php echo htmlspecialchars($slide['slug']); ?>" class="btn btn-primary">Baca Selengkapnya</a>
                        </div>
                    </div>
                <?php endwhile; } ?>
            </div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
            <div class="swiper-pagination"></div>
        </div>
    </section>

    <div class="container" style="padding-top: 40px; padding-bottom: 40px;">
        <div class="content-area">
            <h2 class="page-title"><?php echo ($page_title !== 'Beranda') ? $page_title : 'Berita Terbaru'; ?></h2>
            <div class="news-grid">
                <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                    <a href="detail.php?slug=<?php echo htmlspecialchars($row['slug']); ?>" class="glass-card">
                        <img src="<?php echo htmlspecialchars($row['gambar_unggulan']); ?>" alt="<?php echo htmlspecialchars($row['judul']); ?>" class="news-image">
                        <div class="news-content">
                            <span class="news-category"><?php echo htmlspecialchars($row['nama_kategori']); ?></span>
                            <h3 class="news-title"><?php echo htmlspecialchars($row['judul']); ?></h3>
                            <div class="news-meta">
                                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($row['tanggal_publikasi'])); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endwhile; else: ?>
                    <p style="grid-column: 1 / -1; text-align: center;">Tidak ada berita yang ditemukan.</p>
                <?php endif; ?>
            </div>
            <div class="pagination">
                <?php if ($total_pages > 1): ?>
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $query_string; ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" class="<?php echo $i == $current_page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $query_string; ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
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

    // 2. INISIALISASI SWIPER SLIDER
    if (document.querySelector(".heroSwiper")) {
        var swiper = new Swiper(".heroSwiper", {
            loop: true,
            autoplay: { delay: 4000, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
        });
    }

    // 3. LOGIKA SIDEBAR KATEGORI
    const categoryToggleBtn = document.getElementById('category-toggle');
    const categorySidebar = document.getElementById('category-sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    if (categoryToggleBtn && categorySidebar && sidebarOverlay) {
        const openSidebar = () => { categorySidebar.classList.add('active'); sidebarOverlay.classList.add('active'); document.body.style.overflow = 'hidden'; };
        const closeSidebar = () => { categorySidebar.classList.remove('active'); sidebarOverlay.classList.remove('active'); document.body.style.overflow = ''; };
        categoryToggleBtn.addEventListener('click', (e) => { e.stopPropagation(); if (categorySidebar.classList.contains('active')) { closeSidebar(); } else { openSidebar(); } });
        sidebarOverlay.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && categorySidebar.classList.contains('active')) { closeSidebar(); } });
    }

    // 4. LOGIKA HAMBURGER NAVIGASI UTAMA (UNTUK MOBILE)
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