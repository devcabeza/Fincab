# Desarrollo móvil con NativePHP

El proyecto integra NativePHP for Mobile para ejecutar Laravel en Android e iOS. La configuración de la aplicación vive en Laravel.

## Requisitos

- PHP 8.3 o superior y Composer.
- Para Android: Java, Android SDK y `adb` disponibles en el `PATH`.
- Para iOS: macOS con Xcode; la instalación de iOS solo se admite en macOS.

Consulta los [requisitos oficiales](https://nativephp.com/docs/mobile/4/getting-started/environment-setup) antes de preparar el entorno nativo.

## Instalación

1. Instala las dependencias PHP: `composer install`.
2. Copia `.env.example` a `.env` y configura `NATIVEPHP_APP_ID` con el identificador inverso de dominio del producto, por ejemplo `com.laravertex.app`.
3. Prepara Android: `php artisan native:install android` (o `make native-install`). En macOS, usa `php artisan native:install both` para preparar ambas plataformas.

El instalador añade los archivos nativos y descarga los binarios PHP necesarios. Conserva `NATIVEPHP_APP_ID` una vez que publiques la aplicación, porque las tiendas usan ese identificador para reconocerla.

## Ejecutar y empaquetar

- `php artisan native:run` inicia la app en un dispositivo conectado o emulador Android.
- `php artisan native:package android` empaqueta Android para distribución; configura antes las credenciales de firma.
- En macOS, usa `php artisan native:package ios` para empaquetar iOS con los certificados y perfiles de aprovisionamiento correspondientes.

Consulta las guías oficiales de [Android](https://nativephp.com/docs/mobile/4/publishing/android) e [iOS](https://nativephp.com/docs/mobile/4/publishing/ios) para la firma y distribución. Nunca guardes claves de firma en el repositorio.
