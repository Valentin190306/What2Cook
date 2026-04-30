<section class="asistente-hero">
    <h1>Asistente de Cocina</h1>
    <p>Decime qué ingredientes tenés y te diré qué cocinar</p>

    <div class="tabs">
        <input type="radio" id="comidaUnica" name="modo" value="unica" checked>
        <label for="comidaUnica">Comida Única</label>
        <input type="radio" id="mealPrep" name="modo" value="prep">
        <label for="mealPrep">Meal Prep</label>
    </div>
</section>

<section class="form-panel">
    <h2>Encontrá la receta perfecta</h2>
    <p>Ingresá los ingredientes que tenés y te sugerimos recetas que puedas preparar.</p>

    <form class="ingredientes-form" action="/buscar-recetas" method="GET">
        <ul class="ingredientes-lista">
            <li>
                <span>Huevos <em>x3</em></span>
                <button type="button" class="btn-remove" aria-label="Quitar ingrediente">&times;</button>
            </li>
        </ul>

        <div class="input-row">
            <input type="text" name="ingrediente" placeholder="Ej: pollo, arroz, tomate...">
            <button type="button" class="btn-add" aria-label="Agregar ingrediente">+</button>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Buscar Recetas</button>
            <button type="submit" class="btn-secondary">Las más rápidas</button>
            <button type="submit" class="btn-secondary">Las más saludables</button>
        </div>
    </form>
</section>

<div class="recipe-grid">
    <?php for($i=0; $i<8; $i++): ?>
    <article onclick="window.location='/receta/1'" role="link" tabindex="0">
        <img src="/assets/img/placeholder.jpg" alt="Foto de la receta">
        <button type="button" onclick="event.stopPropagation()" aria-label="Agregar a favoritos"><img src="" alt=""></button>
        <h2>Receta de ejemplo</h2>
        <div class="recipe-meta">
            <span>Tiempo: 30 min</span>
            <span>Porciones: 4</span>
        </div>
        <div class="recipe-tags">
            <span>Vegetariano</span>
            <span>Keto</span>
        </div>
        <table>
            <thead>
                <tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr>
            </thead>
            <tbody>
                <tr><td>350</td><td>15g</td><td>40g</td><td>10g</td></tr>
            </tbody>
        </table>
        <a class="recipe-link" href="/receta/1" aria-hidden="true" tabindex="-1"></a>
    </article>
    <?php endfor; ?>
</div>
