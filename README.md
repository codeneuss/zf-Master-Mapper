ZF Master Mapper
========

Diese Library / Masterklassen sind für die Verwendung mit Zend Framework 1.11 und höher (nicht ZF2) ausgelegt.
Der Mapper ermöglicht ein schnelles Prototyping und übersichtliche Datenbankabfragen direkt im Controller.
Die Bibliothek besteht aus 3 Klassen. Die eine repräsentiert ein Model in Zend Framework und die andere den Mapper.

Der Mapper zeichnet sich besonders durch die Funktionen `findBy`, `findAllBy`, `delete`, `save` aus. Ein Teil der Funktionen beherscht das Model ebenso. Dabei wird hierbei nur eine *Pipeline* zum Mapper erstellt.

========
### Vorraussetzungen ###
+ Für die Klassen wir eine PHP Version >= PHP 5.3.3 erwartet. Die Klasse wurde nicht vollständig mit aktuellen Versionen gestet!
+ Zend Framework 1.11 und höher.

### Installation ###
Für Die Installation muss der Ordner `/Master` in das Library Verzeichnis der Zend Framework Instanz kopiert werden.
In der `application.ini` muss folgender Eintrag ergänzt werden:
```
autoloaderNamespaces[] = "Master"
```
Sofern das Autoloading anders als in den Referenzdokumenten von framework.zend.com geregelt wird müssen die Klassen auf die entsprechende Weise eingebunden werden.

### Anwendung ###

Für jedes Model muss eine Klasse definiert werden. Üblicherweise liegen die im Verzeichnis `application/models` und haben den Namensaufbau *Application*\_Model\_*ModelName*. Die Felder, auf welche zugegriffen werden soll, müssen als protected-Variable definiert werden und einen führenden Unterstrich enthalten.
```php
#application/model/Test.php

class Application_Model_Test extends Master_Model
{
  protected $_id; //Jedes Model sollte eine ID enthalten!
  protected $_created; //Bespielfeld
  protected $_modified; //Beispielfeld
}
```
Um mit der Datenbank agieren zu können müssen folgende Klassen vohanden sein:
+ Mapper
```php
#application/model/TestMapper.php

class Application_Model_TestMapper extends Master_Mapper
{
}
```
+ Table
```php
#application/model/DbTable/Test.php

class Application_Model_DbTable_Test extends Master_Table
{
  protected $_name = 'tbl_Test';
  protected $_adapter = 'default';
}
```

Nun Kann auf die Mapperfunktionen im Controller zugegriffen werden:
```php

$model = new Application_Model_Test(); //Neuer Datensatz
$model->created = time(); //time = 12345654
$model->save(); //INSERT INTO tbl_Test (created) VALUES (12345654)

$mapper = new Application_Model_TestMapper();
$model = $mapper->findById(1); //SELECT * FROM tbl_Test WHERE (id = 1)

$array = $model->__toArray();

$created = $model->created; //Inhalt von created

$model->modified = time(); //time = 13234545
$model->save(); //UPDATE tbl_Test SET modified = 13234545

$mapper->delete($model); //DELETE FROM tbl_Test WHERE (id = 1)

```

## Methoden ##
### findBy ###

