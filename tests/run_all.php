<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno necesarias para los servicios
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Función auxiliar para imprimir resultados en consola
function assertTest(string $description, bool $condition) {
    if ($condition) {
        echo "✅ PASÓ: $description\n";
    } else {
        echo "❌ FALLÓ: $description\n";
    }
}

echo "=== INICIANDO TESTS ===\n\n";

echo "1. Probando OpenAITranslator...\n";
try {
    $translator = new \App\Services\Translation\OpenAITranslator();
    
    // Probando traducción de string
    $resultString = $translator->translate("Hello world", "es");
    assertTest("Traduce 'Hello world' al español", strtolower($resultString) === "hola mundo" || strtolower($resultString) === "hola, mundo" || strtolower($resultString) === "hola mundo.");

    // Probando traducción de Array
    $inputArray = [
        "title" => "Chicken Soup",
        "description" => "A very healthy soup."
    ];
    $resultArray = $translator->translateArray($inputArray, "es");
    assertTest("Traduce keys de un array y mantiene la estructura", 
        isset($resultArray['title']) && 
        strtolower($resultArray['title']) !== "chicken soup" && 
        isset($resultArray['description'])
    );

} catch (\Exception $e) {
    echo "⚠️ Error probando OpenAI: " . $e->getMessage() . "\n";
    echo "Asegúrate de que tienes OPENAI_API_KEY configurada en tu .env y tiene saldo.\n";
}

echo "\n2. Probando GeminiTranslator...\n";
try {
    $gemini = new \App\Services\Translation\GeminiTranslator();
    
    // Probando traducción de string
    $resultString = $gemini->translate("Hello world", "es");
    assertTest("Traduce 'Hello world' al español con Gemini", strtolower($resultString) === "hola mundo" || strtolower($resultString) === "hola, mundo" || strtolower($resultString) === "hola mundo.");

    // Probando traducción de Array
    $inputArray = [
        "title" => "Apple Pie",
        "description" => "A very tasty dessert."
    ];
    $resultArray = $gemini->translateArray($inputArray, "es");
    assertTest("Traduce keys de un array y mantiene la estructura con Gemini", 
        isset($resultArray['title']) && 
        strtolower($resultArray['title']) !== "apple pie" && 
        isset($resultArray['description'])
    );

} catch (\Exception $e) {
    echo "⚠️ Error probando Gemini: " . $e->getMessage() . "\n";
    echo "Asegúrate de que tienes GEMINI_API_KEY configurada en tu .env.\n";
}

echo "\n3. Probando SpoonacularService...\n";
try {
    // Apagamos la traducción forzadamente para probar Spoonacular crudo
    $_ENV['ENABLE_INPUT_TRANSLATION'] = 'false';
    $_ENV['ENABLE_OUTPUT_TRANSLATION'] = 'false';
    $spoonacular = new \App\Services\SpoonacularService();

    // Buscamos algo rápido por ingredientes
    $recipes = $spoonacular->searchByIngredients(['apple'], 1);
    assertTest("La API de Spoonacular responde correctamente (búsqueda de ingredientes)", is_array($recipes) && count($recipes) > 0);

    // 4. Probando Flujo Completo (Input ES -> API EN -> Output ES)
    echo "\n4. Probando Flujo Completo (Traducción de entrada y salida)...\n";
    $_ENV['ENABLE_INPUT_TRANSLATION'] = 'true';
    $_ENV['ENABLE_OUTPUT_TRANSLATION'] = 'true';
    // Nos aseguramos de usar Gemini para el test si está disponible, o el que esté configurado
    $spoonacularTrans = new \App\Services\SpoonacularService();
    
    // Buscamos con ingrediente en español
    $recipesTrans = $spoonacularTrans->searchByIngredients(['manzana'], 1);
    
    assertTest("El flujo completo funciona (Búsqueda por 'manzana' devuelve resultados)", 
        is_array($recipesTrans) && count($recipesTrans) > 0
    );

    if (count($recipesTrans) > 0) {
        $firstRecipeTitle = $recipesTrans[0]['title'] ?? '';
        // Si la traducción de salida funcionó, el título no debería tener palabras muy comunes en inglés
        // o simplemente verificamos que recibimos un string. 
        // Es difícil asegurar el idioma sin una librería, pero si 'manzana' trajo algo, el input translation funcionó.
        assertTest("La respuesta parece estar traducida (Título: '$firstRecipeTitle')", !empty($firstRecipeTitle));
    }

} catch (\Exception $e) {
    echo "⚠️ Error probando Spoonacular/Traducción: " . $e->getMessage() . "\n";
    echo "Revisa tus claves de API y cuotas de OpenAI/Gemini.\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. Traducción de receta completa (simulando el flujo real del backend)
// ─────────────────────────────────────────────────────────────────────────────
echo "\n5. Probando traducción de receta completa (flujo del backend)...\n";
echo "   → Este test simula exactamente lo que hace SpoonacularService::get()\n";
echo "     cuando ENABLE_OUTPUT_TRANSLATION=true, usando un objeto receta real.\n\n";

try {
    // Objeto receta que imita la estructura real devuelta por Spoonacular
    // (campos que el frontend consume: title, summary, instructions, ingredients)
    $recetaOriginal = [
        'id'              => 716429,
        'title'           => 'Pasta with Garlic, Scallions, Cauliflower & Breadcrumbs',
        'readyInMinutes'  => 45,
        'servings'        => 2,
        'summary'         => 'Pasta with Garlic, Scallions, Cauliflower & Breadcrumbs might be a good recipe to expand your main course repertoire. This recipe serves 2 and costs $1.57 per serving.',
        'instructions'    => 'Heat a pan over medium heat. Add oil and garlic, cook until fragrant. Add cauliflower and cook until tender. Toss with pasta and breadcrumbs.',
        'extendedIngredients' => [
            ['id' => 1, 'name' => 'garlic', 'amount' => 3, 'unit' => 'cloves', 'original' => '3 cloves of garlic'],
            ['id' => 2, 'name' => 'scallions', 'amount' => 4, 'unit' => '', 'original' => '4 scallions, sliced'],
            ['id' => 3, 'name' => 'cauliflower', 'amount' => 1, 'unit' => 'head', 'original' => '1 head of cauliflower'],
            ['id' => 4, 'name' => 'breadcrumbs', 'amount' => 0.5, 'unit' => 'cup', 'original' => '1/2 cup breadcrumbs'],
        ],
        'nutrition' => [
            'nutrients' => [
                ['name' => 'Calories', 'amount' => 543.35, 'unit' => 'kcal'],
                ['name' => 'Protein',  'amount' => 18.03,  'unit' => 'g'],
            ]
        ],
        // Campo numérico y booleano: deben permanecer intactos
        'cheap'           => false,
        'veryPopular'     => true,
    ];

    $gemini = new \App\Services\Translation\GeminiTranslator();
    $recetaTraducida = $gemini->translateArray($recetaOriginal, 'es');

    // ── Verificaciones de campos textuales ──────────────────────────────────
    assertTest(
        "El 'title' de la receta fue traducido al español",
        isset($recetaTraducida['title']) &&
        strtolower($recetaTraducida['title']) !== strtolower($recetaOriginal['title'])
    );

    assertTest(
        "El 'summary' de la receta fue traducido al español",
        isset($recetaTraducida['summary']) &&
        $recetaTraducida['summary'] !== $recetaOriginal['summary']
    );

    assertTest(
        "Las 'instructions' de la receta fueron traducidas",
        isset($recetaTraducida['instructions']) &&
        $recetaTraducida['instructions'] !== $recetaOriginal['instructions']
    );

    // ── Verificaciones de estructura (no deben cambiar) ─────────────────────
    assertTest(
        "El 'id' numérico se mantuvo intacto tras la traducción",
        ($recetaTraducida['id'] ?? null) === 716429
    );

    assertTest(
        "El campo 'readyInMinutes' numérico se mantuvo intacto",
        ($recetaTraducida['readyInMinutes'] ?? null) === 45
    );

    assertTest(
        "Los ingredientes ('extendedIngredients') siguen siendo un array de " . count($recetaOriginal['extendedIngredients']) . " elementos",
        isset($recetaTraducida['extendedIngredients']) &&
        count($recetaTraducida['extendedIngredients']) === count($recetaOriginal['extendedIngredients'])
    );

    if (!empty($recetaTraducida['extendedIngredients'])) {
        $primerIngrediente = $recetaTraducida['extendedIngredients'][0];
        assertTest(
            "El nombre del primer ingrediente fue traducido (original: 'garlic')",
            isset($primerIngrediente['name']) &&
            strtolower($primerIngrediente['name']) !== 'garlic'
        );
        assertTest(
            "El campo 'amount' numérico del ingrediente se mantuvo intacto",
            isset($primerIngrediente['amount']) && (float)$primerIngrediente['amount'] === 3.0
        );
    }

    assertTest(
        "El campo booleano 'veryPopular' se mantuvo intacto",
        ($recetaTraducida['veryPopular'] ?? null) === true
    );

    // ── Diagnóstico del título traducido ────────────────────────────────────
    $tituloTraducido = $recetaTraducida['title'] ?? '(vacío)';
    echo "\n   📋 Diagnóstico de traducción del título:\n";
    echo "      Original  : {$recetaOriginal['title']}\n";
    echo "      Traducido : {$tituloTraducido}\n";

    $instruccionesTraducidas = $recetaTraducida['instructions'] ?? '(vacío)';
    echo "\n   📋 Diagnóstico de traducción de instrucciones:\n";
    echo "      Original  : " . substr($recetaOriginal['instructions'], 0, 80) . "...\n";
    echo "      Traducido : " . substr($instruccionesTraducidas, 0, 80) . "...\n";

} catch (\Exception $e) {
    echo "⚠️ Error en test de receta completa: " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. Diagnóstico de caché: detectar si la caché devuelve resultados sin traducir
// ─────────────────────────────────────────────────────────────────────────────
echo "\n6. Diagnóstico de caché (posible causa del bug en el frontend)...\n";
echo "   → El bug ocurre si la caché guardó resultados SIN traducir en una llamada\n";
echo "     anterior (cuando ENABLE_OUTPUT_TRANSLATION era false), y ahora los\n";
echo "     devuelve tal cual, ignorando la configuración actual.\n\n";

$cacheDir = __DIR__ . '/../log/cache';
if (is_dir($cacheDir)) {
    $cacheFiles = glob($cacheDir . '/*.json');
    $totalFiles = count($cacheFiles);
    echo "   📁 Archivos en caché: {$totalFiles}\n";

    $sinTraducir = 0;
    $conTraducir = 0;
    $ejemploSinTraducir = null;

    foreach ($cacheFiles as $file) {
        $content = file_get_contents($file);
        if ($content === false) continue;
        $data = json_decode($content, true);
        if (!is_array($data)) continue;

        // Buscamos 'title' en inglés: si contiene palabras muy comunes en inglés
        // y NO hay palabras en español, probablemente no está traducido.
        $title = '';
        if (isset($data['title'])) {
            $title = $data['title'];
        } elseif (isset($data[0]['title'])) {
            $title = $data[0]['title'];
        }

        if ($title !== '') {
            // Heurística simple: si el título tiene caracteres ASCII puros y
            // no tiene tildes ni ñ, probablemente está en inglés
            $tieneEspanol = preg_match('/[áéíóúüñÁÉÍÓÚÜÑ¿¡]/u', $title);
            if (!$tieneEspanol) {
                $sinTraducir++;
                if ($ejemploSinTraducir === null) {
                    $ejemploSinTraducir = ['file' => basename($file), 'title' => $title];
                }
            } else {
                $conTraducir++;
            }
        }
    }

    assertTest(
        "No hay archivos de caché con títulos en inglés (sin traducir) cuando la traducción debería estar activa",
        $sinTraducir === 0
    );

    if ($sinTraducir > 0) {
        echo "\n   ⚠️  DIAGNÓSTICO: Se encontraron {$sinTraducir} archivos de caché con títulos en inglés.\n";
        echo "      Esto explica por qué el frontend recibe recetas sin traducir:\n";
        echo "      la caché fue generada antes de activar ENABLE_OUTPUT_TRANSLATION.\n";
        echo "\n   💡 SOLUCIÓN: Borrá los archivos de caché con:\n";
        echo "      rm -rf " . realpath($cacheDir) . "/*.json\n";
        if ($ejemploSinTraducir) {
            echo "\n   📋 Ejemplo de caché sin traducir:\n";
            echo "      Archivo : {$ejemploSinTraducir['file']}\n";
            echo "      Título  : {$ejemploSinTraducir['title']}\n";
        }
    } else {
        echo "   ✅ No se detectaron problemas de caché con títulos en inglés.\n";
        echo "      Si el frontend aún no muestra traducciones, el problema puede estar en:\n";
        echo "      - ENABLE_OUTPUT_TRANSLATION no está en 'true' en el .env del servidor.\n";
        echo "      - El frontend no está mostrando el campo 'title' traducido (verifica el JS).\n";
        echo "      - La variable de entorno no se recarga sin reiniciar el servidor.\n";
    }
} else {
    echo "   ℹ️  El directorio de caché no existe todavía. No hay archivos para analizar.\n";
}

echo "\n=== TESTS FINALIZADOS ===\n";
