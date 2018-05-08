<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\Network\Http\Client;

class InfoTask extends ElasticeSearchConnectTask
{

  public $client = null;

  public function main($index = null)
  {
    // set index
    if($index == null)
    {
      $proposal = Inflector::slug(substr(ROOT, strrpos(ROOT, '/')),'_');
      $index = $this->in('Index name ?',['www_'.$proposal,'dev_'.$proposal],'dev_'.$proposal);
    }
    $index = Inflector::slug($index,'_');

    $this->testIndex($index);
  }

  public function testIndex($index)
  {
    $url = ($this->connection->config()['port'] == 443)? 'https://': 'http://';
    $url .= $this->connection->config()['host'].'/'.$index;

    if($this->client == null) $this->client = new Client();
    $response = $this->client->get($url);
    if($response->code == 200)
    {
      $this->info('Ok index: "'.$index.'"');
      debug(json_decode($response->body(),true));
    }else
    {
      $this->err('error with index:"'.$index.'"');
      debug(json_decode($response->body(),true));
    }
  }
}
