<?php // File: includes/header-dinamis.php (dengan perbaikan path CSS) ?>
<head>
  <meta charset="utf-8">
  <title>Pelaporan Bangunan Gedung Pemerintah</title>
  <!-- Perubahan: Menggunakan path absolut untuk file CSS -->
  <link rel="stylesheet" href="/putr/bangunan/assets/style.css">
</head>

<header class="header-selebaran">
    <div class="logo-selebaran">
        <!-- Pastikan URL logo ini juga benar untuk domain Anda -->
        <img id="logoToko" src="https://dputr.tasikmalayakota.go.id/wp-content/uploads/2025/04/Logo-PUTR.png" alt="Logo Toko">
    </div>
    <nav class="menu-selebaran">
        <ul id="menuDinamisToko">
            <li>Memuat menu...</li>
        </ul>
    </nav>
    <button class="hamburger-icon" aria-label="Toggle Menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
</header>
<script>
    // --- PENGATURAN ---
    // Perbaikan: Ubah alamatWordPressAPI agar menunjuk ke instalasi WordPress di domain cPanel Anda
    const alamatWordPressAPI = 'https://dputr.tasikmalayakota.go.id/wp-json'; 
    // Jika WordPress Anda berada di subdirektori (misal: /wordpress/), maka:
    // const alamatWordPressAPI = 'https://dputr.tasikmalayakota.go.id/wordpress/wp-json';
    
    const namaMenuSlug = 'menu-1'; 
    // Perbaikan: Pastikan URL gambar logo manual juga menunjuk ke domain yang benar
    const urlGambarLogoManual = 'https://dputr.tasikmalayakota.go.id/wp-content/uploads/2025/04/Logo-PUTR.png';

    // --- FUNGSI BANTU ---
    function decodeHtmlEntities(text) {
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        return textArea.value;
    }

    // --- KODE LOGO ---
    const elemenLogo = document.getElementById('logoToko');
    if (urlGambarLogoManual && urlGambarLogoManual.startsWith('http') && elemenLogo) {
        elemenLogo.src = urlGambarLogoManual;
    } else if (elemenLogo) {
        elemenLogo.alt = "URL Logo belum diatur atau tidak valid";
    }

    // --- KODE MENU & HAMBURGER ---
    const elemenMenuUl = document.getElementById('menuDinamisToko');
    const hamburgerButton = document.querySelector('.hamburger-icon');

    function buatMenuItem(itemData) {
        const listItem = document.createElement('li');
        const link = document.createElement('a');
        link.href = itemData.url || '#';
        link.textContent = decodeHtmlEntities(itemData.title);
        listItem.appendChild(link);

        if (itemData.child_items && itemData.child_items.length > 0) {
            listItem.classList.add('menu-item-has-children');
            const subMenuUl = document.createElement('ul');
            subMenuUl.classList.add('submenu');

            itemData.child_items.forEach(childItem => {
                subMenuUl.appendChild(buatMenuItem(childItem));
            });
            listItem.appendChild(subMenuUl);

            link.addEventListener('click', function(event) {
                const isMobileView = getComputedStyle(hamburgerButton).display !== 'none';
                if (isMobileView) {
                    event.preventDefault();
                    listItem.classList.toggle('submenu-induk-terbuka');
                    subMenuUl.classList.toggle('submenu-terbuka');
                }
            });
        }
        return listItem;
    }

    async function ambilDanTampilkanMenu() {
        // Menggunakan alamatWordPressAPI yang sudah diatur dengan benar
        const alamatTeleponMenu = `${alamatWordPressAPI}/menus/v1/menus/${namaMenuSlug}`;
        try {
            const respons = await fetch(alamatTeleponMenu);
            if (!respons.ok) { 
                // Log error lebih detail untuk debugging
                console.error(`Gagal mengambil menu. Status: ${respons.status}. URL: ${alamatTeleponMenu}`);
                throw new Error(`Gagal mengambil menu. Status: ${respons.status}.`); 
            }
            const dataMenu = await respons.json();
            elemenMenuUl.innerHTML = '';
            if (dataMenu.items && dataMenu.items.length > 0) {
                dataMenu.items.forEach(item => { elemenMenuUl.appendChild(buatMenuItem(item)); });
            } else { elemenMenuUl.innerHTML = '<li>Menu tidak ditemukan.</li>'; }
        } catch (error) {
            console.error('Error saat mengambil menu:', error);
            elemenMenuUl.innerHTML = `<li>Gagal memuat menu.</li>`;
        }
    }

    if (hamburgerButton && elemenMenuUl) {
        hamburgerButton.addEventListener('click', () => {
            elemenMenuUl.classList.toggle('active');
            hamburgerButton.classList.toggle('open');
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ambilDanTampilkanMenu);
    } else {
        ambilDanTampilkanMenu();
    }
</script>
