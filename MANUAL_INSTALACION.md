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
- Node.js y npm solo son opcionales si se quieren compilar recursos de Vite; no hacen falta para probar la API
- Laravel Sanctum instalado como dependencia

## 2. Requisitos antes de empezar

Instalar estos programas antes de abrir el proyecto:

- PHP 8.2 o superior
- Composer 2.x
- MySQL 8.x o MariaDB 10.x
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

Node.js y npm no son obligatorios para esta API. Si solo se va a levantar el backend y probar endpoints en Postman, se pueden ignorar.

## 3. Instalacion de requisitos por sistema operativo

### Windows

Opcion recomendada:

1. Instalar XAMPP desde https://www.apachefriends.org.
2. Abrir el Panel de Control de XAMPP.
3. Iniciar el servicio de MySQL desde XAMPP.
4. Usar el PHP incluido con XAMPP o instalar PHP aparte si se prefiere.
5. Instalar Composer desde https://getcomposer.org/download.
6. Instalar Git desde https://git-scm.com/download/win.

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
brew install php composer mysql git
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
3. Abrir una terminal dentro de la carpeta raiz, donde estan `artisan` y `composer.json`.

Ejemplo:

```bash
cd /ruta/al/proyecto
```

En Windows:

```powershell
cd C:\Users\TU_USUARIO\Desktop\api-laravel
```

## 5. Que hacer dentro de la raiz del proyecto

Cuando ya estan instaladas las herramientas principales, hay que trabajar dentro de la carpeta raiz del proyecto. La carpeta raiz es la carpeta donde se ven estos archivos:

```txt
artisan
composer.json
composer.lock
package.json
.env.example
routes/
app/
database/
```

Si no ves `artisan` y `composer.json`, no estas en la carpeta correcta.

### 5.1. Abrir la terminal en la carpeta correcta

En Windows:

1. Abrir la carpeta del proyecto.
2. Dar clic derecho dentro de la carpeta.
3. Seleccionar `Abrir en Terminal` o `Open in Terminal`.
4. Comprobar la ubicacion con:

```powershell
dir
```

Debe aparecer algo parecido a:

```txt
artisan
composer.json
package.json
```

Tambien puedes entrar manualmente:

```powershell
cd C:\Users\TU_USUARIO\Desktop\api-laravel
```

En macOS o Linux:

```bash
cd /ruta/donde/esta/api-laravel
ls
```

Debe aparecer:

```txt
artisan
composer.json
package.json
```

### 5.2. Verificar que las herramientas se reconocen

Dentro de la raiz del proyecto ejecutar:

```bash
php -v
composer --version
```

Si alguno dice que el comando no existe, no sigas todavia. Primero hay que instalarlo o arreglar el `PATH`.

En Windows con XAMPP, si `php` no se reconoce:

1. Buscar la carpeta `C:\xampp\php`.
2. Copiar esa ruta.
3. Abrir `Variables de entorno`.
4. Editar la variable `Path`.
5. Agregar:

```txt
C:\xampp\php
```

6. Cerrar y abrir de nuevo la terminal.
7. Probar:

```powershell
php -v
```

### 5.3. Activar extensiones PHP necesarias en XAMPP

En Windows con XAMPP:

1. Abrir el Panel de Control de XAMPP.
2. En la fila de Apache, presionar `Config`.
3. Abrir `PHP (php.ini)`.
4. Buscar estas lineas y asegurarse de que no tengan `;` al inicio:

```ini
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=pdo_sqlite
extension=zip
```

Si alguna aparece asi:

```ini
;extension=pdo_mysql
```

Debe quedar asi:

```ini
extension=pdo_mysql
```

Guardar el archivo y reiniciar Apache desde XAMPP. Aunque esta API se levanta con `php artisan serve`, reiniciar ayuda a que PHP tome la configuracion.

En macOS o Linux normalmente estas extensiones se instalan con los paquetes indicados en la seccion de requisitos.

### 5.4. Instalar dependencias PHP del proyecto

Esto crea la carpeta `vendor`, que contiene las librerias de Laravel y del proyecto.

Ejecutar:

```bash
composer install
```

Si termina bien, debe existir una carpeta llamada:

```txt
vendor/
```

Si Composer muestra errores por extensiones faltantes, instalar o activar la extension indicada y repetir:

```bash
composer install
```

Si el proyecto ya tenia carpeta `vendor`, igualmente es recomendable ejecutar:

```bash
composer install
composer dump-autoload
```

### 5.5. Crear el archivo `.env`

Laravel usa un archivo `.env` para la configuracion local. Si no existe:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Despues de este paso debe existir un archivo llamado:

```txt
.env
```

Ese archivo es privado de cada laptop. No todas las computadoras tienen el mismo usuario de base de datos, contrasena o puerto, por eso se configura ahi.

Generar la llave de la aplicacion:

```bash
php artisan key:generate
```

Esto llena la variable `APP_KEY` dentro del `.env`. Si `APP_KEY` queda vacia, Laravel no funciona bien.

### 5.6. Abrir y editar el archivo `.env`

Abrir `.env` con VS Code, Bloc de notas, Notepad++ o el editor que usen.

En VS Code:

```bash
code .env
```

Si `code` no funciona, abrir VS Code normal y arrastrar el archivo `.env`.

Hay que revisar estas partes:

```env
APP_NAME="Academic Appointment Management API"
APP_ENV=local
APP_KEY=base64:AQUI_DEBE_HABER_UNA_LLAVE_GENERADA
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

No copiar literalmente `AQUI_DEBE_HABER_UNA_LLAVE_GENERADA`. Esa linea la crea el comando:

```bash
php artisan key:generate
```

### 5.7. Configurar base de datos con XAMPP en Windows

Esta es la opcion recomendada para Windows porque fue la usada por el equipo.

1. Abrir XAMPP.
2. Iniciar `MySQL`.
3. Abrir el navegador.
4. Entrar a:

```txt
http://localhost/phpmyadmin
```

5. Dar clic en `Nueva` o `New`.
6. Crear una base de datos con este nombre:

```txt
api_academica
```

7. Elegir cotejamiento:

```txt
utf8mb4_unicode_ci
```

8. Dar clic en `Crear`.

Luego editar `.env` y dejar la base de datos asi:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_academica
DB_USERNAME=root
DB_PASSWORD=
```

Importante:

- En XAMPP normalmente el usuario es `root`.
- En XAMPP normalmente la contrasena esta vacia.
- `DB_PASSWORD=` debe quedar vacio, sin espacios y sin comillas.
- El nombre `api_academica` debe ser exactamente igual al de phpMyAdmin.

### 5.8. Configurar base de datos en macOS o Linux

Si se usa MySQL o MariaDB por terminal:

```bash
mysql -u root -p
```

Crear la base:

```sql
CREATE DATABASE api_academica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Editar `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_academica
DB_USERNAME=root
DB_PASSWORD=TU_CONTRASENA_DE_MYSQL
```

Si el usuario `root` no tiene contrasena, dejar:

```env
DB_PASSWORD=
```

Si prefieres crear un usuario propio:

```sql
CREATE USER 'api_user'@'localhost' IDENTIFIED BY 'api_password';
GRANT ALL PRIVILEGES ON api_academica.* TO 'api_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Y en `.env`:

```env
DB_USERNAME=api_user
DB_PASSWORD=api_password
```

### 5.9. Configurar SQLite si no se quiere usar MySQL

SQLite es mas simple porque no necesita servidor de base de datos, pero para presentar el proyecto se recomienda XAMPP/MySQL.

Crear el archivo:

En macOS o Linux:

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

Y comentar o borrar las variables de MySQL:

```env
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_USERNAME=root
# DB_PASSWORD=
```

### 5.10. Configurar la API externa de usuarios

Este proyecto usa otra API para validar el login. En el `.env` hay que revisar:

```env
USERS_API_URL=http://127.0.0.1:8001/api
```

Si van a probar login, esa otra API debe estar levantada en esa direccion.

Si no tienen esa API externa encendida, esta API igual puede levantar y muchos endpoints pueden responder, pero el login puede devolver `503`.

### 5.11. Configuracion final recomendada del `.env`

Para Windows con XAMPP, una configuracion local tipica seria:

```env
APP_NAME="Academic Appointment Management API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_academica
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

FILESYSTEM_DISK=local
USERS_API_URL=http://127.0.0.1:8001/api
```

No borrar `APP_KEY` si ya fue generada.

### 5.12. Limpiar configuracion despues de editar `.env`

Cada vez que se cambia `.env`, ejecutar:

```bash
php artisan optimize:clear
```

Esto evita que Laravel use datos viejos guardados en cache.

### 5.13. Crear las tablas de la base de datos

Ejecutar:

```bash
php artisan migrate
```

Si la base esta vacia y es una instalacion desde cero, tambien se puede usar:

```bash
php artisan migrate:fresh
```

Advertencia: `migrate:fresh` borra las tablas existentes. Usarlo solo en una base de datos de prueba o desarrollo.

### 5.14. Cargar datos iniciales

Ejecutar:

```bash
php artisan db:seed
```

O en una instalacion limpia:

```bash
php artisan migrate:fresh --seed
```

### 5.15. Preparar almacenamiento de archivos

Ejecutar:

```bash
php artisan storage:link
```

Si dice que el enlace ya existe, no hay problema.

### 5.16. Probar que Laravel esta bien instalado

Ejecutar:

```bash
php artisan about
```

Tambien se pueden correr las pruebas:

```bash
php artisan test
```

### 5.17. Levantar la API

Ejecutar:

```bash
php artisan serve
```

Debe aparecer algo parecido a:

```txt
Server running on [http://127.0.0.1:8000].
```

No cierres esa terminal mientras estes usando la API.

Abrir otra terminal si necesitas ejecutar otros comandos.

### 5.18. Probar la API en el navegador o Postman

Abrir:

```txt
http://127.0.0.1:8000/api/provincia
```

Si devuelve texto en formato JSON, la API esta funcionando.

En Postman:

- Metodo: `GET`
- URL: `http://127.0.0.1:8000/api/provincia`
- Header:

```txt
Accept: application/json
```

### 5.19. Orden exacto de comandos para una laptop nueva

Desde la raiz del proyecto:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test
php artisan serve
```

En Windows PowerShell, cambiar solo el comando de copiar `.env`:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test
php artisan serve
```

Recordatorio: antes de `php artisan migrate:fresh --seed`, ya debe existir la base de datos `api_academica` y el `.env` debe tener bien los datos de conexion.

## 6. Detalle de la API externa de usuarios

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

## 7. Ejecutar migraciones

Con la base de datos ya creada y el `.env` configurado:

```bash
php artisan migrate
```

Si es una instalacion desde cero y se quiere reiniciar todo:

```bash
php artisan migrate:fresh
```

Advertencia: `migrate:fresh` borra todas las tablas de la base de datos configurada. Usarlo solo en desarrollo o en una base de datos de prueba.

## 8. Cargar datos iniciales

Para insertar datos de prueba y catalogos:

```bash
php artisan db:seed
```

Para reiniciar base de datos y cargar seeders en una instalacion nueva:

```bash
php artisan migrate:fresh --seed
```

Este proyecto tiene seeders para programas de formacion, anos academicos, provincias, asignaturas, cursos, modalidades, calificaciones, usuarios/accesos, facultades, departamentos, estudiantes, profesores, PPA, TDPP, grupos, sectores estrategicos y otros catalogos.

## 9. Crear enlace de almacenamiento

El proyecto genera y guarda documentos/logos en `storage`. Crear el enlace publico:

```bash
php artisan storage:link
```

Si el comando dice que el enlace ya existe, no pasa nada.

## 10. Limpiar y reconstruir cache

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

## 11. Levantar el servidor de la API

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

## 12. Probar que la API responde

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

## 13. Probar login

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

## 14. Probar rutas principales

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

## 15. Conectar un frontend

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

## 16. Comandos diarios de desarrollo

Levantar API:

```bash
php artisan serve
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

## 17. Validacion final de instalacion

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

## 18. Errores comunes y soluciones

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

## 19. Instalacion limpia resumida

Estos son los comandos principales para una instalacion nueva:

```bash
composer install
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

## 20. Recomendacion para demostraciones

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

## 21. Comando de prueba final

Con el servidor levantado:

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/provincia
```

Si responde JSON, la API esta corriendo correctamente.
