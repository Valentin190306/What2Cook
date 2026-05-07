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

echo "\n2. Probando SpoonacularService...\n";
try {
    // Apagamos la traducción forzadamente para probar Spoonacular crudo
    $_ENV['ENABLE_TRANSLATION'] = 'false';
    $spoonacular = new \App\Services\SpoonacularService();

    // Buscamos algo rápido por ingredientes
    $recipes = $spoonacular->searchByIngredients(['apple'], 1);
    assertTest("La API de Spoonacular responde correctamente (búsqueda de ingredientes)", is_array($recipes) && count($recipes) > 0);

} catch (\Exception $e) {
    echo "⚠️ Error probando Spoonacular: " . $e->getMessage() . "\n";
    echo "Asegúrate de que tienes SPOONACULAR_KEY configurada en tu .env.\n";
}

echo "\n=== TESTS FINALIZADOS ===\n";
