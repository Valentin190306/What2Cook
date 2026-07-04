<?php
$uid = \App\Core\Session::userId();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'what2cook.app';
$baseUrl = "{$scheme}://{$host}";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'What2Cook') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Planificá tus comidas semanales, buscá recetas por ingredientes con nuestro asistente inteligente y gestioná tus listas de compras en What2Cook.') ?>">

    <?php
    $canonicalUrl = $canonicalUrl ?? ($baseUrl . explode('?', $_SERVER['REQUEST_URI'] ?? '')[0]);
    if (!empty($noindex)): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php else: ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($ogTitle ?? $title ?? 'What2Cook') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($ogDescription ?? $metaDescription ?? 'Planificá tus comidas semanales, buscá recetas por ingredientes con nuestro asistente inteligente y gestioná tus listas de compras en What2Cook.') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($ogType ?? 'website') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($ogImage ?? $baseUrl . '/assets/img/LogoW2C_conFONDO_1.png') ?>">
    <meta property="og:image:width" content="1019">
    <meta property="og:image:height" content="679">
    <meta property="og:image:type" content="image/png">
    <meta property="og:site_name" content="What2Cook">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Schema: WebSite + SearchAction -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "What2Cook",
        "url": "<?= $baseUrl ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": {
                "@type": "EntryPoint",
                "urlTemplate": "<?= $baseUrl ?>/recetas?query={search_term_string}"
            },
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <!-- Schema: Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "What2Cook",
        "url": "<?= $baseUrl ?>",
        "logo": "<?= $baseUrl ?>/assets/img/LogoW2C_1.png",
        "description": "Tu plataforma de recetas y planificación de comidas",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "Buenos Aires",
            "addressCountry": "AR"
        }
    }
    </script>
    
    <!-- Estilos base y comunes -->
    <link rel="stylesheet" href="/assets/styles/base.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/styles/layout.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/styles/components.css?v=<?= time() ?>">
    
    <!-- Estilo específico de la página -->
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link rel="stylesheet" href="/assets/styles/<?= $style ?>.css?v=<?= time() ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Estilos de impresión -->
    <link rel="stylesheet" href="/assets/styles/print.css?v=<?= time() ?>" media="print">
</head>
<body<?php if ($uid !== null): ?> data-user-authed<?php endif; ?>>
    <header>
        <a href="/"><img src="/assets/img/LogoW2C_1.png" alt="Logo W2C"></a>
        <!-- <strong>What2Cook</strong> -->
        <nav>
            <ul>
                <li><a href="/">Inicio</a></li>
                <li><a href="/asistente-cocina">Asistente de Cocina</a></li>
                <li><a href="/asistente-dieta">Asistente de Dietas</a></li>
                <li><a href="/recetas">Catálogo</a></li>
                <li><a href="/diets">Dietas</a></li>
                <li><a href="/about">Nosotros</a></li>
                <?php if ($uid === null): ?>
                    <li><a href="/login">Accedé</a></li>
                    <li class="nav-unit-selector">
                        <select id="nav-unit-system" aria-label="Sistema de unidades">
                            <option value="metric">Métrico</option>
                            <option value="imperial">Imperial</option>
                            <option value="us">US</option>
                        </select>
                    </li>
                <?php else: ?>
                    <li><a href="/favoritos">Favoritos</a></li>
                    <li><a href="/mis-planes">Mis Planes</a></li>
                    <li><a href="/lista-compras">Lista de Compras</a></li>
                    <li><a href="/perfil">Perfil</a></li>
                    <li>
                        <form action="/logout" method="POST" class="logout-form">
                            <input type="hidden" name="_csrf"
                                   value="<?= htmlspecialchars(\App\Core\Session::csrfToken()) ?>">
                            <button type="submit">Cerrar sesion</button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <section>
            <img src="/assets/img/LogoW2C_conFONDO_2.png" alt="Logo W2C">
            <strong>What2Cook</strong>
            <p>Tu plataforma de recetas y planificación de comidas</p>
        </section>

        <h3>Explorar</h3>
        <address>
            <ul>
                <li><a href="/asistente-cocina">Asistente de Cocina</a></li>
                <li><a href="/asistente-dieta">Asistente de Dietas</a></li>
                <li><a href="/recetas">Catálogo de Recetas</a></li>
                <li><a href="/diets">Información de Dietas</a></li>
            </ul>
        </address>

        <h3>Mi cuenta</h3>
        <address>
            <ul>
                <li><a href="/favoritos">Mis favoritos</a></li>
                <li><a href="/mis-planes">Mis planes</a></li>
                <li><a href="/lista-compras">Lista de compras</a></li>
                <li><a href="<?= $uid !== null ? '/perfil' : '#' ?>">Perfil</a></li>
            </ul>
        </address>

        <h3>Sobre What2Cook</h3>
        <address>
            <p>Desarrollado en Buenos Aires, Argentina</p>
            <p>Datos de Spoonacular API</p>
            <p>&copy; 2026 What2Cook</p>
        </address>

        <nav class="social" aria-label="Redes sociales">
            <!-- Iconos SVG (abreviados por brevedad en este bloque, pero manteniéndolos funcionales) -->
            <a href="#" aria-label="X (Twitter)">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.264 5.638 5.9-5.638Zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                </svg>
            </a>
            <a href="#" aria-label="Instagram">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                </svg>
            </a>
        </nav>
    </footer>
    <script src="/assets/js/utils.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/UnitPreferences.js?v=<?= time() ?>" defer></script>
    <script src="/assets/js/UnitConversion.js?v=<?= time() ?>" defer></script>
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="/assets/js/<?= $script ?>.js?v=<?= time() ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <script src="/assets/js/sw-register.js" defer></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        UnitPreferences.syncFromServer();

        var navSelector = document.getElementById('nav-unit-system');
        if (navSelector) {
            navSelector.value = UnitPreferences.getPreferredSystem();
            navSelector.addEventListener('change', function (e) {
                UnitPreferences.setPreferredSystem(e.target.value);
            });
        }

        UnitPreferences.onSystemChange(function (system) {
            if (navSelector) {
                navSelector.value = system;
            }
        });
    });
    </script>
</body>
</html>