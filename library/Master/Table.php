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
 
 
class Master_Table extends Zend_Db_Table_Abstract {
  
  protected $_dependentTables = array();
  protected $_hasMany = array();
  protected $_belongsTo = array();
  
  public function __construct( $config = array()) {
    $appnamespace = Zend_Registry::get('config')->appnamespace;
    foreach($this->_hasMany as $dependentTable) {
      $this->_dependentTables[] = $appnamespace.'_Model_DbTable_'.$dependentTable;
    }
    foreach($this->_belongsTo as $name=>$refTable) {
      $refTable['refTableClass'] = $appnamespace.'_Model_DbTable_'.$refTable['refTableClass'];
      $this->_referenceMap[$name] = $refTable;
    }
    $this->setDefaultAdapter($this->_adapter);
    return parent::__construct( $config);
  }
  
  public function _getReferenceMap() {
    return $this->_referenceMap;
  }
  
  
}


?>
