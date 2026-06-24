# What2Cook

Repositorio de trabajo de la cátedra de Programación en Ambiente Web.

**Estudiantes:**
- Contardi, Gustavo
- Zander, Matt
- Romero Monteagudo, Valentín Joel

---

## 1. Entorno de Desarrollo y Stack

La aplicación se construyó siguiendo un modelo MVC clásico pero simplificado, desarrollado "Vanilla" (sin frameworks pesados de frontend o backend) para maximizar el rendimiento y el control de la lógica de negocio.

| Capa | Tecnología |
| :--- | :--- |
| **Servidor web** | Nginx (Alpine) |
| **Backend** | PHP 8.3 FPM (Alpine) |
| **Base de datos** | PostgreSQL 16 (Alpine) |
| **Contenedores** | Docker + Docker Compose |
| **Gestión de dependencias** | Composer |
| **Frontend** | HTML5 semántico, CSS3 Vanilla, JavaScript (ES6+), Service Worker |

---

## 2. Estructura del Proyecto

El árbol de directorios real del proyecto está organizado de la siguiente manera:

```text
What2Cook/
├── bin/
│   └── preload-recipes.php           # Script CLI de fondo para precarga y traducción de recetas
├── db/
│   └── migrations/                   # Archivos de migración de base de datos (Phinx)
├── docker/
│   ├── nginx.conf                    # Archivo de configuración para el servidor Nginx
│   └── php.Dockerfile                # Receta Docker para PHP FPM con extensiones requeridas y Composer
├── log/                              # Logs del sistema y almacenamiento de caché local
│   ├── .search_index.txt             # Índice rotativo de búsqueda para el job de precarga
│   ├── .spoonacular_points.json      # Registro de consumo de puntos diarios de Spoonacular
│   ├── app.log                       # Log de errores y actividades críticas de la app
│   └── cache/                        # Caché de archivos (respuestas de API y traducciones persistidas)
├── public/                           # Directorio público y raíz web
│   ├── index.php                     # Front controller (punto de entrada principal)
│   ├── sw.js                         # Service Worker para almacenamiento en caché y soporte offline
│   ├── offline.html                  # Plantilla HTML que se sirve cuando no hay conexión
│   ├── .htaccess                     # Reglas de reescritura para Apache (entornos compatibles)
│   └── assets/                       # Recursos estáticos servidos al cliente
│       ├── html/                     # Plantillas HTML estáticas
│       ├── img/                      # Imágenes de la interfaz y placeholders
│       ├── js/                       # Lógica de Javascript Vanilla organizada por módulos
│       ├── styles/                   # Archivos CSS CSS3 organizados por páginas y componentes
│       └── text/                     # Recursos textuales locales
├── src/                              # Código fuente de la aplicación (Autoloaded bajo namespace App\)
│   ├── Controllers/                  # Controladores de la aplicación (extienden de App\Core\Controller)
│   ├── Core/                         # Componentes núcleo (Router, Database, Session, Validator, Log, etc.)
│   ├── Models/                       # Modelos del sistema para acceso a datos (User, Favorite, Plan, etc.)
│   ├── Services/                     # Servicios del negocio (SpoonacularService, DietHelperService)
│   │   ├── Traits/                   # Traits reutilizables (ej. NutritionNormalizer)
│   │   └── Translation/              # Módulos del motor de traducción (OpenAI, Gemini, LibreTranslate)
│   ├── Views/                        # Plantillas de renderizado de vistas PHP
│   │   ├── Components/
│   │   │   └── Layout.php            # Layout HTML principal unificado (Header, Footer, Head)
│   │   └── ...                       # Archivos de vistas individuales (.php)
│   ├── bootstrap.php                 # Inicialización de dependencias, variables de entorno y base de datos
│   └── routes.php                    # Registro y mapeo de URIs a Controladores/Métodos
├── composer.json                     # Archivo de dependencias del proyecto (monolog, phpdotenv, phinx, whoops)
├── docker-compose.yml                # Configuración de los servicios Docker
└── phinx.php                         # Configuración de conexión y rutas para migraciones Phinx
```

---

## 3. Requisitos Previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) o Docker Engine + Docker Compose (Linux)
- Git

---

## 4. Primeros Pasos y Configuración

### 1. Clonar el repositorio
```bash
git clone <url-del-repo>
cd What2Cook
```

### 2. Configurar variables de entorno
Crea una copia de la plantilla `.env.example` y renómbrala a `.env`:
```bash
cp .env.example .env
```

Abre el archivo `.env` y configura tus valores locales:
```env
# Conexión a Base de Datos
DB_HOST=database
DB_PORT=5432
DB_NAME=what2cook
DB_USER=what2cook
DB_PASSWORD=what2cook

# Claves Spoonacular (Frontend y CLI Background Job)
SPOONACULAR_KEY=tu_api_key_frontend
SPOONACULAR_KEY_BACKGROUND=tu_api_key_background

# Proveedores de Traducción (opciones: 'openai', 'gemini', 'libretranslate')
TRANSLATION_PROVIDER=libretranslate
OPENAI_API_KEY=tu_openai_key
GEMINI_API_KEY=tu_gemini_key
LIBRETRANSLATE_URL=http://libretranslate:5000

# Parámetros de Activación de Traducción
ENABLE_INPUT_TRANSLATION=true
ENABLE_OUTPUT_TRANSLATION=true

# Configuración del Preload Job
RECIPES_TABLE_ENABLED=true
TRANSLATION_DAILY_LIMIT=150
TRANSLATION_SEARCH_TERMS=5
```

### 3. Construir las imágenes Docker
Ejecuta la construcción inicial del contenedor PHP:
```bash
docker compose build backend
```

### 4. Levantar los contenedores
Inicia la infraestructura en segundo plano:
```bash
docker compose up -d
```

### 5. Instalar dependencias PHP
Instala las librerías a través de Composer en el contenedor:
```bash
docker compose exec backend composer install
```

### 6. Ejecutar migraciones
Crea la base de datos y ejecuta todas las migraciones pendientes:
```bash
docker compose exec backend ./vendor/bin/phinx migrate
```
*Esto creará la estructura de base de datos incluyendo las tablas de usuarios, favoritos, planes dietarios, traducciones, intentos de login, y tokens persistentes.*

El sitio quedará disponible en **http://localhost:8080**

---

## 5. Autenticación y Seguridad Avanzada

El sistema cuenta con medidas de seguridad robustas implementadas directamente en la capa lógica:

1. **Protección contra Ataques CSRF:** Todo formulario interactivo o petición asíncrona que modifique estado (Login, Registro, Cierre de sesión, Favoritos) genera y valida de forma estricta un token CSRF de sesión único a través de `Session::csrfToken()` y `Session::validateCsrf()`.
2. **Mitigación de Fuerza Bruta (Rate Limiting):** El controlador `AuthController` verifica la dirección IP del cliente contra la tabla `login_attempts`. Si una IP acumula 5 o más intentos fallidos en un rango de 15 minutos, queda bloqueada temporalmente para iniciar sesión durante 15 minutos. Un login exitoso limpia el historial de intentos fallidos de esa IP.
3. **Persistencia Segura ("Remember Me" - Split Tokens):** Para recordar la sesión de los usuarios de manera segura se utiliza un token dividido en dos partes:
   - **Selector:** Cadena aleatoria única que sirve para buscar el registro en la tabla `user_remember_tokens`.
   - **Validador:** Hash criptográfico SHA-256 verificado en la base de datos a partir del validador de la cookie, previniendo ataques de sincronización (*timing attacks*).
   - Las cookies de persistencia se configuran con flags de seguridad modernos: `HttpOnly` (previene robo vía JS), `SameSite=Lax` (protege contra CSRF) y `Secure` (si el canal de conexión es HTTPS).

---

## 6. Subsistema de Traducción y Precarga

Para mitigar los límites de cuota de la API de Spoonacular y ofrecer la aplicación en español de manera transparente, se implementó una arquitectura de traducción híbrida:

### Motores de Traducción
A través de `TranslatorInterface`, el sistema soporta tres proveedores de traducción de manera intercambiable mediante la variable de entorno `TRANSLATION_PROVIDER`:
- **LibreTranslate:** Motor local y open-source (ideal para desarrollo local ilimitado).
- **OpenAI:** Traducción contextual utilizando modelos GPT de OpenAI.
- **Gemini:** Traducción a través del modelo `gemini-2.5-flash-lite`.

### Patrón Decorador de Caché
Cualquier motor de traducción es envuelto dinámicamente por la clase `CachedTranslator`. Esta clase intercepta las peticiones de traducción y almacena localmente en `log/cache/translations/` las traducciones exitosas en formato JSON de forma permanente o con un TTL específico, eliminando el consumo redundante de llamadas a APIs externas.

### Traducción Diferida (Deferred Translation)
Cuando un usuario consulta el detalle de una receta no indexada localmente, el controlador `RecipeController` realiza la consulta original a Spoonacular, renderiza la vista inmediatamente al cliente para evitar retrasos, y delega de manera diferida la traducción al español mediante `DeferredTranslator::afterResponse()`. Esto aprovecha la llamada `fastcgi_finish_request` para continuar el procesamiento en segundo plano después de desconectar el socket del navegador.

### Job CLI de Precarga (`RecipePreloadJob`)
El script ejecutable de consola `bin/preload-recipes.php` realiza tareas periódicas de indexación y traducción masiva de recetas.
- **Rotación de Consultas:** Lee y rota de forma secuencial un pool de palabras clave (dietas, cocinas, tipos de platos) almacenado en `log/.search_index.txt`.
- **Protección de Cuota:** Registra el consumo de puntos de API consumidos hoy en `log/.spoonacular_points.json` y se detiene automáticamente antes de sobrepasar el límite configurado (`TRANSLATION_DAILY_LIMIT`).

---

## 7. Service Worker y Soporte Offline

Para garantizar que el usuario pueda visualizar sus recetas favoritas incluso sin conectividad a internet, la aplicación integra un Service Worker avanzado (`public/sw.js` y `public/assets/js/sw-register.js`):

### Estrategias de Almacenamiento en Caché
- **Stale-While-Revalidate:** Aplicado a los recursos del sistema (`/assets/*`) y las páginas de recetas individuales (`/receta/{id}`). Devuelve de inmediato el contenido en caché (si existe) y realiza una petición a la red en segundo plano para actualizar la caché.
- **Cache-First (con Límite):** Aplicado a las imágenes devueltas por Spoonacular. Reutiliza la imagen local para optimizar datos y mantiene un límite máximo de 60 imágenes en caché, descartando las más antiguas.
- **Navegación Offline Fallback:** Si se navega a cualquier ruta sin conexión y esta no se encuentra cacheada, el SW intercepta la petición y responde con la interfaz amigable `public/offline.html`.

### Sincronización y Reconciliación de Favoritos
- Al iniciar la aplicación en línea, se desencadena un evento `RECONCILE` enviando un mensaje con los IDs de las recetas favoritas actuales al Service Worker.
- El Service Worker realiza un barrido en background para **descargar** todas las recetas favoritas que falten en la caché de recetas y **eliminar** aquellas que el usuario haya quitado de sus favoritos, liberando almacenamiento del dispositivo.
- Adicionalmente, el Service Worker genera y almacena un archivo JSON virtual en caché local (`/offline-favorites.json`) que sirve de índice dinámico para renderizar la lista de recetas favoritas cuando el dispositivo no tiene acceso a internet.
- **Purga por Cierre de Sesión:** Cuando el usuario hace click en *Cerrar Sesión*, el Service Worker intercepta la petición POST de logout y purga de manera inmediata las cachés de imágenes y recetas, impidiendo que usuarios posteriores del navegador vean información privada.

---

## 8. Dependencias PHP Incluidas

Las siguientes dependencias principales se administran mediante Composer y forman parte de la arquitectura del proyecto:

| Librería | Versión | Uso |
| :--- | :--- | :--- |
| **monolog/monolog** | `^3.10` | Registro y formateo de logs del sistema |
| **vlucas/phpdotenv** | `^5.6` | Carga segura de variables de entorno desde el archivo `.env` |
| **robmorgan/phinx** | `^0.16` | Migraciones e inicialización de esquemas de bases de datos relacionales |
| **filp/whoops** | `^2.18` | Manejo visual de errores y depuración detallada en entorno de desarrollo |

---

## 9. Comandos Útiles

```bash
# Ver estado de los contenedores Docker
docker compose ps

# Ver logs en tiempo real de los servicios
docker compose logs -f backend
docker compose logs -f web

# Ejecutar el Job CLI de Precarga de Recetas (en segundo plano / cron)
docker compose exec backend php bin/preload-recipes.php

# Ejecutar el Job CLI de Precarga de Recetas en modo de prueba (sin consumir cuotas)
docker compose exec backend php bin/preload-recipes.php --dry-run

# Forzar una ejecución personalizada del Job con límites específicos
docker compose exec backend php bin/preload-recipes.php --max-points=50 --search-terms=3

# Revertir/Ejecutar migraciones de base de datos
docker compose exec backend ./vendor/bin/phinx migrate
docker compose exec backend ./vendor/bin/phinx rollback

# Entrar a la terminal interactiva del contenedor PHP
docker compose exec backend sh

# Detener los servicios del proyecto
docker compose down

# Apagar los servicios y eliminar volúmenes asociados (restablece base de datos)
docker compose down -v
```

---
*Nota: Recuerda que la carpeta `vendor/` y el archivo `.env` local no se agregan al repositorio git por motivos de seguridad y buenas prácticas de desarrollo.*
