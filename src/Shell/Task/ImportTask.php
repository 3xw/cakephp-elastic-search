<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\ElasticSearch\IndexRegistry;
use Cake\ORM\TableRegistry;
use Cake\Network\Http\Client;
use Cake\Datasource\ConnectionManager;
use Cake\Core\Configure;
use Cake\ORM\Entity;

class importTask extends ElasticeSearchConnectTask
{
  public $client = null;

  public $index = null;

  public $indexName = null;

  public $properties = null;

  public $table = null;

  public function main($indexName = null, $table = null)
  {
    // set index
    if($indexName == null)
    {
      $proposal = Inflector::slug(substr(ROOT, strrpos(ROOT, '/')),'_');
      $indexName = $this->in('Index name for import ?');
    }
    $indexName = Inflector::slug($indexName,'_');

    $this->testIndex($indexName, $table);
  }

  public function testIndex($indexName, $table)
  {
    $url = ($this->connection->config()['port'] == 443)? 'https://': 'http://';
    $url .= $this->connection->config()['host'].'/'.$indexName;

    if($this->client == null) $this->client = new Client();
    $response = $this->client->get($url);
    if($response->code == 200)
    {
      $this->indexName = $indexName;
      $this->collectMapping(json_decode($response->body(),true),$table);
    }else
    {
      $this->err('error with index:"'.$indexName.'"');
      debug(json_decode($response->body(),true));
      return $this->main(null, $table);
    }
  }

  public function collectMapping($json, $table)
  {
    if(empty($json[$this->indexName]['mappings'])){
      $this->err('no mappings found for index:"'.$this->indexName.'"');
      return $this->main(null, $table);
    }

    // collect types
    $documents = array_keys($json[$this->indexName]['mappings']);
    $document = $this->in('In which document would you import MySQL data?',$documents,$documents[0]);
    $this->properties = $json[$this->indexName]['mappings'][$document]['properties'];

    // test model now
    $this->index = IndexRegistry::get($document);
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
    $this->table = TableRegistry::get(Inflector::camelize($table));
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
    $properties = [];

    $this->info('Ok let\'s build the mapping now...');
    foreach($this->properties as $key => $prop)
    {
      $properties[$key] = ['value' => null,'type' => null];

      if($key == 'model') continue;
      if($key == 'locale') continue;
      if($this->in('assign ES "'.$key.'" key with a table\'s field?',['y', 'n'],'y') == 'n')
      {
        if($this->in('assign ES "'.$key.'" key with a static value?',['y', 'n'],'y') == 'n') continue;
        $value = $this->in('Please nter ES "'.$key.'" value, or type "cancel" to cacncel');
        if($value == 'cancel') continue;
        $properties[$key]['type'] = 'static';
      }

      if(empty($properties[$key]['type']))
      {
        foreach($this->table->getSchema()->columns() as $number => $field) $this->out($number.': '.$field);
        $value = $this->in('Build ES "'.$key.'" with comma separated preceding fields: ( type in number(s) )');
        $properties[$key]['type'] = 'fields';
      }

      $properties[$key]['value'] = $value;
    }

    $count = 0;
    foreach($properties as $key => $prop)
    {
      if($key == 'model') continue;
      if($key == 'locale') continue;
      if($prop['value'] == null) continue;

      if($prop['type'] == 'fields')
      {
        $fields = explode(',', $prop['value']);
        $count = (count($fields) > $count)? count($fields):$count;
        $properties[$key]['value'] = [];
        foreach($fields as $field) $properties[$key]['value'][] = $this->table->getSchema()->columns()[trim($field)];
      }else{
        $count = (1 > $count)? 1:$count;
        $properties[$key]['value'] = [$properties[$key]['value']];
      }
    }

    $this->info('Mapping for '.$this->index->alias().': ');
    $mapping = [];
    $mapping[] = array_keys($properties);
    for($i = 0; $i < $count; $i++)
    {
      $row = [];
      foreach($properties as $prop) $row[] = empty($prop['value'][$i])? null: ($prop['type'] == 'static'? $prop['value'][$i].'(static)': $prop['value'][$i]);
      $mapping[] = $row;
    }

    $this->helper('Table')->output($mapping);
    if($this->in('looks good?',['y', 'n'],'y') == 'n') return $this->createMapping();
    //$this->import($properties);
  }

  public function import($mapping)
  {

    // caster
    $caster = function($entity, $field, $type)
    {
      $value;
      switch($type)
      {
        case 'keyword':
        case 'text':
          $value = html_entity_decode(strip_tags($entity->get($field))).' ';
          break;
        case 'boolean':
          if(empty($entity->get($field))) $value = "false";
          else $value = "true";
          break;
        case 'integer':
          $value = (int) $entity->get($field);
          break;
        case 'double':
          $value = (double) $entity->get($field);
          break;
        case 'float':
          $value = (float) $entity->get($field);
          break;
        case 'date':
          $value = (string) $entity->get($field)->format("Y-m-d\TH:i:s.000P");
          break;
        default: $value = $entity->get($field);
      }

      //$this->out($field.' of type '.$type.': '.$entity->get($field).' => '.$value);
      return $value;
    };

    // $mapper
    $properties = $this->properties;
    $defaultLocale = Configure::read('App.defaultLocale');
    $model = $this->table->getAlias();
    $mapper = function($entity, $key, $mapReduce) use($caster, $mapping, $properties, $defaultLocale, $model)
    {
      // regular item
      $item = [];
      foreach($mapping as $field => $entityFileds){

        $item[$field] = '';

        if($field == 'model'){ $item[$field] = $model; continue; }
        if($field == 'locale'){ $item[$field] = $defaultLocale; continue; }

        if(!empty($entityFileds))
        {
          if(!is_array($entityFileds)) $item[$field] = $caster($entity, $entityFileds, $properties[$field]['type']);
          else {
            if(count($entityFileds) == 1 )
            {
              foreach($entityFileds as $entityFiled) $item[$field] = $caster($entity, $entityFiled, $properties[$field]['type']);
            }
            else foreach($entityFileds as $entityFiled) $item[$field] .= $caster($entity, $entityFiled, $properties[$field]['type']);
          }
        }

      }
      //debug($item);
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
          foreach($mapping as $field => $entityFileds)
          {
            if($field == 'locale'){ $localeItem[$field] = $locale; continue; }
            if($field == 'model') continue;

            if(!empty($entityFileds))
            {
              if(!is_array($entityFileds)){
                  if(!empty($localEntity->get($entityFileds))) $localeItem[$field] = $caster($localEntity, $entityFileds, $properties[$field]['type']);
              }else{
                if(count($entityFileds) == 1){
                  foreach($entityFileds as $entityFiled){
                    if($localEntity->get($entityFiled)) $localeItem[$field] =  $caster($localEntity, $entityFiled, $properties[$field]['type']);
                  }
                }else{
                  $localeItem[$field] = '';
                  foreach($entityFileds as $entityFiled){
                    if($localEntity->get($entityFiled)) $localeItem[$field] .=  $caster($localEntity, $entityFiled, $properties[$field]['type']);
                  }
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

    // retrive and save them all
    $finder = $this->table->hasBehavior('Translate')? 'translations': 'all';
    $total = $this->table->find($finder)->count();
    $chunkSize = 25; $count = 0; $page = 1;

    // loop
    $this->out('Found '.$total.' rows in table.');
    $this->out('Now saving:');
    $this->hr();
    $progress = $this->helper('Progress');
    $progress->init(['total' => $total,'width' => 0]);
    while($count < $total)
    {
      $items = $this->table->find($finder)->mapReduce($mapper, $reducer)->limit($chunkSize)->page($page)->toArray();
      if(!$items)
      {
        $this->error('An error occured saving items:');
        debug($items->error());
        break;
      }
      $this->save($items);

      // update
      $count += $chunkSize;
      $page++;
      $progress->increment($chunkSize);
      $progress->draw();
    }
    $this->out(' ');
    $this->hr();
    $this->info($count.' records where created and saved out of '.$total.' table rows');
  }

  public function save($items)
  {
    debug($this->index->newEntities($items));
    //return $this->index->saveMany($this->index->newEntities($items));
  }
}
