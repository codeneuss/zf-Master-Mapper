<?php

/**
 * @author Volkmar Eigler <volkmar.eigler@posteo.de>
 * @copyright (c) 2012 Volkmar Eigler
 * @license MIT
 * @link https://github.com/veigler/zf-Master-Mapper
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
class Master_Model {
  
  protected $_modelName = array();
  
  public function __construct(array $options = null) { 
    $className = get_class($this);
    $classNameArr = explode('_',$className);
    $this->_modelName = array_pop($classNameArr);
    if(is_array($options)){
      $this->setOptions($options);
    }
  }
  
  public function __set( $name, $value) {
    $method = 'set' . $name;
    if(key_exists('_'.$name,get_class_vars(get_class($this)))){
      $this->$method($value);
      return $this;
    }
  }
  
  public function __get($name){
    $method = 'get' . ucfirst($name);
    if(key_exists('_'.$name,get_class_vars(get_class($this)))){
      return $this->$method();
    }
  }
  
  public function __call($name,$argument) {
    $field = '_'.strtolower(substr($name,3));
    $method = substr($name,0,3);
    $vars = get_class_vars(get_class($this));
    $selfvars = get_class_vars(__CLASS__);
    if(false === key_exists($field, $vars) || key_exists($field,$selfvars)) {
      throw new Exception('Unknown Method ' . $field);
    }
    if('set' == $method) {
      $this->$field = $argument[0];
      return $this;
    } elseif('get' == $method) {
      return $this->$field;
    }
  }
  
  
  
  public function setOptions(array $options, $ignoreEmptyValues = true) {
    $reflection = new ReflectionClass($this);
    $vars = $reflection->getDefaultProperties();
    foreach($options as $key => $value) {
      if(trim($value) == '' && $ignoreEmptyValues)
        continue;
      $method = 'set' . ucfirst($key);
      if( key_exists('_'.$key,$vars)) {
        $this->$method(trim($value));
      }
    }
    return $this;
  }
  
  public function save() {
    $mapper = get_class($this).'Mapper';
    $mapper = new $mapper();
    return $mapper->save($this);
  }
  
  public function delete() {
    $mapper = get_class($this).'Mapper';
    $mapper = new $mapper();
    return $mapper->delete($this);
  }
  
  public function __toArray($utf8=false){
    $reflection = new ReflectionClass($this);
    $vars = $reflection->getDefaultProperties();
    $array = array();
    foreach($vars as $key => $value) {
      if($utf8 == true)
        $array[substr($key,1)] = utf8_encode($this->$key);
      else  
        $array[substr($key,1)] = $this->$key;
    }
    return $array;
  }
  
  
}


?>
