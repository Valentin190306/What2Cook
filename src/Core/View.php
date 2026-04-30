<?php

namespace App\Core;

/**
 * Class View
 * 
 * Gestiona la carga y renderizado de las vistas.
 */
class View
{
    /**
     * Renderiza un archivo de vista.
     * 
     * @param string $view Nombre del archivo de vista (sin .php)
     * @param array $data Datos que se pondrán a disposición de la vista
     */
    public static function render(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = __DIR__ . "/../Views/{$view}.php";
        
        if (file_exists($viewFile)) {
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();
            
            require_once __DIR__ . "/../Views/Components/Layout.php";
        } else {
            die("View $view not found.");
        }
    }
}
