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



class Master_Mapper {

  protected $_dbTable;
  protected $_modelClass;
  protected $_modelName;
  protected $_modelFields;

  public function __construct() {
    $mapperClass = get_class($this);
    $mapperClassArr = explode('_',$mapperClass);
    $mapperName = array_pop($mapperClassArr);
    $this->_modelClass = substr($mapperClass,0,strlen($mapperClass)-6);
    $this->_modelName =  substr($mapperName,0,strlen($mapperName)-6);
    $reflection = new ReflectionClass($this->_modelClass);
    $child_vars = $reflection->getDefaultProperties();
    $reflection = new ReflectionClass(__CLASS__);
    $parent_vars = $reflection->getDefaultProperties();
    foreach($child_vars as $key=>$val) {
      if(!key_exists($key, $parent_vars)) {
        $this->_modelFields[] = substr($key,1);
      }
    }
  }

  public function _getModelFields() {
    return $this->_modelFields;
  }

  public function __call( $name, $arguments) {
    $ccarray =  preg_split('/((?:^|[A-Z])[a-z0-9_]+)/',$name,-1,PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    if(method_exists('Master_Mapper','_'.$ccarray[0])) {
      $method = '_'.array_shift( $ccarray );
      return $this->$method($ccarray ,$arguments);
    } else {
      throw new Exception('No matching Method');
      return;
    }
  }

  public function setDbTable($dbTable){
    if(is_string($dbTable)){
      $dbTable = new $dbTable();
    }
    if(!$dbTable instanceof Zend_Db_Table_Abstract) {
      throw new Exception('Ungültiges Table Data Gateway angegeben');
    }
    $this->_dbTable = $dbTable;
    return $this;
  }

  public function getDbTable() {
    if(null === $this->_dbTable){
      $this->setDbTable(Zend_Registry::get('config')->appnamespace.'_Model_DbTable_'.$this->_modelName);
    }
    return $this->_dbTable;
  }

  public function save(&$model) {
    //var_dump($model);
    foreach($this->_getModelFields() as $field){
      $method = 'get'.ucfirst($field);
      if(null !== $model->$method())
        $data[$field] = $model->$method();
     }
    //die('<pre>'.print_r($data,true));
    if((!key_exists('ID',$data)&&!key_exists('id',$data))
        || (key_exists('ID',$data) && $data['ID']<=0)
        || (key_exists('id',$data) && $data['id']<=0)) {
      unset($data['id']);
      unset($data['ID']);
      $this->getDbTable()->getAdapter()->query('SET TRANSACTION ISOLATION LEVEL SNAPSHOT');
      $id = $this->getDbTable()->insert($data);

      $model->setId($id);
      return $id;
    } else {
      $id = (isset($data['id']))?$data['id']:$data['ID'];
      unset($data['id']);
      unset($data['ID']);
      $this->getDbTable()->getAdapter()->query('SET TRANSACTION ISOLATION LEVEL SNAPSHOT');
      $anzahl =  $this->getDbTable()->update($data, array('id = ?' => $id));
      return $anzahl;
    }
  }

  public function delete($model) {

    if(!is_object($this->getDbTable()->find(array('id = ?' => $model->getId())))){
      return true;
    } else {
      if($this->getDbTable()->delete(array('id = ?' => $model->getId()))){
        return true;
      } else {
        return false;
      }
    }

  }


  protected function _fetch($ccarray, $arguments) {
    if('ALL' == strtoupper(array_shift($ccarray))) {
      $resultSet = $this->getDbTable()->fetchAll();
      $dataset = array();
      foreach( $resultSet as $row) {
        $dataset[] = $this->_cleanModel($row);
      }
      return $dataset;
    }
  }

  protected function _by(&$select,&$ccarray,&$arguments)
  {
    $param = array_shift($ccarray);
    if(null === $param){
      return $select;
    }
    if(false === method_exists($this,'_'.strtolower($param))){
      $arg = array_shift($arguments);
      $operator = '=';
      if(is_array($arg)){
        if(false === key_exists('value',$arg)){
          throw new Zend_Exception('_by: Ein Array wurde übergeben aber es fehlt der Key: value');
        }else{
          $value = $arg['value'];
        }
        if(key_exists('operator',$arg)){
          $operator = $arg['operator'];
          if(trim($operator) == 'IN' && is_array($value)){
            $value = "('".implode("','",$value)."')";
            $select->where($param.' IN '.$value);
            return $this->_by($select,$ccarray,$arguments);
          } elseif(trim($operator) == 'IS'){
            $select->where($param . ' IS ' . $value);
            return $this->_by($select,$ccarray,$arguments);
          }
        }
      } else {
        $value = $arg;
      }
      $select->where($param.' '.$operator.' ?',$value);
      return $this->_by($select,$ccarray,$arguments);
    } else {
      $method = '_'.strtolower($param);
      return $this->$method($select,$ccarray,$arguments);
    }
  }


  protected function _find($ccarray, $arguments) {
    $param = strtoupper(array_shift($ccarray));
    $table = $this->getDbTable();
    $select = $table->select();
    if('BY' == $param || 'ALL' == $param) {
      if('ALL' == $param){
        $param2 = array_shift($ccarray);
        if('BY' == strtoupper($param2)){
          $this->_by($select,$ccarray,$arguments);
        }
      } else {
        $this->_by($select,$ccarray,$arguments);
      }

      $arg = array_shift($arguments);
      if($arg <> null && key_exists('order',$arg)){
        if(is_array($arg['order'])){
          foreach($arg['order'] as $order){
            $select->order($order);
          }
        } else {
          $select->order($arg['order']);
        }
      }
      if($arg <> null && key_exists('group',$arg)){
        $select->group($arg['group']);
        if(!key_exists('fields',$arg)){
          $select->from($table,$arg['group']);
        }
      }
      if($arg <> null && key_exists('fields',$arg) && is_array($arg['fields'])){
        $select->from($table,$arg['fields']);
      }
      Zend_Registry::set('lastSQLQuery',$select->__toString());
      switch($param){
        case 'BY':
          $row = $table->fetchRow($select);
          return $this->_cleanModel($row);
        case 'ALL':
          $rowset = $table->fetchAll($select);
          $modelset = array();
          foreach($rowset as $row){
            $modelset[] = $this->_cleanModel($row);
          }
          return $modelset;
      }
    } else {
      throw new Zend_Exception('_find: Parameter BY oder ALL verlangt!');
    }
  }

  public function _count($ccarray,$arguments){
    $field = strtolower(array_shift($ccarray));
    if($field == 'id')
      $field = strtoupper($field);

    $table = $this->getDbTable();
    $select = $table->select();
    $select->from($table,'COUNT('.$field.') as anzahl');

    if(count($ccarray)>0){
      $param = strtoupper(array_shift($ccarray));
      if($param == 'BY'){
        $this->_by($select,$ccarray,$arguments);
      }
    }
    Zend_Registry::set('lastSQLQuery',$select->__toString());

    $row = $table->fetchRow($select);

    return $row['anzahl'];


  }

  protected function _get($ccarray,$arguments){
    $param = strtoupper(array_shift($ccarray));

    $table = $this->getDbTable();
    $select = $table->select();

    switch($param){
      case 'MAX':
        $field = array_shift($ccarray);
        $select->order($field.' DESC')
              ->limit(1);
      break;
      case 'MIN':
        $field = array_shift($ccarray);
        $select->order(strtoupper($field).' ASC')
              ->limit(1);
      break;
    }

    if(count($ccarray)>0){

      $param = strtoupper(array_shift($ccarray));
      if($param == 'BY'){
        $this->_by($select,$ccarray,$arguments);
      }
    }
    Zend_Registry::set('lastSQLQuery',$select->__toString());
    return $this->_cleanModel($table->fetchRow($select));
  }


  protected function _cleanModel($row, $refDataset = null) {
    if(!$row instanceof Zend_Db_Table_Row || $row == null){
      return false;
    }
    $data = new $this->_modelClass();

    foreach($this->_modelFields as $field) {
      $meta = $row->getTable()->info('metadata');
      if(key_exists($field,$meta) && $meta[$field]['DATA_TYPE'] == 'datetime')
        if(is_null($row->$field) || strtotime($row->$field) <= 1 )
          $row->$field = null;
        else
          $row->$field = date(FDATETIME,strtotime($row->$field));

      if(isset($row->$field)) {
        $method = 'set'.ucfirst($field);
        $data->$method($row->$field);
      } else {
        $field = strtoupper($field);
        if(isset($row->$field)) {
          $method = 'set'.ucfirst($field);
          $data->$method($row->$field);
        }
      }
    }
    if(null == $refDataset) {
      return $data;
    }
    foreach($refDataset as $refName=>$refData) {
      $method = 'set'.$refName;
      $data->$method($refData);
    }
    return $data;
  }



  public function __toArray($models,$field=null){
    $return = array();
    if(is_array($models)){
      foreach($models as $model){
        if(!$model instanceof $this->_modelClass)
          continue;
        $ref = new ReflectionClass($this->_modelClass);
        foreach($ref->getDefaultProperties() as $key=>$val){
          if(strtolower($key) == '_modelname')
            continue;
          if($field<>null){
            $method = 'get'.ucfirst($field);
            $return[$model->getId()] = $model->$method();
          } else {
            $method = 'get'.ucfirst(substr($key,1));
            $modelarr[substr($key,1)] = $model->$method();
            $return[$model->getId()] = $modelarr;
          }
        }

      }
    } elseif($models instanceof $this->_modelClass) {
      //var_dump($models);die;
      $ref = new ReflectionClass($this->_modelClass);
      foreach($ref->getDefaultProperties() as $key=>$val){
        if($key == '_modelName')
          continue;
        $method = 'get'.ucfirst(substr($key,1));
        $return[substr($key,1)] = $models->$method();
      }
    }
    return $return;
  }

}
