<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

function assertTest(string $description, bool $condition) {
    if ($condition) {
        echo "\033[32m✓ PASÓ\033[0m: $description\n";
    } else {
        echo "\033[31m✗ FALLÓ\033[0m: $description\n";
    }
}

function section(string $title): void {
    echo "\n━━━ $title ━━━\n\n";
}

// ─────────────────────────────────────────────────────────────────────────────
section("1. LibreTranslateTranslator");

try {
    $translator = new \App\Services\Translation\LibreTranslateTranslator();

    $result = $translator->translate("Hello world", "es");
    assertTest("translate(): 'Hello world' → español", str_contains(strtolower($result), "hola"));

    $resultEn = $translator->translate("Hola mundo", "en");
    assertTest("translate(): 'Hola mundo' → inglés", str_contains(strtolower($resultEn), "hello"));

    $inputArray = [
        "title" => "Chicken Soup",
        "description" => "A very healthy soup."
    ];
    $resultArray = $translator->translateArray($inputArray, "es");
    assertTest("translateArray(): preserva estructura del array",
        isset($resultArray['title'], $resultArray['description'])
    );
    assertTest("translateArray(): traduce cadenas cuando es posible",
        str_contains(strtolower($resultArray['description'] ?? ''), 'sopa')
    );

    $numeric = ["id" => 42, "active" => true];
    $resultNumeric = $translator->translateArray($numeric, "es");
    assertTest("translateArray(): preserva tipos numéricos y booleanos",
        $resultNumeric['id'] === 42 && $resultNumeric['active'] === true
    );

    $withEmptyStrings = ["name" => "garlic", "unit" => ""];
    $resultEmpty = $translator->translateArray($withEmptyStrings, "es");
    assertTest("translateArray(): maneja strings vacíos sin error",
        isset($resultEmpty['name'], $resultEmpty['unit']) && $resultEmpty['unit'] === ''
    );

} catch (\Exception $e) {
    echo "⚠️ LibreTranslate no disponible: " . $e->getMessage() . "\n";
    echo "   Asegurate de tener el contenedor corriendo:\n";
    echo "   docker compose up -d libretranslate\n";
}

// ─────────────────────────────────────────────────────────────────────────────
section("2. Traducción de receta completa (LibreTranslate)");

try {
    $recetaOriginal = [
        'id'              => 716429,
        'title'           => 'Pasta with Garlic, Scallions, Cauliflower & Breadcrumbs',
        'readyInMinutes'  => 45,
        'servings'        => 2,
        'summary'         => 'Pasta with Garlic, Scallions, Cauliflower & Breadcrumbs might be a good recipe to expand your main course repertoire.',
        'instructions'    => 'Heat a pan over medium heat. Add oil and garlic, cook until fragrant. Add cauliflower and cook until tender. Toss with pasta and breadcrumbs.',
        'extendedIngredients' => [
            ['id' => 1, 'name' => 'garlic', 'amount' => 3, 'unit' => 'cloves', 'original' => '3 cloves of garlic'],
            ['id' => 2, 'name' => 'scallions', 'amount' => 4, 'unit' => '', 'original' => '4 scallions, sliced'],
        ],
        'cheap'       => false,
        'veryPopular' => true,
    ];

    $translator = new \App\Services\Translation\LibreTranslateTranslator();
    $recetaTraducida = $translator->translateArray($recetaOriginal, 'es');

    assertTest("Estructura completa preservada (mismas keys)",
        array_keys($recetaTraducida) === array_keys($recetaOriginal)
    );

    assertTest("El 'title' existe en la respuesta",
        isset($recetaTraducida['title']) && is_string($recetaTraducida['title'])
    );

    assertTest("El 'summary' existe en la respuesta",
        isset($recetaTraducida['summary']) && is_string($recetaTraducida['summary'])
    );

    assertTest("Las 'instructions' existen en la respuesta",
        isset($recetaTraducida['instructions']) && is_string($recetaTraducida['instructions'])
    );

    assertTest("El 'id' numérico se mantuvo intacto",
        ($recetaTraducida['id'] ?? null) === 716429
    );

    assertTest("El campo 'cheap' booleano se mantuvo intacto",
        ($recetaTraducida['cheap'] ?? null) === false
    );

    assertTest("El campo 'veryPopular' booleano se mantuvo intacto",
        ($recetaTraducida['veryPopular'] ?? null) === true
    );

    assertTest("Los ingredientes siguen siendo un array con la misma cantidad",
        isset($recetaTraducida['extendedIngredients']) &&
        is_array($recetaTraducida['extendedIngredients']) &&
        count($recetaTraducida['extendedIngredients']) === count($recetaOriginal['extendedIngredients'])
    );

    $tituloTraducido = $recetaTraducida['title'] ?? '(vacío)';
    echo "\n   📋 Diagnóstico:\n";
    echo "      Original  : {$recetaOriginal['title']}\n";
    echo "      Traducido : {$tituloTraducido}\n";

} catch (\Exception $e) {
    echo "⚠️ Error en test de receta: " . $e->getMessage() . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
section("3. SpoonacularService (sin traducción)");

try {
    $_ENV['ENABLE_INPUT_TRANSLATION'] = 'false';
    $_ENV['ENABLE_OUTPUT_TRANSLATION'] = 'false';

    $spoonacular = new \App\Services\SpoonacularService();
    $recipes = $spoonacular->searchByIngredients(['apple'], 1);
    assertTest("searchByIngredients('apple') devuelve resultados",
        is_array($recipes) && count($recipes) > 0
    );

} catch (\Exception $e) {
    echo "⚠️ Spoonacular no disponible: " . $e->getMessage() . "\n";
    echo "   Revisá SPOONACULAR_KEY en .env y la cuota de la API.\n";
}

// ─────────────────────────────────────────────────────────────────────────────
section("4. Flujo completo (traducción input + output)");

try {
    $_ENV['ENABLE_INPUT_TRANSLATION'] = 'true';
    $_ENV['ENABLE_OUTPUT_TRANSLATION'] = 'true';

    $spoonacular = new \App\Services\SpoonacularService();
    $recipes = $spoonacular->searchByIngredients(['manzana'], 1);

    assertTest("Búsqueda por 'manzana' devuelve resultados",
        is_array($recipes) && count($recipes) > 0
    );

    if (count($recipes) > 0) {
        $title = $recipes[0]['title'] ?? '';
        assertTest("El título de la receta se recibió correctamente",
            !empty($title)
        );
        echo "   📋 Título: {$title}\n";
    }

} catch (\Exception $e) {
    echo "⚠️ Error en flujo completo: " . $e->getMessage() . "\n";
    echo "   Verificá que LibreTranslate esté corriendo y las flags en .env.\n";
}

// ─────────────────────────────────────────────────────────────────────────────
section("5. Traductores legacy (opcional)");

// Gemini (solo si hay API key)
if (!empty($_ENV['GEMINI_API_KEY'] ?? '')) {
    try {
        $gemini = new \App\Services\Translation\GeminiTranslator();
        $result = $gemini->translate("Hello world", "es");
        assertTest("[Gemini] Traduce 'Hello world' al español",
            str_contains(strtolower($result), "hola")
        );
    } catch (\Exception $e) {
        echo "⚠️ Gemini: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️ GEMINI_API_KEY no está configurada, saltando test de Gemini.\n";
}

// OpenAI (solo si hay API key)
if (!empty($_ENV['OPENAI_API_KEY'] ?? '')) {
    try {
        $openai = new \App\Services\Translation\OpenAITranslator();
        $result = $openai->translate("Hello world", "es");
        assertTest("[OpenAI] Traduce 'Hello world' al español",
            str_contains(strtolower($result), "hola")
        );
    } catch (\Exception $e) {
        echo "⚠️ OpenAI: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️ OPENAI_API_KEY no está configurada, saltando test de OpenAI.\n";
}

// ─────────────────────────────────────────────────────────────────────────────
section("6. Diagnóstico de caché");

$cacheDir = __DIR__ . '/../log/cache';
if (is_dir($cacheDir)) {
    $cacheFiles = glob($cacheDir . '/*.json');
    $translationFiles = glob($cacheDir . '/translations/*.json');
    $total = count($cacheFiles) + count($translationFiles);

    echo "   📁 Archivos en caché: {$total} (" . count($cacheFiles) . " API, " . count($translationFiles) . " traducciones)\n";

    if ($total === 0) {
        echo "   ✅ Caché vacío. Se llenará a medida que se usen las traducciones.\n";
    } else {
        $sinTraducir = 0;
        $conTraducir = 0;

        foreach ($cacheFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;
            $data = json_decode($content, true);
            if (!is_array($data)) continue;

            $title = $data['title'] ?? $data[0]['title'] ?? '';
            if ($title !== '') {
                $tieneEspanol = preg_match('/[áéíóúüñÁÉÍÓÚÜÑ¿¡]/u', $title) || preg_match('/\b(con|del|las|los|una|para|que|como)\b/i', $title);
                if ($tieneEspanol) $conTraducir++;
                else $sinTraducir++;
            }
        }

        if ($sinTraducir > 0) {
            echo "   ⚠️ {$sinTraducir} archivos sin traducir (datos en inglés).\n";
            echo "   💡 Borrá la caché y volvé a probar con traducción activada.\n";
        }
        if ($conTraducir > 0) {
            echo "   ✅ {$conTraducir} archivos con contenido traducido.\n";
        }
    }
} else {
    echo "   ℹ️ El directorio de caché no existe. Se creará automáticamente al usar el servicio.\n";
}

// ─────────────────────────────────────────────────────────────────────────────
echo "\n━━━ FINALIZADO ━━━\n";
