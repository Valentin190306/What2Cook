/**
 * diets.js — What2Cook
 * Navegación entre dietas con menú hamburguesa en mobile.
 * Los datos están embebidos aquí para una experiencia 100% dinámica (sin recargas).
 */

/* ============================================================
   DATOS DE DIETAS
   ============================================================ */
const DIETAS = {
    'libre-gluten': {
        nombre: 'Dieta Libre de Gluten',
        descripcion: 'La dieta libre de gluten consiste en eliminar completamente el gluten, una proteína presente principalmente en el trigo, la cebada y el centeno. Es indispensable para personas con enfermedad celíaca, sensibilidad al gluten no celíaca o alergia al trigo. También puede ser adoptada por otras personas según recomendaciones médicas o preferencias alimentarias.',
        permitidos: [
            'Frutas y verduras',
            'Carnes, pescados y huevos',
            'Leche y productos lácteos (sin agregados con gluten)',
            'Legumbres',
            'Arroz',
            'Maíz',
            'Papa y batata',
            'Quinoa',
            'Amaranto',
            'Mijo',
            'Frutos secos y semillas',
            'Harinas y productos certificados "sin gluten" o "libres de gluten"'
        ],
        restringidos: [
            'Trigo',
            'Cebada',
            'Centeno',
            'Productos elaborados con trigo, cebada o centeno',
            'Panes, pastas, galletitas y repostería tradicionales',
            'Cerveza tradicional',
            'Cereales de desayuno con gluten',
            'Alimentos procesados sin certificación libre de gluten',
            'Salsas, aderezos o embutidos que puedan contener gluten oculto'
        ],
        beneficios: [
            'Esencial para controlar la enfermedad celíaca',
            'Reduce síntomas digestivos relacionados con el gluten',
            'Favorece la recuperación del intestino en personas celíacas',
            'Puede disminuir inflamación y malestar en personas sensibles al gluten',
            'Promueve una mayor atención a los ingredientes y la composición de los alimentos'
        ],
        consideraciones: [
            'Requiere leer cuidadosamente etiquetas e ingredientes',
            'Existe riesgo de contaminación cruzada durante la preparación de alimentos',
            'No todos los productos naturalmente libres de gluten son saludables',
            'Puede resultar más costosa que una alimentación convencional',
            'No se recomienda eliminar el gluten sin evaluación profesional cuando no existe indicación médica',
            'Es importante mantener una dieta equilibrada para evitar deficiencias nutricionales'
        ]
    },

    'cetogenica': {
        nombre: 'Dieta Cetogénica',
        descripcion: 'La dieta cetogénica (o keto) es un patrón alimentario caracterizado por un consumo muy bajo de carbohidratos, moderado de proteínas y alto de grasas. Su objetivo es inducir un estado metabólico llamado cetosis, en el cual el cuerpo utiliza principalmente grasas como fuente de energía en lugar de carbohidratos.',
        permitidos: [
            'Carnes y aves',
            'Pescados y mariscos',
            'Huevos',
            'Quesos y lácteos altos en grasa',
            'Aceites saludables (oliva, coco, palta)',
            'Palta (aguacate)',
            'Frutos secos y semillas',
            'Verduras bajas en carbohidratos',
            'Manteca y crema',
            'Aceitunas',
            'Bebidas sin azúcar'
        ],
        restringidos: [
            'Azúcar y dulces',
            'Pan, harinas y productos de panadería',
            'Pastas, arroz y cereales',
            'Papas, batatas y otros tubérculos ricos en almidón',
            'Legumbres (según la versión de la dieta)',
            'Frutas con alto contenido de azúcar',
            'Gaseosas y bebidas azucaradas',
            'Jugos industriales',
            'Snacks ultraprocesados ricos en carbohidratos',
            'Alcohol con alto contenido de azúcar o carbohidratos'
        ],
        beneficios: [
            'Puede favorecer la pérdida de peso en algunas personas',
            'Ayuda a reducir el consumo de azúcares refinados y ultraprocesados',
            'Puede contribuir al control de la glucosa en ciertos contextos clínicos',
            'Puede generar mayor sensación de saciedad',
            'Tiene aplicaciones terapéuticas específicas, como en algunos casos de epilepsia'
        ],
        consideraciones: [
            'Requiere una planificación cuidadosa de macronutrientes',
            'Puede provocar efectos iniciales temporales ("gripe keto"), como fatiga o dolor de cabeza',
            'No resulta adecuada para todas las personas o condiciones de salud',
            'Puede ser difícil de mantener a largo plazo debido a sus restricciones',
            'Es importante controlar la calidad de las grasas consumidas',
            'Se recomienda seguimiento profesional para evitar desequilibrios nutricionales'
        ]
    },

    'vegetariana': {
        nombre: 'Dieta Vegetariana',
        descripcion: 'La dieta vegetariana es un patrón alimentario basado principalmente en alimentos de origen vegetal que excluye el consumo de carne y pescado. Dependiendo de su variante, puede incluir productos de origen animal como huevos, leche y derivados. Su adopción puede responder a motivos de salud, éticos, ambientales o culturales.',
        permitidos: [
            'Frutas y verduras',
            'Cereales y granos',
            'Legumbres',
            'Frutos secos y semillas',
            'Aceites vegetales',
            'Tofu, tempeh y otros derivados de soja',
            'Leche y productos lácteos (según la variante)',
            'Huevos (según la variante)',
            'Hierbas, especias y condimentos'
        ],
        restringidos: [
            'Carne vacuna',
            'Carne porcina',
            'Carne aviar',
            'Pescados y mariscos',
            'Embutidos y fiambres de origen animal',
            'Caldos y productos elaborados con carne o pescado',
            'Gelatina de origen animal (en algunas variantes)',
            'Otros productos animales, dependiendo del tipo de vegetarianismo'
        ],
        beneficios: [
            'Puede aumentar el consumo de frutas, verduras y fibra',
            'Puede contribuir al cuidado de la salud cardiovascular',
            'Favorece una alimentación basada en alimentos vegetales variados',
            'Puede reducir el consumo de grasas saturadas y alimentos ultraprocesados',
            'Puede alinearse con objetivos éticos y ambientales'
        ],
        consideraciones: [
            'Requiere planificación para cubrir adecuadamente proteínas, hierro, vitamina B12 y otros nutrientes clave',
            'Algunas variantes pueden necesitar suplementación nutricional',
            'Es importante mantener variedad y equilibrio en la alimentación',
            'No todos los productos vegetarianos industrializados son necesariamente saludables',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'Puede ser útil contar con orientación profesional al realizar cambios importantes en la dieta'
        ]
    },

    'lacto-vegetariana': {
        nombre: 'Dieta Lacto-Vegetariana',
        descripcion: 'La dieta lacto-vegetariana es una variante de la dieta vegetariana que excluye el consumo de carne, pescado y huevos, pero permite la incorporación de leche y productos lácteos. Se basa principalmente en alimentos de origen vegetal complementados con derivados lácteos como fuente adicional de proteínas, calcio y otros nutrientes.',
        permitidos: [
            'Frutas y verduras',
            'Cereales y granos',
            'Legumbres',
            'Frutos secos y semillas',
            'Aceites vegetales',
            'Tofu, tempeh y otros derivados de soja',
            'Leche',
            'Yogur',
            'Quesos y otros productos lácteos',
            'Hierbas, especias y condimentos'
        ],
        restringidos: [
            'Carne vacuna',
            'Carne porcina',
            'Carne aviar',
            'Pescados y mariscos',
            'Huevos',
            'Embutidos y fiambres de origen animal',
            'Caldos y productos elaborados con carne o pescado',
            'Productos que contengan huevo como ingrediente principal',
            'Otros productos animales no compatibles con esta variante alimentaria'
        ],
        beneficios: [
            'Favorece un mayor consumo de alimentos vegetales y fibra',
            'Puede aportar calcio y proteínas mediante los productos lácteos',
            'Puede contribuir al cuidado de la salud cardiovascular',
            'Puede reducir el consumo de grasas provenientes de carnes',
            'Puede adaptarse a preferencias éticas, culturales o religiosas'
        ],
        consideraciones: [
            'Requiere planificación para asegurar una adecuada ingesta de hierro, vitamina B12, proteínas y otros nutrientes',
            'La calidad nutricional depende de la variedad y equilibrio de los alimentos elegidos',
            'Algunas personas pueden presentar intolerancia o sensibilidad a los lácteos',
            'No todos los productos lacto-vegetarianos industrializados son saludables',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'Puede ser recomendable contar con orientación profesional para mantener una alimentación completa y equilibrada'
        ]
    },

    'ovo-vegetariana': {
        nombre: 'Dieta Ovo-Vegetariana',
        descripcion: 'La dieta ovo-vegetariana es una variante de la dieta vegetariana que excluye el consumo de carne, pescado y productos lácteos, pero permite el consumo de huevos. Se basa principalmente en alimentos de origen vegetal, utilizando los huevos como una fuente importante de proteínas, vitaminas y minerales.',
        permitidos: [
            'Frutas y verduras',
            'Cereales y granos',
            'Legumbres',
            'Frutos secos y semillas',
            'Aceites vegetales',
            'Tofu, tempeh y otros derivados de soja',
            'Huevos',
            'Productos elaborados a base de huevo compatibles con la dieta',
            'Hierbas, especias y condimentos'
        ],
        restringidos: [
            'Carne vacuna',
            'Carne porcina',
            'Carne aviar',
            'Pescados y mariscos',
            'Leche',
            'Yogur',
            'Quesos y otros productos lácteos',
            'Embutidos y fiambres de origen animal',
            'Caldos y productos elaborados con carne o pescado',
            'Otros productos animales no compatibles con esta variante alimentaria'
        ],
        beneficios: [
            'Favorece un mayor consumo de alimentos vegetales y fibra',
            'Los huevos aportan proteínas de alta calidad y nutrientes esenciales',
            'Puede contribuir al cuidado de la salud cardiovascular cuando está bien planificada',
            'Puede reducir el consumo de grasas provenientes de carnes',
            'Puede adaptarse a preferencias éticas, culturales o personales'
        ],
        consideraciones: [
            'Requiere planificación para asegurar una adecuada ingesta de calcio, hierro, vitamina B12 y otros nutrientes',
            'La calidad nutricional depende de la variedad y equilibrio de los alimentos seleccionados',
            'Es importante considerar fuentes alternativas de calcio ante la exclusión de lácteos',
            'No todos los productos ovo-vegetarianos industrializados son necesariamente saludables',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'Puede ser útil contar con orientación profesional para mantener una alimentación equilibrada'
        ]
    },

    'vegana': {
        nombre: 'Dieta Vegana',
        descripcion: 'La dieta vegana es un patrón alimentario basado exclusivamente en alimentos de origen vegetal, que excluye todos los productos y subproductos de origen animal. Además de una elección alimentaria, muchas personas la adoptan por motivos éticos, ambientales, culturales o de salud.',
        permitidos: [
            'Frutas y verduras',
            'Cereales y granos',
            'Legumbres',
            'Frutos secos y semillas',
            'Aceites vegetales',
            'Tofu, tempeh y otros derivados de soja',
            'Bebidas vegetales',
            'Sustitutos vegetales de carne, leche y queso',
            'Hierbas, especias y condimentos',
            'Alimentos fortificados de origen vegetal'
        ],
        restringidos: [
            'Carne vacuna',
            'Carne porcina',
            'Carne aviar',
            'Pescados y mariscos',
            'Huevos',
            'Leche y productos lácteos',
            'Miel',
            'Gelatina de origen animal',
            'Embutidos y productos elaborados con ingredientes animales',
            'Otros subproductos de origen animal'
        ],
        beneficios: [
            'Favorece un alto consumo de frutas, verduras, fibra y alimentos vegetales',
            'Puede contribuir al cuidado de la salud cardiovascular',
            'Puede reducir el consumo de grasas saturadas provenientes de productos animales',
            'Puede alinearse con objetivos éticos y ambientales',
            'Puede promover una mayor atención a la calidad y composición de los alimentos'
        ],
        consideraciones: [
            'Requiere planificación para asegurar una adecuada ingesta de proteínas, hierro, calcio, omega-3, vitamina B12 y otros nutrientes clave',
            'La suplementación de vitamina B12 suele ser necesaria en muchos casos',
            'Es importante incluir alimentos variados y equilibrados',
            'No todos los productos veganos industrializados son necesariamente saludables',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'Puede ser recomendable contar con orientación profesional para mantener una alimentación nutricionalmente completa'
        ]
    },

    'pescetariana': {
        nombre: 'Dieta Pescetariana',
        descripcion: 'La dieta pescetariana es un patrón alimentario basado principalmente en alimentos de origen vegetal que excluye el consumo de carne vacuna, porcina y aviar, pero permite incluir pescados y mariscos. Dependiendo de la variante seguida, también puede incorporar huevos y productos lácteos.',
        permitidos: [
            'Frutas y verduras',
            'Cereales y granos',
            'Legumbres',
            'Frutos secos y semillas',
            'Pescados',
            'Mariscos',
            'Aceites vegetales',
            'Tofu, tempeh y otros derivados de soja',
            'Huevos y productos lácteos (según la variante)',
            'Hierbas, especias y condimentos'
        ],
        restringidos: [
            'Carne vacuna',
            'Carne porcina',
            'Carne aviar',
            'Embutidos y fiambres elaborados con carnes terrestres',
            'Caldos y productos elaborados con carne vacuna, porcina o aviar',
            'Productos derivados de carnes terrestres',
            'Otros alimentos excluidos según la variante individual de la dieta'
        ],
        beneficios: [
            'Favorece un alto consumo de alimentos vegetales y fibra',
            'Los pescados y mariscos pueden aportar proteínas de alta calidad y ácidos grasos omega-3',
            'Puede contribuir al cuidado de la salud cardiovascular',
            'Puede reducir el consumo de carnes rojas y procesadas',
            'Puede ofrecer una alimentación variada y flexible'
        ],
        consideraciones: [
            'Es importante elegir pescados y mariscos de buena calidad y procedencia segura',
            'La planificación alimentaria influye en el equilibrio nutricional general',
            'Algunas especies de pescado pueden presentar mayor contenido de mercurio u otros contaminantes',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'No todos los productos pescetarianos industrializados son necesariamente saludables',
            'Puede ser útil contar con orientación profesional para mantener una alimentación equilibrada'
        ]
    },

    'paleo': {
        nombre: 'Dieta Paleo',
        descripcion: 'La dieta paleo (o paleolítica) es un patrón alimentario inspirado en los alimentos que se presume consumían las poblaciones humanas durante el Paleolítico. Se centra en alimentos mínimamente procesados y excluye productos agrícolas modernos, cereales, legumbres y alimentos industrializados.',
        permitidos: [
            'Carnes magras',
            'Pescados y mariscos',
            'Huevos',
            'Frutas',
            'Verduras',
            'Frutos secos y semillas',
            'Aceites naturales (oliva, coco, palta)',
            'Tubérculos y raíces',
            'Hierbas, especias y alimentos mínimamente procesados'
        ],
        restringidos: [
            'Cereales y granos',
            'Legumbres',
            'Productos lácteos',
            'Azúcar refinada',
            'Alimentos ultraprocesados',
            'Panes, pastas y productos de panadería',
            'Aceites altamente refinados',
            'Bebidas azucaradas',
            'Productos con aditivos artificiales'
        ],
        beneficios: [
            'Favorece el consumo de alimentos frescos y mínimamente procesados',
            'Puede aumentar la ingesta de frutas, verduras y proteínas',
            'Puede reducir el consumo de azúcares refinados y ultraprocesados',
            'Puede contribuir a la sensación de saciedad en algunas personas',
            'Promueve una mayor atención a la calidad de los alimentos'
        ],
        consideraciones: [
            'Excluye grupos alimentarios importantes como cereales, legumbres y lácteos',
            'Requiere planificación para asegurar un adecuado aporte de nutrientes',
            'Puede resultar restrictiva o difícil de mantener a largo plazo',
            'La evidencia científica sobre algunos de sus fundamentos evolutivos es objeto de debate',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'Puede ser recomendable contar con orientación profesional para mantener una alimentación equilibrada'
        ]
    },

    'primal': {
        nombre: 'Dieta Primal',
        descripcion: 'La dieta primal es un patrón alimentario inspirado en la alimentación ancestral, similar a la dieta paleo, pero generalmente más flexible en ciertos aspectos. Se enfoca en alimentos enteros, mínimamente procesados y naturales, permitiendo en muchas versiones algunos productos lácteos de alta calidad y grasas saludables.',
        permitidos: [
            'Carnes y aves',
            'Pescados y mariscos',
            'Huevos',
            'Frutas y verduras',
            'Frutos secos y semillas',
            'Tubérculos y raíces',
            'Aceites naturales (oliva, coco, palta)',
            'Manteca y grasas animales naturales',
            'Algunos productos lácteos enteros y fermentados',
            'Alimentos mínimamente procesados'
        ],
        restringidos: [
            'Azúcar refinada',
            'Cereales y granos refinados',
            'Alimentos ultraprocesados',
            'Aceites vegetales altamente refinados',
            'Bebidas azucaradas',
            'Productos con aditivos artificiales',
            'Snacks industrializados',
            'Harinas refinadas y productos derivados'
        ],
        beneficios: [
            'Favorece el consumo de alimentos naturales y mínimamente procesados',
            'Puede reducir la ingesta de azúcares refinados y ultraprocesados',
            'Puede aumentar el consumo de proteínas, grasas saludables y vegetales',
            'Puede contribuir a una mayor sensación de saciedad',
            'Ofrece mayor flexibilidad que otras dietas ancestrales más restrictivas'
        ],
        consideraciones: [
            'Puede restringir o limitar ciertos grupos alimentarios dependiendo de la versión aplicada',
            'Requiere planificación para asegurar una alimentación equilibrada',
            'La calidad nutricional depende de la variedad y calidad de los alimentos elegidos',
            'Algunos fundamentos teóricos relacionados con la alimentación ancestral son objeto de debate científico',
            'Las necesidades nutricionales pueden variar según la edad, actividad física y estado de salud',
            'Puede ser útil contar con orientación profesional antes de realizar cambios importantes en la alimentación'
        ]
    },

    'whole30': {
        nombre: 'Dieta Whole30',
        descripcion: 'La dieta Whole30 es un programa alimentario de 30 días enfocado en eliminar temporalmente ciertos grupos de alimentos considerados potencialmente problemáticos para evaluar cómo afectan al organismo. Promueve el consumo de alimentos enteros y mínimamente procesados, con una reintroducción gradual posterior de los alimentos restringidos.',
        permitidos: [
            'Carnes y aves',
            'Pescados y mariscos',
            'Huevos',
            'Frutas',
            'Verduras',
            'Frutos secos y semillas',
            'Aceites naturales (oliva, coco, palta)',
            'Hierbas, especias y condimentos compatibles',
            'Alimentos enteros y mínimamente procesados'
        ],
        restringidos: [
            'Azúcar añadida y edulcorantes',
            'Alcohol',
            'Cereales y granos',
            'Legumbres',
            'Productos lácteos',
            'Alimentos ultraprocesados',
            'Panes, pastas y productos de panadería',
            'Snacks y postres recreados con ingredientes "permitidos"',
            'Algunos aditivos o ingredientes incompatibles según las reglas del programa'
        ],
        beneficios: [
            'Favorece el consumo de alimentos enteros y mínimamente procesados',
            'Puede aumentar la conciencia sobre hábitos alimentarios y composición de los alimentos',
            'Puede reducir el consumo de azúcares añadidos y ultraprocesados',
            'Algunas personas lo utilizan para identificar posibles sensibilidades alimentarias',
            'Promueve la planificación y preparación consciente de comidas'
        ],
        consideraciones: [
            'Es un programa temporal y relativamente restrictivo',
            'Requiere una planificación cuidadosa para mantener equilibrio nutricional',
            'Puede resultar difícil de sostener en contextos sociales o rutinas exigentes',
            'La respuesta individual a la eliminación y reintroducción de alimentos puede variar',
            'No todos los beneficios atribuidos al programa cuentan con respaldo científico concluyente',
            'Puede ser recomendable contar con orientación profesional antes de iniciar cambios alimentarios restrictivos'
        ]
    }
};

/* ============================================================
   RENDERIZADO
   ============================================================ */

/**
 * Genera el HTML para una card de categoría.
 * @param {string} titulo  - Título de la sección
 * @param {string} mod     - Modificador CSS de la card
 * @param {string[]} items - Lista de ítems
 */
function buildCard(titulo, mod, items) {
    const liItems = items
        .map(item => `<li>${item}</li>`)
        .join('\n                    ');
    return `
        <article class="dieta-card dieta-card--${mod}">
            <h3><span class="card-icon" aria-hidden="true"></span> ${titulo}</h3>
            <ul>
                ${liItems}
            </ul>
        </article>`;
}

/**
 * Renderiza el contenido de una dieta en el panel principal.
 * @param {string} key - Clave de la dieta (ej. 'cetogenica')
 */
function renderDiet(key) {
    const data = DIETAS[key];
    if (!data) return;

    const contentEl = document.getElementById('dieta-content');
    if (!contentEl) return;

    const html = `
        <header class="dieta-header">
            <h2>${data.nombre}</h2>
            <p class="descripcion">${data.descripcion}</p>
        </header>
        <div class="dieta-cards">
            ${buildCard('Alimentos Permitidos',   'permitidos',      data.permitidos)}
            ${buildCard('Alimentos Restringidos', 'restringidos',    data.restringidos)}
            ${buildCard('Beneficios',             'beneficios',      data.beneficios)}
            ${buildCard('Consideraciones',        'consideraciones', data.consideraciones)}
        </div>`;

    /* Animación de entrada */
    contentEl.classList.remove('animating');
    void contentEl.offsetWidth; /* forzar reflow */
    contentEl.innerHTML = html;
    contentEl.classList.add('animating');
}

/* ============================================================
   NAVEGACIÓN DEL SIDEBAR
   ============================================================ */
function initDietNav() {
    const links     = document.querySelectorAll('.diet-link');
    const checkbox  = document.getElementById('menu-toggle');

    if (!links.length) return;

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            const key = this.dataset.diet;
            if (!key || !DIETAS[key]) return;

            /* 1. Actualizar active link */
            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            /* 2. Renderizar contenido */
            renderDiet(key);

            /* 3. En mobile: cerrar el menú automáticamente */
            if (checkbox && window.innerWidth < 900) {
                checkbox.checked = false;
            }

            /* 4. Scroll suave al contenido en mobile */
            if (window.innerWidth < 900) {
                const contentEl = document.getElementById('dieta-content');
                if (contentEl) {
                    contentEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });

    /* Cargar la primera dieta por defecto (Libre de Gluten) */
    const defaultKey = 'libre-gluten';
    const defaultLink = document.querySelector(`.diet-link[data-diet="${defaultKey}"]`);
    if (defaultLink) {
        links.forEach(l => l.classList.remove('active'));
        defaultLink.classList.add('active');
    }
    renderDiet(defaultKey);
}

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', initDietNav);
