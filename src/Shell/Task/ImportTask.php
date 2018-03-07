<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\Filesystem\File;
use Cake\Network\Http\Client;

class importTask extends ElasticeSearchConnectTask
{
  public $client = null;

  public function main($index = null, $model = null)
  {
    // set index
    if($index == null)
    {
      $proposal = Inflector::slug(substr(ROOT, strrpos(ROOT, '/')),'_');
      $index = $this->in('Index name for import ?',[$proposal,'dev_'.$proposal],'dev_'.$proposal);
    }
    $index = Inflector::slug($index,'_');

    $this->testIndex($index, $model);
  }

  public function testIndex($index, $model)
  {
    $url = ($this->connection->config()['port'] == 443)? 'https://': 'http://';
    $url .= $this->connection->config()['host'].'/'.$index;

    if($this->client == null) $this->client = new Client();
    $response = $this->client->get($url);
    if($response->code == 200)
    {
      $this->collectMapping(json_decode($response->body(),true),$index,$model);
    }else
    {
      $this->err('error with index:"'.$index.'"');
      debug(json_decode($response->body(),true));
      $this->main($index, $model);
    }
  }

  public function collectMapping($json, $index, $model)
  {
    debug($json);
  }
}
