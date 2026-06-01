<?php
$title   = 'Dietas y Nutrición — What2Cook';
$styles  = ['informacionDietas'];
$scripts = ['diets'];
?>

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