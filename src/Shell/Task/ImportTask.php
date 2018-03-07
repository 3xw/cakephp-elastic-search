<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\ElasticSearch\TypeRegistry;
use Cake\ORM\TableRegistry;
use Cake\Network\Http\Client;

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
    if($table == null) $table = $this->in('Which table you like to import ?');
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
      if($this->in('assign ES "'.$key.'" key with a table\'s field?',['Y', 'N'],'Y') == 'N')continue;

      foreach($this->table->getSchema()->columns() as $number => $field) $this->out($number.': '.$field);
      $value = $this->in('Build ES "'.$key.'" with comma separated preceding fields:');
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

    if($this->in('looks good?',['Y', 'N'],'Y') == 'N') return $this->createMapping();

    $this->import();
  }

  public function import()
  {
      //return debug($this->table->hasBehavior('Translate'));
  }


}
