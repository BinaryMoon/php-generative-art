<style>
body { margin: 0; padding: 0; }
img { border: 1em solid white; max-width: 100%; }
.images { display: flex; flex-wrap: wrap; }
.image { flex-grow: 1; position: relative; }
span { position: absolute; top: 1em; left: 1em; padding: 2px 10px; background: white; font-size: 12px; font-weight: bold; }
</style>
<?php

// Just in case!
error_reporting( E_ALL );

/**
 * Width and height of the output image.
 * For best results this should be a multiple of the source layer images.
 * In this case I'm using layers that are 32 x 32.
 */
define( 'WIDTH', 32 * 5 );
define( 'HEIGHT', 32 * 5 );
/**
 * The number of images to generate.
 */
define( 'ITEM_COUNT', 20 );
/**
 * Should we overwrite existing saved images?
 */
define( 'KEEP_EXISTING', false );

$testGD = get_extension_funcs( 'gd' );
if ( ! $testGD ) {
	echo 'GD not installed.';
	die();
}

generate_images();
display_images();

/**
 * Generate the images.
 * Doesn't really need to be in a function, this just keeps things little more organised.
 */
function generate_images() {

	for( $i = 1; $i <= ITEM_COUNT; $i ++ ) {

		$filename = './generated/' . $i . '.png';

		if ( file_exists( $filename ) && KEEP_EXISTING ) {
			continue;
		}

		$img = imagecreatetruecolor( WIDTH, HEIGHT );

		draw_part( $img, 'skin' );
		draw_part( $img, 'eyes' );
		draw_part( $img, 'mouths' );

		imagepng( $img, $filename );
		imagedestroy( $img );

	}

}

/**
 * Display a list of generated images.
 */
function display_images() {

	$images = glob( './generated/*.png' );

	echo '<div class="images">';

	foreach( $images as $image ) {

		$name = str_replace( array( './generated/', '.png' ), '', $image );
		echo '<div class="image">';
		printf( '<img src="%s" />', $image );
		printf( '<span class="">%s</span>', $name );
		echo '</div>';

	}

	echo '</div>';

}

/**
 * Draw the layers.
 */
function draw_part( $image, $group ) {

	/**
	 * Grab all of the images from the specified folder.
	 */
	$parts = glob( './images/' . $group . '/*.png' );

	/**
	 * Shuffle all of the parts, We will use the first one in the list.
	 * @var [type]
	 */
	shuffle( $parts );

	/**
	 * Loca the part for the first image.
	 */
	$imagePart = imagecreatefrompng( $parts[0] );

	/**
	 * Copy the layer from the loaded image to the image we are creating.
	 * I am using imagecopyresized so that the pixelart stays sharp.
	 * You can also use imagecopyresampled to get resampling.
	 *
	 * imagesx and imagesy gets the x and y size of the part.
	 * I know these are 32 for my images, but they might be different for yours.
	 */
	imagecopyresized( $image, $imagePart, 0, 0, 0, 0, WIDTH, HEIGHT, imagesx($imagePart), imagesy( $imagePart ) );

}
