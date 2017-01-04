<?php
require_once ('../config.php');

$GDsupported = false;

if (extension_loaded ( 'gd' )) {
	$GDsupported = true;
}

try {

	$image_id = null;
	$image = null;

	if (isset ( $_GET ['ID'] ))
		$image_id = intval ( filter_var ( $_GET ['ID'], FILTER_SANITIZE_NUMBER_INT ) );
	else
		throw new Exception ();

	if (isset ( $_GET ['maxWidth'] ))
		$maxThumbWidth = intval ( filter_var ( $_GET ['maxWidth'], FILTER_SANITIZE_NUMBER_INT ) );
	else
		$maxThumbWidth = cMaxThumbWidth;

	if (! is_int ( $image_id ))
		throw new Exception ();

	$db = new PDO ( cDbConnSYNOstr, cDbConnSYNOuser, cDbConnSYNOpwd ) or die ( 'Could not connect to DB!' );

	$db->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

	$db->beginTransaction ();
	$stream = $db->pgsqlLOBOpen ( $image_id, 'r' );
	if (! $stream)
		throw new Exception ( 'Read image error!' );

	header ( "Content-type: image/jpeg" );

	$image = stream_get_contents ( $stream );

	if ($GDsupported) {
		$img = imagecreatefromstring ( $image );
		if ($img) {
			$width = imagesx ( $img );
			$height = imagesy ( $img );

			if ($width > ($maxThumbWidth + 1)) {
				$new_width = $maxThumbWidth;
				$new_height = floor ( $height * ($maxThumbWidth / $width) );

				$tmp_img = imagecreatetruecolor ( $new_width, $new_height );

				imagecopyresampled ( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

				// save thumbnail into a file
				if (! imagejpeg ( $tmp_img )) {
					echo ($image);
				}
				;
				imagedestroy ( $tmp_img );
			} else {
				echo ($image);
			}
			imagedestroy ( $img );
			exit ();
		}
	}

	echo ($image);
	exit ();
} catch ( Exception $e ) {
	header ( $_SERVER ['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500 );
	die ( 'Error: ' . $e->getMessage () );
}