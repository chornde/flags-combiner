<?php



// settings

$files = glob('single/*.png');
$exportdir = 'side-by-side';
$targetsize = 90;
$overlap = false;



// internal

function pre($thing){
	echo '<pre>', print_r($thing,1), '</pre>';
};



// gather original dimensions

$resources = array_reduce($files, function($resources, $file){
	$name = substr(basename($file), 0, 2);
	$src = imagecreatefrompng($file);
	$size = getimagesize($file);
	$resources[] = [
		'src' => $src,
		'name' => $name,
		'w' => $size[0],
		'h' => $size[1],
		'x' => 0,
		'y' => 0,
	];
	return $resources;
});


// merge

foreach($resources as $lft){

	foreach($resources as $rgt){

		// short-circuit: do not change same picture to prevent distortion

		if($lft['name'] === $rgt['name']){
			$dest = $lft['src'];
		}

		// pictures below the optimum size end up next to each other

		elseif($lft['w'] + $rgt['w'] < $targetsize){
			$dest = imagecreatetruecolor($lft['w'] + $rgt['w'], $lft['h']);
			imagecopy($dest, $lft['src'], 0, 0, 0, 0,  $lft['w'], $lft['h']);
			imagecopy($dest, $rgt['src'], $lft['w'], 0, 0, 0, $rgt['w'], $rgt['h']);
		}

		// always align the right picture on the right side in the background; use left pircure as overlay
		// left picture may fill up to the right one, but will not fall below the middle, except for the original size is lower

		else {
			$dest = imagecreatetruecolor($targetsize, $lft['h']);
			$rgt['x'] = $targetsize - $rgt['w'];
			$half = floor($targetsize / 2);
			$lft['w'] = min(max($rgt['x'], $half), $lft['w']);
			imagecopy($dest, $rgt['src'], $rgt['x'], $rgt['y'], 0, 0, $rgt['w'], $rgt['h']);
			imagecopy($dest, $lft['src'], 0, 0, 0, 0, $lft['w'], $lft['h']);
		}

		// store image

		$path = sprintf('%s/%s%s.png', $exportdir, $lft['name'], $rgt['name']);
		imagepng($dest, $path, 9);

		// make it overlap on a twisted angle

		if($overlap){
			$m = [];
			$xmax = 20;
			$ymax = $left['lh'];
			$offset = floor($xmax / 2) * -1;
			foreach(range(0, $ymax) as $y){
				foreach(range(0, $xmax) as $x){
					$m[$y][$x] = $x * ($ymax / $xmax) < $y;
					if($x * ($ymax / $xmax) < $y){
						imagecopy($dest, $left['src'],  $left['lw'] + $x + $offset,  $y,  $left['lw'] + $x + $offset,  $y,  1,1);
					}
					else {
						imagecopy($dest, $right['src'],  $left['lw'] + $x + $offset,  $y,  $right['rx'] + $x + $offset,  $y,  1,1);
					}
				}
			}
		}
	}

}

exit('done');
