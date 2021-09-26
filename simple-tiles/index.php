
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
 * Grid dimensions.
 */
define( 'GRID_WIDTH', 20 );
define( 'GRID_HEIGHT', 20 );
/**
 * The size of each individual tile in the tileset.
 */
define( 'TILE_SIZE', 16 );
/**
 * How much we want to increase the image size by.
 * Keep it to whole numbers to keep things looking crisp.
 */
define( 'MULT', 2 );
/**
 * Width and height of the output image.
 * For best results this should be a multiple of the source layer images.
 */
define( 'WIDTH', GRID_WIDTH * TILE_SIZE * MULT );
define( 'HEIGHT', GRID_HEIGHT * TILE_SIZE * MULT );
/**
 * The number of images to generate.
 */
define( 'ITEM_COUNT', 3 );
/**
 * Should we overwrite existing saved images?
 */
define( 'KEEP_EXISTING', false );
/**
 * Tile types.
 */
define( 'TILE_DEFAULT', 0 );
define( 'TILE_ROAD', 1 );
define( 'TILE_PARK', 2 );

/**
 * Test for GD.
 */
$testGD = get_extension_funcs( 'gd' );
if ( ! $testGD ) {
	echo 'GD not installed.';
	die();
}

/**
 * The world array.
 */
$world = array();
/**
 * The tiles we will be drawing.
 */
$tile_image = imagecreatefrompng( './tiles.png' );


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

		generate_world();
		draw_world( $img );

		imagepng( $img, $filename );
		imagedestroy( $img );

	}

}


function generate_world() {

	global $world;

	/**
	 * Reset the world.
	 * We will call this function once for each image so we must be sure to reset it.
	 */
	$world = array();

	/**
	 * You will notice the world is saved as an array of rows, which then contains columns.
	 * This is common in games, but may not seem logical.
	 *
	 * This means the array is stored as $world[$y][$x] rather than x,y which
	 * seems more logical at first glance.
	 */
	for ( $y = 0; $y < GRID_HEIGHT; $y ++ ) {
		$world[$y] = array_fill( 0, GRID_WIDTH, TILE_DEFAULT );
	}

	/**
	 * Create roads.
	 * We work out how many roads to add by looking at how many tiles there are
	 * and multiplying by a value to calculate a proportion of the world.
	 * We use a grid  multiplier so that there is always space between the roads
	 * to place buildings.
	 */
	$directions = array( 'ns', 'ew' );
	$grid = 4; // Maybe this could be random?
	$road_count = ( GRID_WIDTH * GRID_HEIGHT ) * 0.03;	// Tweak the numbers until they feel good.

	/**
	 * Note there's no error checking. If c is < 1 this loop will do nothing,
	 * but since it's not public this doesn't matter. I can tweak the multiplier
	 * until it works as intended.
	 */
	for ( $c = 0; $c < $road_count; $c ++ ) {

		/**
		 * We could just use rand here, but I might want to add additional
		 * directions for diagonal roads, or some other shape so using an array
		 * of values keeps this part neat.
		 */
		shuffle( $directions );

		/**
		 * The length of the road.
		 */
		$length = rand( 6, 12 );

		/**
		 * The start and end positions for the road.
		 *
		 * Note that dividing the possible values by grid and then multiplying
		 * then by grid keeps the roads on the grid spacing.
		 */
		$startX = rand( 0, round( GRID_WIDTH / $grid ) ) * $grid;
		$startY = rand( 0, round( GRID_HEIGHT / $grid ) ) * $grid;
		$endX = $startX;
		$endY = $startY;

		/**
		 * Change the end positions of the road lengths.
		 */
		if ( 'ns' === $directions[0] ) {
			$endY = min( $endY + $length, GRID_HEIGHT );
		}
		if ( 'ew' === $directions[0] ) {
			$endX = min( $endX + $length, GRID_WIDTH );
		}

		for ( $y = $startY; $y <= $endY; $y ++ ) {
			for ( $x = $startX; $x <= $endX; $x ++ ) {
				$world[$y][$x] = TILE_ROAD;
			}
		}

	}


	/**
	 * Add some parks.
	 */
	$park_count = ( GRID_WIDTH * GRID_HEIGHT ) * 0.03;	// Tweak the numbers until they feel good.
	$grid = rand( 2, 4 );

	for( $c = 0; $c < $park_count; $c++ ) {

		/**
		 * Calculate coordinates for some rectanglular parks.
		 * There's no checks to see if parks (or roads for that matter) are
		 * positioned on top of each other. It doesn't really matter, that's
		 * part of the randomness/ organic nature of the generative process.
		 */
		$startX = rand( 0, round( GRID_WIDTH / $grid ) ) * $grid;
		$startY = rand( 0, round( GRID_HEIGHT / $grid ) ) * $grid;
		$endX = $startX + rand( 2, 4 );
		$endY = $startY + rand( 2, 4 );

		for ( $y = $startY; $y <= $endY; $y ++ ) {
			for ( $x = $startX; $x <= $endX; $x ++ ) {
				$world[$y][$x] = TILE_PARK;
			}
		}

	}

}

/**
 * Draw everything.
 */
function draw_world( $img ) {

	global $world;

	for( $y = 0; $y < GRID_HEIGHT; $y ++ ) {
		for( $x = 0; $x < GRID_WIDTH; $x ++ ) {

			$tile_x_position = $x * TILE_SIZE * MULT;
			$tile_y_position = $y * TILE_SIZE * MULT;

			draw_tile( $img, $world[$y][$x], $tile_x_position, $tile_y_position );

		}
	}

}


/**
 * Draw individual tiles.
 */
function draw_tile( $img, $tile_type, $x, $y ) {

	global $tile_image;

	/**
	 * Default concrete tile.
	 * The array stores the "x, y" position of the tile image we will be drawing.
	 */
	$tile = array( 0, 0 );

	if ( TILE_DEFAULT === $tile_type ) {
		// Use this to work out whether to draw a building or not.
		if ( rand( 0, 100 ) > 50 ) {
			/**
			 * The x parameters (index 0) is a random number between 0 and 1.
			 * This number could be larger if there were more tiles to pick from.
			 */
			$tile = array( rand( 0, 1 ), 2 );
		}
	}

	if ( TILE_ROAD === $tile_type ) {
		$tile = array( 1, 0 );
	}

	if ( TILE_PARK === $tile_type ) {
		$tile = array( rand( 0, 2 ), 1 );
	}

	imagecopyresized(
		$img, $tile_image,						// images.
		$x, $y,									// position on the draw image.
		$tile[0] * TILE_SIZE,					// X position of the tile to draw.
		$tile[1] * TILE_SIZE,					// Y position of the tile to draw.
		TILE_SIZE * MULT, TILE_SIZE * MULT,		// Dimensions to copy the tile to.
		TILE_SIZE, TILE_SIZE					// Size of the tile on the source image.
	);

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
