<?php
$title = 'Asistente de Dietas - What2Cook';
$styles = ['asistenteDieta'];
$scripts = ['diet-helper'];
?>
<section class="dieta-hero">
    <h1>Asistente de Dietas</h1>
    <p>Creá un plan de comidas personalizado según tus objetivos nutricionales</p>
</section>

<section class="config-panel">
    <h2>Asistente de Dietas</h2>

    <form class="config-form" id="diet-form">

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
<p id="plan-status"></p>
<section class="plan-panel" id="plan-panel" hidden>

    <div class="plan-header">
        <div class="form-field">
            <label for="semana">Semana</label>
            <select id="semana-select" name="semana">
                <option value="1">Semana 1</option>
                <option value="2">Semana 2</option>
                <option value="3">Semana 3</option>
                <option value="4">Semana 4</option>
            </select>
        </div>
    </div>

    <!-- Tabs de días -->
    <div class="tabs-dias" id="tabs-dias">
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
    <div class="comidas-grid" id="comidas-grid">
        <!-- Renderizado dinámico con JS -->
    </div>

    <div class="dia-totales" id="dia-totales"></div>

    <div class="plan-actions">
        <button type="button" class="btn-save" id="btn-guardar">Guardar plan</button>
    </div>
</section>
