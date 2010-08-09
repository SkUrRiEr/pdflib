<?php

function normalise_path($path) {
	$path = preg_replace("://+:", "/", $path);

	$npath = $path;
	$path = "";
	while( $npath != $path ) {
		$path = $npath;

		$npath = preg_replace(":(^|/)\./:", "$1", $npath);
	}

	$path = "";
	while( $npath != $path ) {
		$path = $npath;

		$npath = preg_replace(":(^|/)[^/]+/\.\./:", "$1", $npath);
	}

	return $npath;
}

function file_which($path) {
	$paths = explode(PATH_SEPARATOR, get_include_path());

	if( count($paths) == 0 ) {
		if( file_exists($path) )
			return $path;
		else
			return false;
	}

	foreach($paths as $p) {
		$x = normalise_path($p."/".$path);

		if( file_exists($x) )
			return $x;
	}

	return false;
}

?>
