<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Datasource\ConnectionManager;
use Cake\Console\Shell;

class ElasticeSearchConnectTask extends Shell
{
  public $connections = [];

  public $connection = null;

  public function getOptionParser()
  {
    $parser = parent::getOptionParser()
    ->addOptions([
      'connection' => ['short' => 'c', 'help' => 'The Elestic Search connection to use']
    ]);

    return $parser;
  }

  public function initialize()
  {
    parent::initialize();
    $this->connections = ConnectionManager::configured();
  }

  public function startup()
  {
    if(empty($this->params['connection'])) $this->setConnection();
    else $this->testConnection();
  }

  public function setConnection()
  {
    $this->params['connection'] = $this->in('Which elastic connection?', $this->connections, null);
    $this->testConnection();
  }

  public function testConnection()
  {
    if(array_search($this->params['connection'], $this->connections) === false)
    {
      $this->err('This connection does not exists!');
      $this->setConnection();
    }else{
      $this->connection = ConnectionManager::get($this->params['connection']);
      if(empty($this->connection->config()['driver']) || $this->connection->config()['driver'] != 'Cake\ElasticSearch\Datasource\Connection')
      {
        $this->err('This connection is not an Elastic Search Datasource!');
        $this->info('Please choose an other one:');
        $this->setConnection();
      }
    }
  }
}
