<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['SPOONACULAR_KEY'];

$url = "https://api.spoonacular.com/recipes/complexSearch?apiKey={$apiKey}&type=breakfast&minCalories=300&maxCalories=800&minProtein=10&maxProtein=50&number=1&addRecipeNutrition=true";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
