# env-builder

CLI en PHP 8.2+ para compilar múltiples fragmentos de entorno desde `./.env.d` en un único `.env`, con soporte de overlays `*.env.dev`, trazabilidad por bloque y despliegue remoto vía `scp`/`rsync`.

## Estructura recomendada

```text
.
├─ .env.d/
│  ├─ app.env
│  ├─ app.env.dev
│  └─ database.env
├─ bin/
│  └─ env-builder
├─ src/
│  ├─ Console/
│  ├─ Deploy/
│  ├─ Env/
│  ├─ Exception/
│  ├─ Laravel/
│  └─ Service/
├─ build.php
└─ composer.json
```

## Instalación como paquete Composer (Laravel u otros proyectos PHP)

```bash
composer require equidna/env-builder
```

Comando binario:

```bash
vendor/bin/env-builder build --dev
```

En Laravel (opcional), también queda disponible como Artisan:

```bash
php artisan env-builder:build --dev
```

## Uso CLI

Compilar `.env.d` -> `.env`:

```bash
php bin/env-builder build
```

Compilar incluyendo overlays `*.env.dev`:

```bash
php bin/env-builder build --dev
```

Salida personalizada:

```bash
php bin/env-builder build --source=.env.d --output=.env.production
```

Compilar y desplegar por SSH:

```bash
php bin/env-builder build --dev --deploy="usuario@ip:/ruta/destino/.env"
```

Prueba E2E reproducible (`--dev` + prioridad de `app.env`/`app.env.dev`):

```bash
composer test:e2e
```

O ejecutando el binario directamente:

```bash
php bin/env-builder-e2e-test
```

El binario de prueba escribe su fixture en un directorio temporal del sistema (`%TEMP%/env-builder-e2e` en Windows).

## Formato de salida compilada

El `.env` generado incluye trazabilidad por bloque:

```dotenv
### [app.env] ###
APP_NAME=MyApp

### [app.env.dev] ###
APP_DEBUG=true
```

Si una variable se redefine en un archivo posterior (incluyendo `.env.dev`), la última definición reemplaza la anterior y en el archivo compilado solo queda una entrada por clave.

Además, cuando existen, `app.env` y `app.env.dev` se procesan primero para que el archivo compilado comience con esos bloques.

## Build de PHAR (distribución universal)

1. Instalar dependencias:

```bash
composer install --no-dev --optimize-autoloader
```

2. Compilar PHAR:

```bash
composer build:phar
```

3. Ejecutar el binario autónomo:

```bash
./dist/env-builder.phar build --dev
```

En Windows:

```powershell
php .\dist\env-builder.phar build --dev
```
