<?php
$title = 'Catálogo de Recetas - What2Cook';
$styles = ['catalogoRecetas'];
?>
<section class="catalogo-header">
    <h1>Catálogo de Recetas</h1>
    <p>Explorá nuestra colección de recetas deliciosas y saludables.</p>
</section>

<form class="search-bar" action="/buscar-recetas" method="GET">
    <input type="search" name="busqueda" placeholder="Buscar...">
    <button type="submit"><img src="" alt="Buscar"></button>
</form>

<fieldset class="filtros">
    <legend>Filtrar por dieta:</legend>
    <div class="filtros-opciones">
        <?php 
        $dietas = [
            'sin-gluten' => 'Sin Gluten',
            'vegetariano' => 'Vegetariano',
            'vegano' => 'Vegano',
            'pescetariano' => 'Pescetariano',
            'cetogenica' => 'Cetogénica',
            'paleo' => 'Paleo',
            'primal' => 'Primal',
            'lacto-vegetariano' => 'Lacto-vegetariano',
            'ovo-vegetariano' => 'Ovo-vegetariano',
            'whole30' => 'Whole30'
        ];
        foreach($dietas as $val => $label): ?>
            <label><input type="checkbox" name="dieta[]" value="<?= $val ?>"> <span><?= $label ?></span></label>
        <?php endforeach; ?>
    </div>
</fieldset>

<div class="recipe-grid">
    <?php for($i=0; $i<8; $i++): ?>
    <article onclick="window.location='/receta/1'" role="link" tabindex="0">
        <img src="/assets/img/placeholder.jpg" alt="Foto de la receta">
        <button type="button" onclick="event.stopPropagation()"><img src="" alt="Favorito"></button>
        <h2>Receta Saludable</h2>
        <div class="recipe-meta">
            <span>Tiempo: 45 min</span>
            <span>Porciones: 2</span>
        </div>
        <div class="recipe-tags">
            <span>Saludable</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Kcal</th>
                    <th>Proteína</th>
                    <th>Carbs</th>
                    <th>Grasa</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>450</td>
                    <td>25g</td>
                    <td>50g</td>
                    <td>15g</td>
                </tr>
            </tbody>
        </table>
        <a class="recipe-link" href="/receta/1" aria-hidden="true" tabindex="-1"></a>
    </article>
    <?php endfor; ?>
</div>
