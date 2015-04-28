<?php
/**
 * A data object to contain our patterns and then perform the pattern matching algorithm
 *
 * In a worst-case scenario, where we have all wildcard patterns and they're all of the 
 * same length and the wildcards all appear in the exact same position, then we'll be
 * making O(N*M) comparisons where N is the patterns and M is the paths.
 *
 * On average, however, we will have a better distribution of patterns across the data
 * structure and we will look at a much smaller subset of the total number of patterns
 * probably along the lines of O(NlogM), as the data structure uses a trie-type structure to
 * make educated traversal down the possible paths
 *
 * @author Patrick Dunn <pdunn1327@gmail.com>
 * (c) 2015 Patrick Dunn
 */
class PatternContainer {
  public $no_wildcards;
  public $wildcards;

  function PatternContainer() {
    $this->no_wildcards = array();
    $this->wildcards = array();
  }
  
  /*
   * Process the input and add it to the appropriate array as necessary, the array for 
   * patterns with or without wildcards.
   *
   * @param $input string The pattern we want to process and add
   *
   * @return null
   */
  public function addPattern($input) {
    // trim any leading or trailing whitespace
    trim($input);
    
    // if we're passed an empty string then just skip it, include a shortcut in case it's a literal string '0'
    if (empty($input) && $input != '0') {
      return;
    }
    
    // trim the trailing newline character
    $input = substr($input, 0, -1);
    // save an original copy for use later
    $original = $input;
    
    // split the pattern into sections
    $split_input = explode(',', $input);
    
    // now convert this into a regular expression pattern we will use later
    $pattern = $this->convertToRegExPattern($split_input);
    
    // should we add this to the wildcard array or the no wildcards array?
    $has_wildcard = false;
    for ($i = 0; $i < sizeof($split_input); $i++) {
      if ($split_input[$i] == '*') {
        $has_wildcard = true;
        break;
      }
    }
    
    if ($has_wildcard) {
      // this pattern has a wildcard, so place it as appropriate
      $this->wildcards = $this->addToArray($this->wildcards, $pattern, $original);
    } else {
      // no wildcards, so simply place it
      $this->no_wildcards = $this->addToArray($this->no_wildcards, $pattern, $original);
    }
  }
  
  /*
   * Convert the pattern into a regular expression for storage
   *
   * @param array $split The pattern split into an array
   *
   * @return string
   */
  private function convertToRegExPattern($split) {
    $pattern = "/^";
    
    // convert the wildcards as necessary
    for ($i = 0; $i < sizeof($split); $i++) {
      $section = $split[$i];
      if ($section == '*') {
        $pattern .= "[a-zA-Z0-9]+";
      } else {
        $pattern .= $section;
      }
      // use this (hopefully) unique pattern as a section delineator for later
      if ($i + 1 < sizeof($split)) {
        $pattern .= chr(11); // ASCII tab character
      }
    }
    
    // now finish up the pattern (and remove the trailing delineator) and then return it
    $pattern .= "$/";
    return $pattern;
  }
  
  /*
   * A function to add the pattern to the appropriate array (wildcard or no wildcard)
   *
   * @param array  $array    The array (wildcard or no wildcard) that we want to add the pattern to
   * @param string $pattern  The regex converted pattern we want to add
   * @param string $original The original form of the pattern we want to add
   *
   * @return null
   */
  private function addToArray($array, $pattern, $original) {
    // collect a count of wildcards and where the first wildcard appears
    $wild_count = 0;
    $first_wild = null;
    $split_original = explode(',', $original);
    $section_count = sizeof($split_original);
    for ($i = 0; $i < sizeof($split_original); $i++) {
      if ($split_original[$i] == '*') {
        if (!isset($first_wild)) {
          $first_wild = $i;
        }
        $wild_count++;
      }
    }
    
    // if the index pertaining to the count of sections does not exist, add it
    if (!array_key_exists($section_count, $array)) {
      $array[$section_count] = array();
      
      // and if we are dealing with the wildcards, also initialize the wildcard count sub-array as well
      if ($wild_count > 0) {
        $array[$section_count][$wild_count] = array();
        $array[$section_count][$wild_count][$first_wild] = array();
      }
    }
    
    // if we're dealing with wildcards, then add the pattern into the sub-array related to the first wildcard's location
    if ($wild_count > 0) {
      $array[$section_count][$wild_count][$first_wild][] = [$pattern, $original];
    } else {
      // or this is really the simple, no wildcard array so just add the pattern without complication
      $array[$section_count][] = [$pattern, $original];
    }
    
    return $array;
  }
  
  /*
   * Searches the arrays for a pattern that matches the path supplied as input
   * Will return 'NO MATCH' if there is no match found
   *
   * @param string $input The raw path that we want to attempt to match against out patterns
   *
   * @return string
   */
  public function findMatch($input) {
    if (empty($input)) {
      return 'NO MATCH';
    }
    
    // remove the likely trailing new line character
    $input = substr($input, 0, -1);
    
    // if the newly trimmed string is empty, then also return NO MATCH
   if (empty($input) && $input != '0') {
      return 'NO MATCH';
    }
    
    // store the original for use later
    $original = $input;
    
    // trim a leading or trailing '/'
    if (substr($input, 0, 1) == '/') {
      $input = substr($input, 1);
    }
    if (substr($input, -1) == '/') {
      $input = substr($input, 0, -1);
    }
    
    // now replace the slashes with a (hopefully) unique character set we can split on later
    $input = str_replace('/', chr(11), $input); // ASCII tab character
    
    // might be a more efficient way of learning this, but this is more accurate
    $section_count = substr_count($input, chr(11)) + 1; // ASCII tab character
    
    // match on no wildcards first, if possible
    if (array_key_exists($section_count, $this->no_wildcards)) {
      $patterns = $this->no_wildcards[$section_count];
      // check all patterns of this amount of sections
      foreach ($patterns AS $regex_pattern) {
        $pattern = substr(substr($regex_pattern[0], 2), 0, -2); // trim back on the leading /^ and trailing $/
        
        // does it match? if so simply return it, it's better than searching wildcards
        if ($pattern == $input) {
          return $regex_pattern[1];
        }
      }
    }
    
    // try matching with wildcards now
    $matches = array();
    $best_num_wildcards = 9999999;
    
    // get the patterns with the same number of sections
    $patterns_by_wilds = $this->wildcards[$section_count];
    
    if (!empty($patterns_by_wilds)) {
      // now look at how many wildcards appeared
      $wildcard_counts = array_keys($patterns_by_wilds);
      sort($wildcard_counts); // sort it so we look at the lowest number of wildcards first
      
      foreach ($wildcard_counts AS $count) {
        $patterns_by_wildcard_count = $patterns_by_wilds[$count];
        // so now we know how many wildcards are in these patterns, let's look at the ones
        //  where the wildcards first appear the latest in the pattern
        $in_order_wild_sections = array_keys($patterns_by_wildcard_count);
        
        for ($i = sizeof($in_order_wild_sections) - 1; $i >= 0; $i--) {
          $patterns = $patterns_by_wildcard_count[$in_order_wild_sections[$i]];
          
          // now let's actually examine these patterns
          foreach ($patterns AS $pattern) {
          
            // does the pattern actually match the input, given the wildcards?
            if (preg_match($pattern[0], $input) === 1) {
              // it matched, so add it to a list in case we need to compare multiple matches later
              $matches[] = $pattern;
            }
          }
          // shortcut out if we saw any matches
          if (!empty($matches)) {
            break(2); // break out of all the for-loops
          }
        }
      }
    }
    
    // if there was only one match then simply return it
    if (sizeof($matches) == 1) {
      return $matches[0][1];
    }
    
    // we saw multiple matches, so let's dive into those and compare them
    if (sizeof($matches) > 0) {
      return $this->findBestMatch($matches);
    }
    
    // could not find a match, so return the default value
    return 'NO MATCH';
  }
  
  /*
   * A helper function to find the best match from an array of potential candidates
   * Can be called recursively if there's enough of a matching pattern Right To Left
   *
   * @param array $matches An array of patterns that we want to compare to find the best match
   *
   * @return string
   */
  private function findBestMatch($matches) {
    $best_match = null;
    $best_pos = 0;
    
    // let's look at all of our matches
    $dupes = array();
    foreach ($matches AS $match) {
      // find where the first wildcard appears
      $pos = strrpos($match[0], '*');
      
      // if this position is "better" then keep track and use this as the new base
      if ($pos > $best_pos) {
        $best_pos = $pos;
        $best_match = $match[1];
        $dupes = array(); // reset
        $dupes[] = $match; // start filling
      } elseif ($pos == $best_pos) {
        // otherwise, if they're the same, add it in
        $dupes[] = $match;
      }
    }
    
    // if we somehow find the matches are the same, then let's trim then let's go deeper
    if (sizeof($dupes) > 1) {
    
      // if the wildcard is the first character (we've trimmed everything else down)
      // then simply return the first pattern, this is getting ridiculous
      if ($best_pos == 0) {
        return $dupes[0][1]; // just stop
      }
      
      // trim the matches and then try with the new matches
      $new_matches = array();
      foreach ($dupes AS $dupe) {
        $new_matches[] = [substr($dupe[0], 0, $best_pos - 1), $dupe[1]];
      }
      
      // let's get recursive!
      return findBestMatch($new_matches);
    }
    
    // we found just one match, so let's just return it
    return $best_match;
  }
}