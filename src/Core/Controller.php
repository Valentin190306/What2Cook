<?php

namespace App\Core;

use App\Core\Log\LoggerInterface;
use App\Core\Session;

/**
 * Class Controller
 * 
 * Controlador Padre para la aplicación.
 */
abstract class Controller
{
    protected ?LoggerInterface $logger = null;

    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger === null) return;
        $module = (new \ReflectionClass($this))->getShortName();
        $this->logger->log($level, "[{$module}] {$message}", $context);
    }

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

    /**
     * Requiere autenticación para endpoints de la API (JSON).
     */
    protected function requireAuthApi(): int
    {
        $id = Session::userId();
        if ($id === null) {
            $this->json(['error' => 'No autenticado.'], 401);
        }
        return $id;
    }

    /**
     * Requiere autenticación para páginas web (Redirección).
     */
    protected function requireAuthWeb(): int
    {
        $id = Session::userId();
        if ($id === null) {
            Session::flash('error', 'Necesitás iniciar sesión.');
            header('Location: /login');
            exit;
        }
        return $id;
    }

    /**
     * Redirige a otra URL y finaliza la ejecución.
     */
    protected function redirect(string $to): void
    {
        header('Location: ' . $to);
        exit;
    }
}
