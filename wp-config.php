<?php
/** 
 * Configuración básica de WordPress.
 *
 * Este archivo contiene las siguientes configuraciones: ajustes de MySQL, prefijo de tablas,
 * claves secretas, idioma de WordPress y ABSPATH. Para obtener más información,
 * visita la página del Codex{@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} . Los ajustes de MySQL te los proporcionará tu proveedor de alojamiento web.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** Ajustes de MySQL. Solicita estos datos a tu proveedor de alojamiento web. ** //
/** El nombre de tu base de datos de WordPress */
define('DB_NAME', 'pensionadeco');

/** Tu nombre de usuario de MySQL */
define('DB_USER', 'root');

/** Tu contraseña de MySQL */
define('DB_PASSWORD', '');

/** Host de MySQL (es muy probable que no necesites cambiarlo) */
define('DB_HOST', 'localhost');

/** Codificación de caracteres para la base de datos. */
define('DB_CHARSET', 'utf8mb4');

/** Cotejamiento de la base de datos. No lo modifiques si tienes dudas. */
define('DB_COLLATE', '');

/**#@+
 * Claves únicas de autentificación.
 *
 * Define cada clave secreta con una frase aleatoria distinta.
 * Puedes generarlas usando el {@link https://api.wordpress.org/secret-key/1.1/salt/ servicio de claves secretas de WordPress}
 * Puedes cambiar las claves en cualquier momento para invalidar todas las cookies existentes. Esto forzará a todos los usuarios a volver a hacer login.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'fc$baz5tLzi-xOC<6g3sOX&Ph2AXoxz#>:Ct=Y.p a2~ShAO%^rw[|^n)kNP[3gF');
define('SECURE_AUTH_KEY', 'r{kQ)_8n<62H_GP>PE)9c%W3W9x?-aiLTC`.~5o[|!rx<DGx$05M4(|>.#:GGLr9');
define('LOGGED_IN_KEY', 'X&?K6j637+il.?EhTu3wE6;AS&V`ZmZ0A8bEq:o&ljn-VWC=l=[J6aPN~;bsWm(b');
define('NONCE_KEY', 'Mte6;&#5i?BDR=V,%|j[/.g}D%n8]zggY|SfBifu0f.zO=|8}c[<3G6p<gipjvR%');
define('AUTH_SALT', 'ai#,}x)*0.wSd-&*NuFkVR(UWlx)0@**PX.c~B3T Bn}&%5~`[y2>qayZDR&5dqi');
define('SECURE_AUTH_SALT', '&e7%}%fRU@;j OH^po4l`9mo(4*klVS0uYo<cgLNZi?V.7a/MCz&rvqY8y%#FrRP');
define('LOGGED_IN_SALT', 'nGKrP]_uwXZwxas#Qhc3y33U<iB/>#X#_Q7-9r![>Uwn;RqRn6 ;QXZRo5<h_0VX');
define('NONCE_SALT', 'Vm7b5B~hvMgLRs.(d]q7=HIR<H3}@x2PG?Q!9J_oN18!`/AQXl&!={`dA+;+f0nW');

/**#@-*/

/**
 * Prefijo de la base de datos de WordPress.
 *
 * Cambia el prefijo si deseas instalar multiples blogs en una sola base de datos.
 * Emplea solo números, letras y guión bajo.
 */
$table_prefix  = 'wp_ad';


/**
 * Para desarrolladores: modo debug de WordPress.
 *
 * Cambia esto a true para activar la muestra de avisos durante el desarrollo.
 * Se recomienda encarecidamente a los desarrolladores de temas y plugins que usen WP_DEBUG
 * en sus entornos de desarrollo.
 */
define('WP_DEBUG', false);

/* ¡Eso es todo, deja de editar! Feliz blogging */

/** WordPress absolute path to the Wordpress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');

