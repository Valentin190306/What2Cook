<?php
$title = 'Asistente de Dietas - What2Cook';
$styles = ['asistenteDieta'];
?>
<section class="dieta-hero">
    <h1>Asistente de Dietas</h1>
    <p>Creá un plan de comidas personalizado según tus objetivos nutricionales</p>
</section>

<section class="config-panel">
    <h2>Asistente de Dietas</h2>

    <form class="config-form" action="/generar-plan" method="POST">

        <div class="form-grid">
            <div class="form-field">
                <label for="duracion">Duración del plan</label>
                <select id="duracion" name="duracion" required>
                    <option value="">Seleccioná una duración</option>
                    <option value="7">7 días (1 semana)</option>
                    <option value="14">14 días (2 semanas)</option>
                    <option value="30">30 días (1 mes)</option>
                </select>
            </div>

            <div class="form-field">
                <label for="dieta">Tipo de dieta (opcional)</label>
                <select id="dieta" name="dieta">
                    <option value="">Seleccioná una dieta</option>
                    <option value="vegetariana">Vegetariana</option>
                    <option value="vegana">Vegana</option>
                    <option value="keto">Keto</option>
                </select>
            </div>
        </div>

        <fieldset class="nutri-fieldset">
            <legend>Configuración del Plan</legend>
            <p class="fieldset-desc">Definí tus objetivos nutricionales diarios</p>

            <div class="nutri-grid">
                <div class="form-field">
                    <label for="calorias">Calorías diarias (kcal)</label>
                    <input type="number" id="calorias" name="calorias" placeholder="ej: 2000">
                </div>
                <div class="form-field">
                    <label for="proteinas">Proteínas (g)</label>
                    <input type="number" id="proteinas" name="proteinas" placeholder="ej: 150">
                </div>
                <div class="form-field">
                    <label for="carbohidratos">Carbohidratos (g)</label>
                    <input type="number" id="carbohidratos" name="carbohidratos" placeholder="ej: 200">
                </div>
                <div class="form-field">
                    <label for="grasas">Grasas (g)</label>
                    <input type="number" id="grasas" name="grasas" placeholder="ej: 70">
                </div>
            </div>
        </fieldset>

        <div class="form-actions">
            <button type="submit" class="btn-primary">Generar Plan</button>
        </div>
    </form>
</section>

<!-- PANEL DEL PLAN SEMANAL -->
<section class="plan-panel">

    <div class="plan-header">
        <div class="form-field">
            <label for="semana">Semana</label>
            <select id="semana" name="semana">
                <option value="1">Semana 1</option>
                <option value="2">Semana 2</option>
                <option value="3">Semana 3</option>
                <option value="4">Semana 4</option>
            </select>
        </div>
    </div>

    <!-- Tabs de días -->
    <div class="tabs-dias">
        <input type="radio" id="lunes" name="dia" value="lunes" checked>
        <label for="lunes">Lunes</label>
        <!-- ...otros días... -->
        <input type="radio" id="martes" name="dia" value="martes">
        <label for="martes">Martes</label>
        <input type="radio" id="miercoles" name="dia" value="miercoles">
        <label for="miercoles">Miércoles</label>
        <input type="radio" id="jueves" name="dia" value="jueves">
        <label for="jueves">Jueves</label>
        <input type="radio" id="viernes" name="dia" value="viernes">
        <label for="viernes">Viernes</label>
        <input type="radio" id="sabado" name="dia" value="sabado">
        <label for="sabado">Sábado</label>
        <input type="radio" id="domingo" name="dia" value="domingo">
        <label for="domingo">Domingo</label>
    </div>

    <!-- Comidas del día -->
    <div class="comidas-grid">
        <?php foreach(['Desayuno', 'Almuerzo', 'Merienda', 'Cena'] as $comida): ?>
        <article class="comida">
            <header class="comida-header"><h3><?= $comida ?></h3></header>
            <div class="comida-card">
                <img src="/assets/img/placeholder.jpg" alt="Foto de la receta">
                <button type="button" aria-label="Agregar a favoritos"><img src="" alt=""></button>
                <h4>Receta del plan</h4>
                <div class="recipe-meta">
                    <span>Tiempo: 20 min</span>
                    <span>Porciones: 1</span>
                </div>
                <div class="recipe-tags">
                    <span>Saludable</span>
                </div>
                <table>
                    <thead>
                        <tr><th>Kcal</th><th>Proteína</th><th>Carbs</th><th>Grasa</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>400</td><td>20g</td><td>30g</td><td>12g</td></tr>
                    </tbody>
                </table>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <div class="plan-actions">
        <button type="button" class="btn-save">Guardar plan</button>
    </div>
</section>
