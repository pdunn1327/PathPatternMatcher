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
    trim($input);
    $input = substr($input, 0, -1);
    
    $split_input = explode(',', $input);
    $pattern = $this->convertToRegExPattern($split_input);
    $length = sizeof($split_input);
    
    if (strpos($input, '*') !== false) {
      $this->wildcards = $this->addToArray($this->wildcards, $pattern, $length);
    } else {
      $this->no_wildcards = $this->addToArray($this->no_wildcards, $pattern, $length);
    }
  }
  
  private function convertToRegExPattern($split) {
    $pattern = "/^";
    
    foreach ($split AS $section) {
      if ($section == '*') {
        $pattern .= "[a-zA-Z0-9]+";
      } else {
        $pattern .= $section;
      }
      $pattern .= '/';
    }
    
    $pattern = substr($pattern, 0, -1) . "$/";
    return $pattern;
  }
  
  private function addToArray($array, $pattern, $index) {
    if (!array_key_exists($index, $array)) {
      $array[$index] = array();
    }
    $array[$index][] = $pattern;
    
    return $array;
  }
}