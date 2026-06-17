# Manual de instalacion detallado de la API

Este manual explica como instalar y ejecutar correctamente esta API Laravel en una laptop con Windows, macOS o Linux. La idea es que cualquier persona pueda levantar el backend, cargar la base de datos, probar endpoints y detectar rapido los errores comunes.

## 1. Que es este proyecto

Esta API esta desarrollada con Laravel 11 y PHP 8.2+. Expone servicios REST para gestionar informacion academica, planes de estudio, profesores, estudiantes, PPA, alumnos ayudantes, documentos, indicadores, accesos y notificaciones.

Tecnologias principales:

- PHP 8.2 o superior
- Laravel 11
- Composer
- MySQL o MariaDB recomendado
- SQLite opcional para pruebas locales simples
- Node.js y npm para compilar assets si se usan vistas o recursos de Vite
- Laravel Sanctum instalado como dependencia

## 2. Requisitos antes de empezar

Instalar estos programas antes de abrir el proyecto:

- PHP 8.2 o superior
- Composer 2.x
- MySQL 8.x o MariaDB 10.x
- Node.js 18 o superior
- npm
- Git
- Un editor de codigo, por ejemplo VS Code
- Postman, Insomnia, Thunder Client o curl para probar la API

Extensiones PHP recomendadas:

- `bcmath`
- `ctype`
- `curl`
- `dom`
- `fileinfo`
- `gd`
- `intl`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `pdo_mysql`
- `pdo_sqlite`
- `tokenizer`
- `xml`
- `zip`

Para verificar PHP:

```bash
php -v
php -m
```

Para verificar Composer:

```bash
composer --version
```

Para verificar Node y npm:

```bash
node -v
npm -v
```

## 3. Instalacion de requisitos por sistema operativo

### Windows

Opcion recomendada:

1. Instalar XAMPP desde https://www.apachefriends.org.
2. Abrir el Panel de Control de XAMPP.
3. Iniciar el servicio de MySQL desde XAMPP.
4. Usar el PHP incluido con XAMPP o instalar PHP aparte si se prefiere.
5. Instalar Composer desde https://getcomposer.org/download.
6. Instalar Node.js LTS desde https://nodejs.org.
7. Instalar Git desde https://git-scm.com/download/win.

Esta es la opcion recomendada porque fue la usada durante el desarrollo y las pruebas del proyecto. XAMPP facilita tener MySQL y PHP listos en una misma herramienta, lo que reduce problemas de instalacion en laptops diferentes.

Alternativa:

1. Instalar Laragon desde https://laragon.org/download.
2. Activar Apache/Nginx y MySQL desde Laragon.
3. Asegurarse de que `php` este disponible en la terminal.

Comprobar en PowerShell:

```powershell
php -v
composer --version
mysql --version
node -v
npm -v
git --version
```

Si `php` no se reconoce, hay que agregar la carpeta de PHP al `PATH`. En XAMPP suele estar en:

```txt
C:\xampp\php
```

En Laragon suele estar en una ruta parecida a:

```txt
C:\laragon\bin\php\php-8.x.x
```

### macOS

Instalar Homebrew si no esta instalado:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Instalar dependencias:

```bash
brew install php composer mysql node git
```

Iniciar MySQL:

```bash
brew services start mysql
```

Comprobar:

```bash
php -v
composer --version
mysql --version
node -v
npm -v
git --version
```

### Linux Ubuntu/Debian

Actualizar paquetes:

```bash
sudo apt update
sudo apt upgrade
```

Instalar PHP y extensiones:

```bash
sudo apt install php php-cli php-mbstring php-xml php-curl php-zip php-gd php-intl php-mysql php-sqlite3 unzip git mysql-server
```

Instalar Composer:

```bash
sudo apt install composer
```

Instalar Node.js y npm:

```bash
sudo apt install nodejs npm
```

Iniciar MySQL:

```bash
sudo systemctl start mysql
sudo systemctl enable mysql
```

Comprobar:

```bash
php -v
composer --version
mysql --version
node -v
npm -v
git --version
```

## 4. Descargar o copiar el proyecto

Si se descarga desde GitHub:

```bash
git clone URL_DEL_REPOSITORIO
cd NOMBRE_DEL_PROYECTO
```

Si se copia por memoria USB, ZIP, Telegram u otro medio:

1. Descomprimir el proyecto en una carpeta sin caracteres raros.
2. Evitar rutas con tildes o nombres muy largos.
3. Abrir una terminal dentro de la carpeta raiz, donde estan `artisan`, `composer.json` y `package.json`.

Ejemplo:

```bash
cd /ruta/al/proyecto
```

En Windows:

```powershell
cd C:\Users\TU_USUARIO\Desktop\api-laravel
```

## 5. Instalar dependencias PHP

Desde la raiz del proyecto:

```bash
composer install
```

Si Composer muestra errores por extensiones faltantes, instalar la extension indicada y repetir:

```bash
composer install
```

Si el proyecto ya tenia carpeta `vendor`, igualmente es recomendable ejecutar:

```bash
composer install
composer dump-autoload
```

## 6. Instalar dependencias de Node

Aunque la API funciona principalmente con PHP, el proyecto incluye Vite. Para tener todo completo:

```bash
npm install
```

Para compilar assets:

```bash
npm run build
```

Para desarrollo con Vite:

```bash
npm run dev
```

Si solo se va a probar la API desde Postman y no se usaran vistas, `npm run dev` no es obligatorio.

## 7. Crear el archivo de ambiente

Laravel usa un archivo `.env` para la configuracion local. Si no existe:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generar la llave de la aplicacion:

```bash
php artisan key:generate
```

## 8. Configurar la base de datos

### Opcion recomendada: MySQL o MariaDB

Crear una base de datos vacia.

Entrar a MySQL:

```bash
mysql -u root -p
```

Crear la base de datos:

```sql
CREATE DATABASE api_academica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Crear un usuario opcional:

```sql
CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'api_password';
GRANT ALL PRIVILEGES ON api_academica.* TO 'api_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Editar `.env`:

```env
APP_NAME="Academic Appointment Management API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_academica
DB_USERNAME=api_user
DB_PASSWORD=api_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

USERS_API_URL=http://127.0.0.1:8001/api
```

Si se usa el usuario `root` sin contrasena en Laragon/XAMPP:

```env
DB_USERNAME=root
DB_PASSWORD=
```

### Opcion rapida: SQLite

SQLite puede servir para pruebas locales simples, pero para una demostracion completa se recomienda MySQL/MariaDB.

Crear el archivo de base de datos:

```bash
touch database/database.sqlite
```

En Windows PowerShell:

```powershell
New-Item database/database.sqlite -ItemType File
```

Editar `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Comentar o eliminar estas variables si estaban configuradas para MySQL:

```env
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_USERNAME=root
# DB_PASSWORD=
```

## 9. Configurar la API externa de usuarios

El login de este proyecto no valida usuarios directamente en esta base de datos. Usa un servicio externo configurado con:

```env
USERS_API_URL=http://127.0.0.1:8001/api
```

La API externa debe responder:

- `POST /api/users/validate`
- `GET /api/users`

El endpoint de validacion debe aceptar:

```json
{
  "username": "usuario01",
  "password": "contrasena"
}
```

Y debe devolver algo parecido a:

```json
{
  "valid": true,
  "user": {
    "username": "usuario01"
  }
}
```

Si esa API externa no esta levantada o la URL esta mal, el login de esta API respondera con error `503` y el mensaje:

```json
{
  "message": "No se pudo validar el usuario en la API de usuarios."
}
```

Para probar el resto de endpoints que no dependan de login, se puede levantar esta API sin el servicio externo. Para probar login completo, hay que levantar tambien la API de usuarios o configurar `USERS_API_URL` con la direccion correcta.

## 10. Ejecutar migraciones

Con la base de datos ya creada y el `.env` configurado:

```bash
php artisan migrate
```

Si es una instalacion desde cero y se quiere reiniciar todo:

```bash
php artisan migrate:fresh
```

Advertencia: `migrate:fresh` borra todas las tablas de la base de datos configurada. Usarlo solo en desarrollo o en una base de datos de prueba.

## 11. Cargar datos iniciales

Para insertar datos de prueba y catalogos:

```bash
php artisan db:seed
```

Para reiniciar base de datos y cargar seeders en una instalacion nueva:

```bash
php artisan migrate:fresh --seed
```

Este proyecto tiene seeders para programas de formacion, anos academicos, provincias, asignaturas, cursos, modalidades, calificaciones, usuarios/accesos, facultades, departamentos, estudiantes, profesores, PPA, TDPP, grupos, sectores estrategicos y otros catalogos.

## 12. Crear enlace de almacenamiento

El proyecto genera y guarda documentos/logos en `storage`. Crear el enlace publico:

```bash
php artisan storage:link
```

Si el comando dice que el enlace ya existe, no pasa nada.

## 13. Limpiar y reconstruir cache

Despues de cambiar `.env` o configuraciones:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

Luego:

```bash
php artisan config:cache
```

En desarrollo, si se cambian variables muchas veces, se puede trabajar sin `config:cache`.

## 14. Levantar el servidor de la API

Desde la raiz del proyecto:

```bash
php artisan serve
```

Por defecto queda en:

```txt
http://127.0.0.1:8000
```

Si el puerto 8000 esta ocupado:

```bash
php artisan serve --port=8002
```

La base de la API sera:

```txt
http://127.0.0.1:8000/api
```

## 15. Probar que la API responde

Abrir en navegador:

```txt
http://127.0.0.1:8000
```

Probar un endpoint publico:

```bash
curl http://127.0.0.1:8000/api/provincia
```

Probar con Postman:

- Metodo: `GET`
- URL: `http://127.0.0.1:8000/api/provincia`
- Headers: `Accept: application/json`

Si todo esta bien, debe responder JSON.

## 16. Probar login

Solo funcionara si la API externa de usuarios esta configurada y levantada.

En Postman:

- Metodo: `POST`
- URL: `http://127.0.0.1:8000/api/login`
- Headers:
  - `Accept: application/json`
  - `Content-Type: application/json`
- Body raw JSON:

```json
{
  "username": "usuario01",
  "password": "contrasena",
  "application": "gestion_roles"
}
```

Posibles respuestas:

Login correcto:

```json
{
  "valid": true,
  "user": {
    "username": "usuario01"
  },
  "application_code": "gestion_roles",
  "can_access": true,
  "access": []
}
```

Credenciales incorrectas:

```json
{
  "valid": false,
  "message": "Usuario o contrasena incorrectos."
}
```

API externa no disponible:

```json
{
  "message": "No se pudo validar el usuario en la API de usuarios."
}
```

## 17. Probar rutas principales

Listar rutas disponibles:

```bash
php artisan route:list --path=api
```

Endpoints utiles para comprobar que la instalacion quedo bien:

```txt
GET  /api/provincia
GET  /api/municipio
GET  /api/universidad
GET  /api/facultad
GET  /api/departamento
GET  /api/progForm
GET  /api/a_academico
GET  /api/curso
GET  /api/asignatura
GET  /api/profesor
GET  /api/estudiante
GET  /api/ppa
GET  /api/alumno-ayudante
GET  /api/documentos
```

Ejemplo con curl:

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/facultad
```

## 18. Conectar un frontend

Si existe un frontend Vue u otro cliente, configurar su URL base de API como:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

En esta API, CORS esta configurado para aceptar solicitudes a `api/*` desde cualquier origen durante desarrollo.

Si el frontend corre en:

```txt
http://localhost:5173
```

Y la API en:

```txt
http://127.0.0.1:8000
```

Las llamadas deben apuntar a:

```txt
http://127.0.0.1:8000/api/...
```

## 19. Comandos diarios de desarrollo

Levantar API:

```bash
php artisan serve
```

Levantar Vite:

```bash
npm run dev
```

Ver rutas:

```bash
php artisan route:list --path=api
```

Ejecutar pruebas:

```bash
php artisan test
```

Limpiar cache:

```bash
php artisan optimize:clear
```

Reconstruir autoload:

```bash
composer dump-autoload
```

Reiniciar base de datos con datos:

```bash
php artisan migrate:fresh --seed
```

## 20. Validacion final de instalacion

Antes de decir que la API esta lista, comprobar:

1. `php -v` muestra PHP 8.2 o superior.
2. `composer install` termina sin errores.
3. Existe archivo `.env`.
4. `APP_KEY` tiene valor.
5. La base de datos existe.
6. Las credenciales de `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` son correctas.
7. `php artisan migrate` termina sin errores.
8. `php artisan db:seed` termina sin errores.
9. `php artisan storage:link` esta ejecutado.
10. `php artisan serve` levanta el servidor.
11. `GET http://127.0.0.1:8000/api/provincia` devuelve JSON.
12. Si se va a usar login, `USERS_API_URL` apunta a una API de usuarios funcionando.
13. `POST /api/login` responde correctamente con usuarios validos.

## 21. Errores comunes y soluciones

### Error: `No application encryption key has been specified`

Falta generar la llave:

```bash
php artisan key:generate
php artisan optimize:clear
```

### Error: `could not find driver`

Falta la extension PHP para la base de datos.

Para MySQL:

```bash
sudo apt install php-mysql
```

Para SQLite:

```bash
sudo apt install php-sqlite3
```

En Windows, activar en `php.ini`:

```ini
extension=pdo_mysql
extension=pdo_sqlite
```

Reiniciar terminal/servidor despues.

### Error: `Access denied for user`

Usuario o contrasena incorrectos en `.env`.

Revisar:

```env
DB_USERNAME=
DB_PASSWORD=
```

Luego limpiar cache:

```bash
php artisan optimize:clear
```

### Error: `Unknown database`

La base de datos no existe. Crear la base:

```sql
CREATE DATABASE api_academica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Error: `SQLSTATE[HY000] [2002] Connection refused`

MySQL no esta encendido o el puerto esta mal.

Windows:

- Abrir Laragon/XAMPP.
- Iniciar MySQL.

macOS:

```bash
brew services start mysql
```

Linux:

```bash
sudo systemctl start mysql
```

### Error: cambios en `.env` no se aplican

Laravel puede tener configuracion cacheada:

```bash
php artisan optimize:clear
php artisan config:clear
```

### Error: `Class not found`

Regenerar autoload:

```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: `The stream or file storage/logs/laravel.log could not be opened`

Problema de permisos en `storage` o `bootstrap/cache`.

Linux/macOS:

```bash
chmod -R 775 storage bootstrap/cache
```

Si hace falta:

```bash
sudo chown -R $USER:www-data storage bootstrap/cache
```

Windows:

- Verificar que la carpeta no este en modo solo lectura.
- Evitar ejecutar el proyecto dentro de carpetas protegidas del sistema.

### Error: `Vite manifest not found`

Compilar assets:

```bash
npm install
npm run build
```

Durante desarrollo tambien se puede ejecutar:

```bash
npm run dev
```

### Error: login responde 503

La API externa de usuarios no esta disponible o `USERS_API_URL` esta mal.

Revisar:

```env
USERS_API_URL=http://127.0.0.1:8001/api
```

Probar:

```bash
curl http://127.0.0.1:8001/api/users
```

Si esa URL no responde, levantar la API de usuarios o cambiar `USERS_API_URL`.

### Error: el frontend no conecta con la API

Revisar:

1. La API esta levantada con `php artisan serve`.
2. El frontend apunta a `http://127.0.0.1:8000/api`.
3. Se esta usando `Accept: application/json`.
4. No hay error de puerto ocupado.
5. CORS esta activo para `api/*`.

## 22. Instalacion limpia resumida

Estos son los comandos principales para una instalacion nueva:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Si la base de datos es nueva y se quiere cargar todo desde cero:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

## 23. Recomendacion para demostraciones

Para que la API funcione bien en cualquier laptop durante una presentacion:

1. En Windows, instalar XAMPP como primera opcion, porque fue la herramienta usada para preparar y probar el proyecto.
2. Usar el MySQL de XAMPP en vez de depender de una base remota.
3. Tener una copia del proyecto con `composer install` ya ejecutado.
4. Tener una copia del `.env` de ejemplo lista, sin contrasenas reales.
5. Tener los seeders cargados.
6. Probar antes estos endpoints:

```txt
GET /api/provincia
GET /api/facultad
GET /api/departamento
GET /api/progForm
GET /api/ppa
GET /api/alumno-ayudante
```

7. Si se va a mostrar login, levantar tambien la API externa de usuarios.
8. Si no se va a mostrar login, explicar que el login depende de `USERS_API_URL`.
9. Llevar Postman/Insomnia con una coleccion preparada.
10. Confirmar que el puerto 8000 esta libre.
11. Si el puerto esta ocupado, usar:

```bash
php artisan serve --port=8002
```

Y probar:

```txt
http://127.0.0.1:8002/api/provincia
```

## 24. Comando de prueba final

Con el servidor levantado:

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/provincia
```

Si responde JSON, la API esta corriendo correctamente.
