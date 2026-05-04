<?php

namespace App\Core;

/**
 * Class Controller
 * 
 * Controlador Padre para la aplicación.
 */
abstract class Controller
{
    /**
     * Verifica que la petición tenga Content-Type: application/json.
     * Responde 415 y termina la ejecución si no es así.
     */
    protected function requireJson(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($contentType, 'application/json')) {
            $this->json(['error' => 'Content-Type debe ser application/json.'], 415);
            exit;
        }
    }

    /**
     * Lee y decodifica el cuerpo JSON de la petición.
     * Devuelve un array vacío si el body está vacío o no es JSON válido.
     */
    protected function parseBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    /**
     * Envía una respuesta JSON y termina la ejecución.
     *
     * @param array $data   Datos a serializar.
     * @param int   $status Código de estado HTTP (por defecto 200).
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
