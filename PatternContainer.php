<?php
/**
 * A data object to contain our patterns and then perform the pattern matching algorithm
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
  
  public function addPattern($input) {
    // trim any leading or trailing whitespace
    trim($input);
    // trim the trailing newline character
    $input = substr($input, 0, -1);
    // save an original copy for use later
    $original = $input;
    
    // split the pattern into sections
    $split_input = explode(',', $input);
    $section_count = sizeof($split_input);
    
    // now convert this into a regular expression pattern we will use later
    $pattern = $this->convertToRegExPattern($split_input);
    
    // should we add this to the wildcard array or the no wildcards array?
    if (strpos($input, '*') !== false) {
      // find which section has the first wildcard, will need this info later for the "best" match
      $first_wild = null;
      for ($i == 0; $i < sizeof($split_input); $i++) {
        if ($split_input[$i] == '*') {
          $first_wild = $i;
          break;
        }
      }
      
      // now insert this pattern as appropriate
      $this->wildcards = $this->addToArray($this->wildcards, $pattern, $original, $section_count, $first_wild);
    } else {
      // no wildcards, so simply place it
      $this->no_wildcards = $this->addToArray($this->no_wildcards, $pattern, $original, $section_count, null);
    }
  }
  
  private function convertToRegExPattern($split) {
    $pattern = "/^";
    
    // convert the wildcards as necessary
    foreach ($split AS $section) {
      if ($section == '*') {
        $pattern .= "[a-zA-Z0-9]+";
      } else {
        $pattern .= $section;
      }
      // use this (hopefully) unique pattern as a section delineator for later
      $pattern .= '::';
    }
    
    // now finish up the pattern (and remove the trailing delineator) and then return it
    $pattern = substr($pattern, 0, -2) . "$/";
    return $pattern;
  }
  
  private function addToArray($array, $pattern, $original, $section_index, $first_wild) {
    // collect a count of wildcards
    $wild_count = substr_count($original, '*');
    
    // if the index pertaining to the count of sections does not exist, add it
    if (!array_key_exists($section_index, $array)) {
      $array[$section_index] = array();
      
      // and if we are dealing with the wildcards, also initialize the wildcard count sub-array as well
      if ($wild_count > 0) {
        $array[$section_index][$wild_count] = array();
      }
    }
    
    // if we're dealing with wildcards, then add the pattern into the sub-array related to the first wildcard's location
    if ($wild_count > 0) {
      $array[$section_index][$wild_count][$first_wild][] = [$pattern, $original];
    } else {
      // or this is really the simple, no wildcard array so just add the pattern without complication
      $array[$section_index][] = [$pattern, $original];
    }
    return $array;
  }
  
  public function findMatch($input) {
    if (empty($input)) {
      return 'NO MATCH';
    }
    
    // remove the likely trailing new line character
    $input = substr($input, 0, -1);
    
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
    $input = str_replace('/', '::', $input);
    
    // might be a more efficient way of learning this, but this is more accurate
    $section_count = sizeof(explode('::', $input));
    
    // match on no wildcards first, if possible
    if (array_key_exists($section_count, $this->no_wildcards)) {
      $patterns = $this->no_wildcards[$section_count];
      // check all patterns of this amount of sections
      foreach ($patterns AS $pattern) {
        // does it match? if so simply return it, it's better than searching wildcards
        if (preg_match($pattern[0], $input) === 1) {
          return $pattern[1];
        }
      }
    }
    
    // try matching with wildcards now
    $matches = array();
    $best_num_wildcards = 9999999;
    
    // redo this to only look at patterns with exact amount of sections
    foreach ($this->wildcards AS $section_count_index => $patterns_by_wilds) {
      
      // if there's not enough sections, skip ahead, no need to look at these
      if ($section_count_index < $section_count) continue;
      
      // now look at how many wildcards appeared
      foreach ($patterns_by_wilds AS $patterns_by_wild_section) {
        
        // so now we know how many wildcards are in these patterns, let's look at the ones
        // where the wildcards first appear the latest in the pattern
        $in_order_wild_sections = array_reverse(array_keys($patterns_by_wild_section));
        for ($i = 0; $i < sizeof($in_order_wild_sections); $i++) {
          $patterns = $patterns_by_wild_section[$in_order_wild_sections[$i]];
          
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
            break;
          }
        }
        // shortcut out if we saw any matches
        if (!empty($matches)) {
          break;
        }
      }
      // shortcut out if we saw any matches
      if (!empty($matches)) {
        break;
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