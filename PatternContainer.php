<?php
/**
 *
 *
 *
 *
 */
class PatternContainer {
  public $no_wildcards;
  public $wildcards;

  function PatternContainer() {
    $this->no_wildcards = array();
    $this->wildcards = array();
  }
  
  public function addPattern($input) {
    $length = sizeof(explode(',', $input)) - 1;
    $pattern = $this->convertToRegExPattern($input);
    if (strpos($input, '*') !== false) {
      $this->addToArray($this->wildcards, $pattern, $index);
    } else {
      $this->addToArray($this->no_wildcards, $pattern, $index);
    }
  }
  
  private function convertToRegExPattern($input) {
    $split = explode(',', $input);
    $pattern = '/^';
    
    foreach ($split AS $section) {
      if ($section == ',') {
        $pattern += '/';
      } elseif ($section == '*') {
        $pattern += '[a-zA-Z0-9]+';
      } else {
        $pattern += $section;
      }
    }
    
    $pattern += '$/';
    return $pattern;
  }
  
  private function addToArray(&$array, $pattern, $index) {
    if (!array_key_exists($index, $array)) {
      $array[$index] = array();
    }
    $array[$index][] = $pattern;
  }
}