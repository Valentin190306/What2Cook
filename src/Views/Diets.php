<?php
$title   = 'Dietas y Nutrición — What2Cook';
$styles  = ['informacionDietas'];
$scripts = ['diets'];
$metaDescription = 'Conocé las bases, alimentos permitidos y restringidos, beneficios y consideraciones de dietas como la Cetogénica, Vegana, Paleo, Libre de Gluten y más.';

$scheme = (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '8080') ? 'http' : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
$host   = $_SERVER['HTTP_HOST'] ?? 'what2cook.app';
$baseUrl = "{$scheme}://{$host}";
?>
<!-- Schema: BreadcrumbList -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "Inicio", "item": "<?= htmlspecialchars($baseUrl) ?>/"},
        {"@type": "ListItem", "position": 2, "name": "Dietas y Nutrición", "item": "<?= htmlspecialchars($baseUrl) ?>/diets"}
    ]
}
</script>

<!-- Schema: ItemList -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "ItemList",
    "name": "Dietas y Nutrición",
    "description": "Información detallada sobre diferentes tipos de dietas disponibles en What2Cook.",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Dieta Libre de Gluten",
            "description": "La dieta libre de gluten consiste en eliminar completamente el gluten, una proteína presente principalmente en el trigo, la cebada y el centeno."
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "Dieta Cetogénica",
            "description": "La dieta cetogénica (o keto) es un patrón alimentario caracterizado por un consumo muy bajo de carbohidratos, moderado de proteínas y alto de grasas."
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": "Dieta Vegetariana",
            "description": "La dieta vegetariana es un patrón alimentario basado principalmente en alimentos de origen vegetal que excluye el consumo de carne y pescado."
        },
        {
            "@type": "ListItem",
            "position": 4,
            "name": "Dieta Lacto-Vegetariana",
            "description": "La dieta lacto-vegetariana es una variante de la dieta vegetariana que excluye el consumo de carne, pescado y huevos, pero permite la incorporación de leche y productos lácteos."
        },
        {
            "@type": "ListItem",
            "position": 5,
            "name": "Dieta Ovo-Vegetariana",
            "description": "La dieta ovo-vegetariana es una variante de la dieta vegetariana que excluye el consumo de carne, pescado y productos lácteos, pero permite el consumo de huevos."
        },
        {
            "@type": "ListItem",
            "position": 6,
            "name": "Dieta Vegana",
            "description": "La dieta vegana es un patrón alimentario basado exclusivamente en alimentos de origen vegetal, que excluye todos los productos y subproductos de origen animal."
        },
        {
            "@type": "ListItem",
            "position": 7,
            "name": "Dieta Pescetariana",
            "description": "La dieta pescetariana es un patrón alimentario basado principalmente en alimentos de origen vegetal que excluye el consumo de carne vacuna, porcina y aviar, pero permite incluir pescados y mariscos."
        },
        {
            "@type": "ListItem",
            "position": 8,
            "name": "Dieta Paleo",
            "description": "La dieta paleo (o paleolítica) es un patrón alimentario inspirado en los alimentos que se presume consumían las poblaciones humanas durante el Paleolítico."
        },
        {
            "@type": "ListItem",
            "position": 9,
            "name": "Dieta Primal",
            "description": "La dieta primal es un patrón alimentario inspirado en la alimentación ancestral, similar a la dieta paleo, pero generalmente más flexible en ciertos aspectos."
        },
        {
            "@type": "ListItem",
            "position": 10,
            "name": "Dieta Whole30",
            "description": "La dieta Whole30 es un programa alimentario de 30 días enfocado en eliminar temporalmente ciertos grupos de alimentos considerados potencialmente problemáticos."
        }
    ]
}
</script>

<header class="page-header">
    <h1>Información de Dietas</h1>
    <p>
        Conocé más sobre los diferentes tipos de dietas<br>
        y encontrá la que mejor se adapte a tus necesidades
    </p>
</header>

<div class="dietas-layout">

    <!-- ===== Sidebar ===== -->
    <aside class="dietas-sidebar">

        <!-- Checkbox hack: controla apertura en mobile -->
        <input type="checkbox" id="menu-toggle" class="menu-toggle-checkbox" aria-hidden="true">

        <label for="menu-toggle" class="sidebar-toggle" aria-label="Menú de dietas">
            <span class="sidebar-title">Dietas</span>
            <svg class="hamburger-icon"
                 viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <line x1="3" y1="6"  x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </label>

        <nav class="sidebar-nav" aria-label="Tipos de dieta">
            <ul>
                <li><a href="#" class="diet-link" data-diet="libre-gluten">Libre de Gluten</a></li>
                <li><a href="#" class="diet-link" data-diet="cetogenica">Cetogénica</a></li>
                <li><a href="#" class="diet-link" data-diet="vegetariana">Vegetariana</a></li>
                <li><a href="#" class="diet-link" data-diet="lacto-vegetariana">Lacto-Vegetariana</a></li>
                <li><a href="#" class="diet-link" data-diet="ovo-vegetariana">Ovo-Vegetariana</a></li>
                <li><a href="#" class="diet-link" data-diet="vegana">Vegana</a></li>
                <li><a href="#" class="diet-link" data-diet="pescetariana">Pescetariana</a></li>
                <li><a href="#" class="diet-link" data-diet="paleo">Paleo</a></li>
                <li><a href="#" class="diet-link" data-diet="primal">Primal</a></li>
                <li><a href="#" class="diet-link" data-diet="whole30">Whole30</a></li>
            </ul>
        </nav>

    </aside>

    <!-- ===== Contenido principal ===== -->
    <section class="dieta-content" id="dieta-content" aria-live="polite">
        <!-- Renderizado por diets.js -->
    </section>

</div>