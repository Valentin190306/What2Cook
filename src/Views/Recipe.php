<?php
$title = "What2Cook - Receta #$id";
$styles = ['receta'];
?>
<article>
    <img src="/assets/img/placeholder.jpg" alt="Imagen del plato">
    <button type="button" aria-label="Agregar a favoritos">Agregar a favoritos</button>

    <h1><?= $recipeName ?? 'Nombre del plato' ?></h1>

    <dl class="receta-meta">
        <dt>
            <svg aria-hidden="true" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z" />
            </svg>
            Tiempo
        </dt>
        <dd>45 min</dd>
        <dt>
            <svg aria-hidden="true" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="20" height="20">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
            </svg>
            Porciones
        </dt>
        <dd>2</dd>
    </dl>

    <ul class="receta-tags">
        <li>Cena</li>
        <li>Libre de gluten</li>
        <li>Libre de lacteos</li>
        <li>Vegetariano</li>
    </ul>

    <section class="descripcion">
        <h2>Descripción</h2>
        <p>
            Esta es una receta deliciosa preparada con ingredientes frescos y naturales. 
            Ideal para compartir en familia o con amigos.
        </p>
    </section>

    <section class="ingredientes">
        <h2>Ingredientes</h2>
        <fieldset class="porciones">
            <legend>Porciones:</legend>
            <button type="button" aria-label="Reducir porciones">−</button>
            <output>2</output>
            <button type="button" aria-label="Aumentar porciones">+</button>
        </fieldset>
        <ul>
            <?php for($i=1; $i<=4; $i++): ?>
            <li>
                <img src="/assets/img/placeholder.jpg" alt="Ingrediente <?= $i ?>" width="40">
                <span class="nombre">Ingrediente <?= $i ?></span>
                <span class="cantidad">100 · g</span>
            </li>
            <?php endfor; ?>
        </ul>
    </section>

    <section class="preparacion">
        <h2>Preparación</h2>
        <ol>
            <li>Comenzar lavando todos los ingredientes frescos.</li>
            <li>Cortar los vegetales en cubos pequeños y reservar.</li>
            <li>Cocinar a fuego lento durante 20 minutos.</li>
            <li>Servir caliente y disfrutar.</li>
        </ol>
    </section>
</article>
