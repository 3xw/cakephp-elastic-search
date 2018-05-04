<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\ElasticSearch\TypeRegistry;
use Cake\ORM\TableRegistry;
use Cake\Network\Http\Client;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
use Cake\ORM\Entity;

class importTask extends ElasticeSearchConnectTask
{
  public $client = null;

  public $index = null;

  public $type = null;

  public $properties = null;

  public $table = null;

  public function main($index = null, $table = null)
  {
    // set index
    if($index == null)
    {
      $proposal = Inflector::slug(substr(ROOT, strrpos(ROOT, '/')),'_');
      $index = $this->in('Index name for import ?',[$proposal,'dev_'.$proposal],'dev_'.$proposal);
    }
    $index = Inflector::slug($index,'_');

    $this->testIndex($index, $table);
  }

  public function testIndex($index, $table)
  {
    $url = ($this->connection->config()['port'] == 443)? 'https://': 'http://';
    $url .= $this->connection->config()['host'].'/'.$index;

    if($this->client == null) $this->client = new Client();
    $response = $this->client->get($url);
    if($response->code == 200)
    {
      $this->index = $index;
      $this->collectMapping(json_decode($response->body(),true),$table);
    }else
    {
      $this->err('error with index:"'.$index.'"');
      debug(json_decode($response->body(),true));
      return $this->main($index, $table);
    }
  }

  public function collectMapping($json, $table)
  {
    if(empty($json[$this->index]['mappings'])){
      $this->err('no mappings found for index:"'.$index.'"');
      return $this->main(null, $table);
    }

    // collect types
    $types = array_keys($json[$this->index]['mappings']);
    $type = $this->in('In which table/type would you import MySQL data?',$types,$types[0]);
    $this->properties = $json[$this->index]['mappings'][$type]['properties'];

    // test model now
    $this->type = TypeRegistry::get($type);
    $this->testTable($table);
  }

  public function testTable($table)
  {
    if($table == null)
    {
      $connection = $this->in('From which source connection would you like to import a table ?', $this->connections, 'default');
      $tables = ConnectionManager::get($connection)->schemaCollection()->listTables();
      foreach($tables as $key => $tableName) $this->out($key.': '.$tableName);
      $tableKey = $this->in('Which table you like to import ? ( type in the number )');
      if(!empty($tables[$tableKey])) $table = $tables[$tableKey];
      else $this->testTable(null);

    }
    $this->table = TableRegistry::get($table);
    try {
      $row = $this->table->find()->first();
    } catch (\Exception $e) {
      $this->err($e->getMessage());
      return $this->testTable(null);
    }

    $this->createMapping();
  }

  public function createMapping()
  {
    // reset!
    foreach($this->properties as $key => &$value) $value = null;

    $this->info('Ok let\'s build the mapping now...');
    foreach($this->properties as $key => &$value)
    {
      $value = null;
      if($key == 'model') continue;
      if($key == 'locale') continue;
      if($this->in('assign ES "'.$key.'" key with a table\'s field?',['y', 'n'],'y') == 'n')continue;

      foreach($this->table->getSchema()->columns() as $number => $field) $this->out($number.': '.$field);
      $value = $this->in('Build ES "'.$key.'" with comma separated preceding fields: ( type in number(s) )');
    }

    $count = 0;
    foreach($this->properties as $key => &$value)
    {
      if($key == 'model') continue;
      if($key == 'locale') continue;
      if($value == null) continue;

      $fields = explode(',', $value);
      $count = (count($fields) > $count)? count($fields):$count;
      $value = [];

      foreach($fields as $field) $value[] = $this->table->getSchema()->columns()[trim($field)];
    }

    $this->info('Mapping for '.$this->type->alias().': ');
    $mapping = [];
    $mapping[] = array_keys($this->properties);
    for($i = 0; $i < $count; $i++)
    {
      $row = [];
      foreach($this->properties as $field => $f) $row[] = ($f == null || empty($f[$i]))? null: $f[$i];
      $mapping[] = $row;
    }
    $this->helper('Table')->output($mapping);

    if($this->in('looks good?',['y', 'n'],'y') == 'n') return $this->createMapping();

    $this->import();
  }

  public function import()
  {
    $finder = $this->table->hasBehavior('Translate')? 'translations': 'all';
    $total = $this->table->find($finder)->count();
    $chunkSize = 50; $count = 0;

    // $mapper
    $properties = $this->properties;
    $defaultLocale = Configure::read('App.defaultLocale');
    $model = $this->table->alias();
    $mapper = function($entity, $key, $mapReduce) use($properties, $defaultLocale, $model)
    {
      // regular item
      $item = [];
      foreach($properties as $field => $entityFileds){

        $item[$field] = '';

        if($field == 'model'){ $item[$field] = $model; continue; }
        if($field == 'locale'){ $item[$field] = $defaultLocale; continue; }

        if(!empty($entityFileds))
        {
          if(!is_array($entityFileds)) $item[$field] = html_entity_decode(strip_tags($entity->get($entityFileds)));
          else foreach($entityFileds as $entityFiled) $item[$field] .= html_entity_decode(strip_tags($entity->get($entityFiled))).' ';
        }

      }
      $mapReduce->emitIntermediate($item, $entity->id);

      //i18n
      if(!empty($entity->get('_i18n')))
      {
        $locales = [];
        foreach($entity->get('_i18n') as $i18n)
        {
          if(!array_key_exists($i18n->locale, $locales)) $locales[$i18n->locale] = new Entity();
          $locales[$i18n->locale]->set($i18n->field, $i18n->content);
        }

        foreach($locales as $locale => $localEntity)
        {
          $localeItem = $item;
          foreach($properties as $field => $entityFileds)
          {
            if($field == 'locale'){ $localeItem[$field] = $locale; continue; }
            if($field == 'model') continue;

            if(!empty($entityFileds))
            {
              if(!is_array($entityFileds)){
                  if(!empty($localEntity->get($entityFileds))) $localeItem[$field] = html_entity_decode(strip_tags($localEntity->get($entityFileds)));
              }else{
                foreach($entityFileds as $entityFiled){
                  if($localEntity->get($entityFiled)) $localeItem[$field] .= html_entity_decode(strip_tags($localEntity->get($entityFiled))).' ';
                }
              }
            }
          }
          $mapReduce->emitIntermediate($localeItem, $entity->id);
        }
      }

    };

    // $reducer
    $reducer = function($entities, $id, $mapReduce){ foreach($entities as $entity) $mapReduce->emit($entity, null); };

    $items = $this->table->find($finder)->mapReduce($mapper, $reducer)->page(1)->limit(4)->toArray();

    $this->save($items);
  }

  public function save($items)
  {
    $this->type->saveMany($this->type->newEntities($items));
  }
}
