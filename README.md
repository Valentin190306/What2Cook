# What2Cook

Repositorio de trabajo de la catedrá de Programación en Ambiente Web.

Estudiantes:
* Contardi, Gustavo
* Zander, Matt
* Romero Monteagudo, Valentín Joel

# Entorno de Desarrollo

## Stack

| Capa | Tecnología |
|---|---|
| Servidor web | Nginx (Alpine) |
| Backend | PHP 8.3 FPM (Alpine) |
| Base de datos | PostgreSQL 16 (Alpine) |
| Contenedores | Docker + Docker Compose |

## Estructura del proyecto

```
proyecto/
├── docker/
│   ├── nginx.conf          # Configuración de Nginx
│   └── php.Dockerfile      # Imagen PHP con extensiones y Composer
├── public/                 # Raíz web — único directorio accesible desde el navegador
│   ├── index.php
│   └── assets/
│       ├── html/
│       ├── estilos/
│       └── img/
├──src/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── CatalogoController.php
│   │   └── ...
│   ├── Core/
│   │   ├── Router.php       ← maneja las rutas
│   │   └── ...
│   ├── Models/
│   │   ├── Libro.php
│   │   └── ...
│   └── Views/
│       ├── home.php
│       ├── catalogo.php
│       └── layouts/
│           └── main.php      ← header + footer compartido
├── bootstrap.php
├── vendor/                 # Generado por Composer — no se commitea
├── .env                    # Variables de entorno locales — no se commitea
├── .env.example            # Plantilla de variables de entorno — sí se commitea
├── .gitignore
├── composer.json
├── composer.lock
└── docker-compose.yml
```

## Requisitos previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) o Docker Engine + Docker Compose (Linux)
- Git

## Primeros pasos

### 1. Clonar el repositorio

```bash
git clone <url-del-repo>
cd pawprints
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Editá `.env` con tus valores locales:

```env
DB_HOST=db
DB_PORT=5432
DB_NAME=what2cook
DB_USER=what2cook
DB_PASSWORD=what2cook
```

### 3. Construir las imágenes

Solo es necesario la primera vez, o cuando se modifica el `backend.Dockerfile`:

```bash
docker compose build backend
```

### 4. Levantar los contenedores

```bash
docker compose up -d
```

### 5. Instalar dependencias PHP

Solo es necesario la primera vez, o cuando se modifica `composer.json`:

```bash
docker compose exec backend composer install
```

### 6. Verificar que todo levantó

```bash
docker compose ps
```

Los tres servicios deben aparecer con estado `running`:

```
NAME              SERVICE       STATUS
pawprints-db-1    database      running
pawprints-php-1   backend       running
pawprints-web-1   web           running
```

El sitio queda disponible en **http://localhost:8080**

## Dependencias PHP

| Librería | Versión | Uso |
|---|---|---|
| monolog/monolog | ^3.10 | Logging |
| vlucas/phpdotenv | ^5.6 | Variables de entorno |
| robmorgan/phinx | ^0.16 | Migraciones de base de datos |

## Comandos útiles

```bash
# Ver logs de un servicio específico
docker compose logs php
docker compose logs web
docker compose logs db

# Reiniciar un servicio sin bajar los demás
docker compose restart web

# Entrar al contenedor PHP
docker compose exec php sh

# Bajar todos los contenedores
docker compose down

# Bajar y eliminar volúmenes (borra la base de datos)
docker compose down -v
```

## Archivos en .gitignore

```
vendor/
.env
```

`vendor/` se regenera con `composer install`.  
`.env` contiene credenciales locales — nunca se commitea. Usar `.env.example` como referencia.