<?php
/**
 * Builds build/kontoros-company-lookup-<version>.zip with the files that ship,
 * skipping everything listed in .distignore. Usage: php bin/build-zip.php
 */

$root = dirname( __DIR__ );
$slug = 'kontoros-company-lookup';

preg_match( '/^ \* Version:\s*(\S+)/m', file_get_contents( "$root/$slug.php" ), $m );
$version = $m[1] ?? 'dev';

$ignore = array();
foreach ( file( "$root/.distignore", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) as $line ) {
	$line = trim( $line );
	if ( '' !== $line && '#' !== $line[0] ) {
		$ignore[] = $line;
	}
}
$ignore[] = '/build';

$skip = function ( $rel ) use ( $ignore ) {
	foreach ( $ignore as $pattern ) {
		if ( '/' === $pattern[0] ) {
			$p = substr( $pattern, 1 );
			if ( $rel === $p || 0 === strpos( $rel, $p . '/' ) ) {
				return true;
			}
		} elseif ( basename( $rel ) === $pattern || fnmatch( $pattern, basename( $rel ) ) ) {
			return true;
		}
	}
	return false;
};

@mkdir( "$root/build" );
$file = "$root/build/$slug-$version.zip";
@unlink( $file );
$zip = new ZipArchive();
$zip->open( $file, ZipArchive::CREATE );
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
$n  = 0;
foreach ( $it as $f ) {
	$rel = ltrim( substr( $f->getPathname(), strlen( $root ) ), '/' );
	if ( $skip( $rel ) ) {
		continue;
	}
	$zip->addFile( $f->getPathname(), "$slug/$rel" );
	$n++;
}
$zip->close();
echo "$file ($n files)\n";
