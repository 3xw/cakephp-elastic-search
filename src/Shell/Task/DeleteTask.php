<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\Network\Http\Client;

class DeleteTask extends ElasticeSearchConnectTask
{

  public $client = null;

  public function main($index = null)
  {
    // set index
    if($index == null)
    {
      $proposal = Inflector::slug(substr(ROOT, strrpos(ROOT, '/')),'_');
      $index = $this->in('Index name to delete ?',['www_'.$proposal,'dev_'.$proposal],'dev_'.$proposal);
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
      $this->goDelete($index);
    }else
    {
      $this->err('error with index:"'.$index.'"');
      debug(json_decode($response->body(),true));
    }
  }

  public function goDelete($index)
  {
    $url = ($this->connection->config()['port'] == 443)? 'https://': 'http://';
    $url .= $this->connection->config()['host'].'/'.$index;

    $sure = $this->in('Are you sur you want to delete existing index: '.$index,['yes','no'],'yes');
    if($sure != 'yes') return $this->main();

    // delete
    $response = $this->client->delete($url);
    if($response->code == 200){
      $this->info('Ok index: "'.$index.'" deleted!');
    }else{
      $this->err('Warning error for delete index: "'.$index.'".');
      debug(json_decode($response->body(),true));
    }
  }
}
