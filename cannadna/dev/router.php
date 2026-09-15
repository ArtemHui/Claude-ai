<?php
// Dev-server router: serve real files, hand everything else to WordPress.
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = __DIR__ . '/wordpress' . $path;

if ( $path !== '/' && file_exists( $file ) && ! is_dir( $file ) ) {
	return false;
}

if ( is_dir( $file ) && file_exists( rtrim( $file, '/' ) . '/index.php' ) ) {
	require rtrim( $file, '/' ) . '/index.php';
	return true;
}

require __DIR__ . '/wordpress/index.php';
