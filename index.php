<?php

// cargar lo necesario
require 'config.php';

// iniciar variables
$path = 'upload/';
$error = $ok = '';

if( $pass || $super_pass ) session_start();

// declarar funciones
function cmp( $a, $b ){
	if( $a['name'] == $b['name'] ) return 0;
	return ( $a['name'] < $b['name'] ) ? -1 : 1;
}
	
function getSize( $path ){
	if( !is_dir( $path ) ) return filesize( $path );
	else if( $handle = opendir( $path ) ) {
		$size = 0;
		while( false !== ( $file = readdir( $handle ) ) ){
			if( $file != '.' && $file != '..' ){
				$size += filesize( $path . '/' . $file );
				$size += getSize( $path . '/' . $file );
			}
		}
		closedir( $handle );
		return $size;
	}
}

function byteSize($file_size) {
	if( $file_size >= 1073741824 ) $show_filesize = number_format( ( $file_size / 1073741824 ), 2 ) . ' GB';
	else if( $file_size >= 1048576 ) $show_filesize = number_format( ( $file_size / 1048576 ), 2 ) . ' MB';
	else if( $file_size >= 1024 ) $show_filesize = number_format( ( $file_size / 1024 ), 2 ) . ' KB';
	else $show_filesize = $file_size . ' bytes';
	return $show_filesize;
}

function getExtension( $file_name ){
	return end( explode( '.', $file_name ) );
}

function delTree($dir) {
	$files = glob( $dir . '*', GLOB_MARK );
	foreach( $files as $file ){
		if( substr( $file, -1 ) == '/' ) delTree( $file );
		else @unlink( $file );
	}
	if( is_dir( $dir ) ) @rmdir( $dir );
} 

// proteccion
if( !isset( $_GET['dir'] ) ) $_GET['dir'] = $path;
if( !strstr( $_GET['dir'], $path ) || strstr( $_GET['dir'], '..' ) || strstr( $_GET['file'], '..' ) || strstr( $_GET['new_dir'], '..' ) || ( $_GET['file'] == '/' ) || ( $_GET['dir'] == '/' )  ) die( "Operación denegada" );

// ftp protegido
if( isset( $_GET['logout'] ) ){
	$_SESSION['pass'] = false;
	unset( $_SESSION['pass'] );
}

if( $pass || isset( $_GET['login'] ) ){
	
	if( isset( $_POST['pass'] ) ){
		if( in_array( $_POST['pass'], array( $pass, $super_pass ) ) ) $_SESSION['pass'] = $_POST['pass'];
		else $error = '&nbsp;&nbsp;&nbsp;<span style="color:red">Contraseña incorrecta</span>';
	}
	if( !$_SESSION['pass'] ){
		echo '<html><head><title>', $title, '</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head><body><form method="post">Contraseña: <input type="password" name="pass" id="pass"/> <input type="submit" value="Entrar"/>', $error, '</form><script>document.getElementById(\'pass\').focus()</script></body></html>';
		exit();
	}
}

$can_delete = ( ( $super_pass && $_SESSION['pass'] == $super_pass ) || ( $pass && $_SESSION['pass'] == $pass ) || ( !$pass && !$super_pass ) );

// descargar un fichero
if( isset( $_GET['file'] ) ){
	if( !file_exists( $_GET['file'] ) ) die( "El fichero no existe" );
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header( "Content-Disposition: attachment; filename=". basename( $_GET['file'] ) );
	header( "Content-Description: File Transfer");
	readfile( $_GET['file'] );
	exit();
}

// entrar en una carpeta
$path = $_GET['dir'];
while( strpos( $path, '\\' ) ) $path = stripslashes( $path );
if( !file_exists( $path ) ) die( "El directorio no existe" );

// subir fichero
if( isset( $_FILES['upload'] ) ){
	$total = count( $_FILES['upload'] );
	for( $i = 0; $i < $total; $i++ ){
		if( $_FILES['upload']['name'][$i] ){
			if( $file_type && !in_array( getExtension( $_FILES['upload']['name'][$i] ), $file_type ) ) $error .= $_FILES['upload']['name'][$i] . ", tipo fichero no permitido<br/>";
			else if( $file_size && $file_size < filesize( $_FILES['upload']['tmp_name'][$i] )  ) $error .= $_FILES['upload']['name'][$i] . ", el fichero supera el tamaño permitido<br/>";
			else if( !move_uploaded_file( $_FILES['upload']['tmp_name'][$i], $path . $_FILES['upload']['name'][$i] ) ) $error .= $_FILES['upload']['name'][$i] . ", no se ha podido subir el fichero<br/>";
			else $ok .= $_FILES['upload']['name'][$i] . ", se ha subido correctamente<br/>";
		}
	}
}

// eliminar ficheros
if( $can_delete && isset( $_POST['delete'] ) ){
	foreach( $_POST['delete'] as $file ){
		$file = stripslashes( $file );
		if( file_exists( $file ) && !strstr( $file, '..' ) ) delTree( $file );
	}
}

// crear nueva carpeta
if( isset( $_GET['new_dir'] ) ){
	$_GET['new_dir'] = stripslashes( strip_tags( $_GET['new_dir'] ) );
	if( !file_exists( $path . $_GET['new_dir'] ) ) mkdir( $path . $_GET['new_dir'] );
}

// listar carpetas y ficheros
$folders = $files = array();
$dir = opendir( $path );
while( $file = readdir( $dir ) ){
	if( $file != '.' && $file != '..' ){
		if( is_dir( $path . $file ) ){
			$folders[] = array(
				'name' => $file,
				'ico' => 'dir',
				'size' => byteSize( getSize( $path . $file ) ),
				'date' => filectime( $path . $file )
				);
		}else{
			$files[] = array(
				'name' => $file,
				'ico' => 'file',
				'size' => byteSize( getSize( $path . $file ) ),
				'date' => filectime( $path . $file )
				);
		}	
	}
}
closedir( $dir );

// para ordenar primero las carpetas y luego los ficheros
usort( $folders, 'cmp' );
usort( $files, 'cmp' );
$files = array_merge( $folders, $files );

echo '<html><head><title>', $title, '</title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/></head><body>';
if( isset( $_SESSION['pass'] ) ) echo '<a href="?logout" class="lnk_logout">Logout</a><br/><br/>';
else if( $super_pass ) echo '<a href="?login">Login</a><br/><br/>';

?>

<link rel="stylesheet" href="main.css" type="text/css" media="all" /> 
<script type="text/javascript" src="general.js"></script> 
<script type="text/javascript">
var BASE_URL = '<?=addslashes( $_SERVER['SCRIPT_NAME'] . '?dir=' . $path )?>';
</script>
<?php

echo '<a href="', $_SERVER['SCRIPT_NAME'], '"><h1>', $title, '</h1></a>';

if( $error ) echo '<span style="color:red">', $error, '</span>';
if( $ok ) echo '<span style="color:green">', $ok, '</span>';

// mostrar formulario upload
echo '<form action="', addslashes( $_SERVER['SCRIPT_NAME'] . '?dir=' . $path ), '" method="post" enctype="multipart/form-data">
<div style="float:left;width:250px" id="uploads">
<input name="upload[]" type="file"/>
</div>
<div style="padding-left:15px;float:left">
<input type="submit" value="Subir fichero"/>&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:;" onclick="newDir()">Nueva carpeta</a>
</div>
<br style="clear:both"/><br/>
<a href="javascript:addUpload()">Subir más ficheros</a>
<br/>
</form>';
if( $file_type ) echo 'Ficheros permitidos: ', implode( ', ', $file_type ), '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
if( $file_size ) echo 'Tamaño máximo: ', byteSize( $file_size );
if( $file_type || $file_size ) echo '<br/><br/><br/>';

// constuir arbol
if( $paths = explode( '/', $path ) ){
	$path_temp = '';
	foreach( $paths as $path_ ){
		if( $path_ ){
			$path_temp .= $path_ . '/';
			echo '<a href="?dir=', $path_temp , '">', $path_ , '</a> &nbsp;/&nbsp; ';
		}
	}
}

// mostar carpetas y ficheros
if( $files ){
	echo '<form action="', addslashes( $_SERVER['SCRIPT_NAME'] . '?dir=' . $path ), '" method="post" onsubmit="if( !confirm(\'¿estas seguro?\') ){return false}"><table border="1" cellspacing="0" cellpadding="5"><tr>';
	if( $can_delete ) echo '<td><input type="checkbox" onclick="selectAll()"/></td>';
	echo '<td width="350">Archivos</td><td width="80">Tamaño</td><td>Fecha</td></tr>';
	foreach( $files as $file ){
		$url = ( $file['ico'] == 'dir' ) ? '?dir=' . $path . $file['name'] . '/' : '?file=' . $path . $file['name'];
		echo '<tr>';
		if( $can_delete ) echo '<td><input type="checkbox" name="delete[]" value="' . $path . $file['name'] . '"/></td>';
		echo '<td>';
		if( $file['ico'] == 'file' ) echo '<a href="http://qrcode.es/generador/qr_img.php?d=', urlencode( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . $url ) , '" target="_blank" style="float:right">QR</a>';
		echo '<a href="', $url , '" class="', $file['ico'], '">', $file['name'] , '</a></td>';
		echo '<td>', $file['size'], '</td>';
		echo '<td>', date( 'd/m/Y H:i', $file['date'] ), '</td></tr>';
	}
	echo '</table>';	
	if( $can_delete ) echo '<input type="submit" value="Eliminar seleccionados"></form>';
	
}else echo '<p>No hay ficheros</p>';
echo '</body></html>';

?>