<?php

/* header( 'Content-Type: application/json' );

$user = array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : '';
$pwd = array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : '';

if ( $user !== 'mauro' || $pwd !== '1234' ) {
	header('Status-Code: 403');

	echo json_encode(
		[ 
			'error' => "Usuario y/o password incorrectos", 
		]
	);

	die;
}

header( 'Content-Type: application/json' ); */

// Autenticación con HMAC
/* header( 'Content-Type: application/json' );

if ( 
	!array_key_exists('HTTP_X_HASH', $_SERVER) || 
	!array_key_exists('HTTP_X_TIMESTAMP', $_SERVER) || 
	!array_key_exists('HTTP_X_UID', $_SERVER)  
	) {
		header( 'Status-Code: 403' );
	
		echo json_encode(
			[
				'error' => "No autorizado",
			]
		);
		
		die;
	}

list( $hash, $uid, $timestamp ) = [ $_SERVER['HTTP_X_HASH'], $_SERVER['HTTP_X_UID'], $_SERVER['HTTP_X_TIMESTAMP'] ];
$secret = 'Sh!! No se lo cuentes a nadie!';
$newHash = sha1($uid.$timestamp.$secret);

if ( $newHash !== $hash ) {
	header( 'Status-Code: 403' );
	
		echo json_encode(
			[
				'error' => "No autorizado. Hash esperado: $newHash, hash recibido: $hash",
			]
		);
		
		die;
} */

// Autenticación con Access Tokens
header( 'Content-Type: application/json' );

if ( !array_key_exists( 'HTTP_X_TOKEN', $_SERVER ) ) {

	die;
}

$url = 'https://'.$_SERVER['HTTP_HOST'].'/auth';

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_HTTPHEADER, [
	"X-Token: {$_SERVER['HTTP_X_TOKEN']}",
]);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
$ret = curl_exec( $ch );

if ( curl_errno($ch) != 0 ) {
	die ( curl_error($ch) );
}

if ( $ret !== 'true' ) {
	http_response_code( 403 );

	die;
}

// Definimos los recursos disponibles
$allowedResourceTypes = [
	'books',
	'authors',
	'genres',
];

// Validamos que el recurso este disponible
$resourceType = $_GET['resource_type'];
if ( !in_array( $resourceType, $allowedResourceTypes ) ) {
	http_response_code( 400 );
	echo json_encode(
		[
			'error' => "$resourceType is un unkown",
		]
	);
	
	die;
}

// Definimos los recursos
$books = [
	1 => [
		'titulo' => 'Lo que el viento se llevo',
		'id_autor' => 2,
		'id_genero' => 2,
	],
	2 => [
		'titulo' => 'La Iliada',
		'id_autor' => 1,
		'id_genero' => 1,
	],
	3 => [
		'titulo' => 'La Odisea',
		'id_autor' => 1,
		'id_genero' => 1,
	],
];

// Generamos la respuesta asumiendo que el pedido es correcto
$resourceId = array_key_exists('resource_id', $_GET ) ? $_GET['resource_id'] : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ( strtoupper( $method ) ) {
	case 'GET':
		if ( "books" !== $resourceType ) {
			http_response_code( 404 );

			echo json_encode(
				[
					'error' => $resourceType.' not yet implemented :(',
				]
			);

			die;
		}
        // No especifiquen ningun recurso
		if ( !empty( $resourceId ) ) {
            // Si especifican un recurso en especifico
			if ( array_key_exists( $resourceId, $books ) ) {
				echo json_encode(
					$books[ $resourceId ]
				);
			} else {
				http_response_code( 404 );

				echo json_encode(
					[
						'error' => 'Book '.$resourceId.' not found :(',
					]
				);
			}
		} else {
			echo json_encode(
				$books
			);
		}

		die;
		
		break;
    case 'POST':
        // Tomamos la entrada cruda
		$json = file_get_contents( 'php://input' );

        // Transformamos el json recibido a un nuevo elemento del arreglo 
		$books[] = json_decode( $json );

        // Emitimos hacia la salida la ultima clave del arreglo de los libros
		echo array_keys($books)[count($books)-1];
		break;
    case 'PUT':
        // Validamos que el recurso buscado exista
		if ( !empty($resourceId) && array_key_exists( $resourceId, $books ) ) {
            // Tomamos la entrada cruda
			$json = file_get_contents( 'php://input' );
            
            // Transformamos el json recibido a un nuevo elemento del arreglo
			$books[ $resourceId ] = json_decode( $json, true );

			echo $resourceId;
		}
		break;
    case 'DELETE':
        // Validamos que el recurso exista
		if ( !empty($resourceId) && array_key_exists( $resourceId, $books ) ) {
            // Eliminamos el recurso
			unset( $books[ $resourceId ] );
		}
		break;
	default:
		http_response_code( 404 );

		echo json_encode(
			[
				'error' => $method.' not yet implemented :(',
			]
		);

		break;
}
