<?php
/*
 *
 *
 *
 */

require_once 'PatternContainer.php';

// start by creating the PatternContainer that we'll use when reading from the stream
$pattern_container = new PatternContainer();

// open the stream from STDIN
$stream = fopen('php://stdin', 'r');

// we know the first line should be an integer giving us the number of patterns we'll be consuming
$pattern_count = fgets($stream);

// now let's run through the patterns and store them
for ($i = 0; $i < $pattern_count; $i++) {
  $raw_pattern = fgets($stream);
  $pattern_container->addPattern($raw_pattern);
}

// we should now receive an integer giving us the number of path strings to check against
$path_count = fgets($stream);

for ($i = 0; $i < $path_count; $i++) {
  $raw_path = fgets($stream);
}

var_dump($pattern_container->no_wildcards);
echo PHP_EOL,PHPE_EOL;
var_dump($pattern_container->wildcards);