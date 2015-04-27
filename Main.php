<?php
/* A simple script to pull in data from STDIN.
 * Accepts Patterns first and places them into a PatternContainer
 * Then the script pulls in the paths to check and passes them to the PatternContainer for amtching
 *
 * @author Patrick Dunn <pdunn1327@gmail.com>
 * (c) 2015 Patrick Dunn
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

// pull in all of the paths and then pass them to the pattern container for matching
for ($i = 0; $i < $path_count; $i++) {
  $raw_path = fgets($stream);
  $result = $pattern_container->findMatch($raw_path);
  echo $result, PHP_EOL;
}
