<?php

echo "🔧 Agregando namespaces a TODOS los archivos de Pages...\n\n";

$resourcesDir = __DIR__ . '/app/Filament/App/Resources';
$fixed = 0;
$errors = 0;

// Función para agregar namespace a un archivo
function addNamespace($file) {
    $content = file_get_contents($file);
    
    // Si ya tiene namespace, saltar
    if (preg_match('/^\s*namespace\s+/m', $content)) {
        return false;
    }
    
    // Extraer el nombre del recurso desde la ruta
    $path = str_replace('\\', '/', $file);
    
    if (preg_match('/\/(\w+Resource)\/Pages\/(\w+)\.php$/', $path, $matches)) {
        $resourceName = $matches[1];
        $className = $matches[2];
        
        $namespace = "namespace App\\Filament\\App\\Resources\\{$resourceName}\\Pages;";
        
        // Agregar namespace después de <?php
        $newContent = preg_replace(
            '/^<\?php\s*\n/',
            "<?php\n\n{$namespace}\n\n",
            $content
        );
        
        if ($newContent !== $content) {
            file_put_contents($file, $newContent);
            return true;
        }
    }
    
    return false;
}

// Buscar recursivamente todos los archivos PHP en subdirectorios Pages
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($resourcesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        // Solo procesar archivos dentro de /Pages/
        if (strpos($path, DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR) !== false) {
            try {
                if (addNamespace($path)) {
                    $relativePath = str_replace($resourcesDir . DIRECTORY_SEPARATOR, '', $path);
                    echo "✓ " . $relativePath . "\n";
                    $fixed++;
                }
            } catch (Exception $e) {
                echo "✗ Error en " . basename($path) . ": " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ Archivos corregidos: $fixed\n";
if ($errors > 0) {
    echo "❌ Errores: $errors\n";
}
echo "\nAhora ejecuta:\n";
echo "  composer dump-autoload\n";
echo "  php artisan optimize:clear\n";
echo "  php artisan filament:clear-cached-components\n";