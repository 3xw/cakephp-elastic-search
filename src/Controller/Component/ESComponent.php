<?php
namespace Trois\ElasticSearch\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Trois\ElasticSearch\Utility\Needle;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Client;

class ESComponent extends Component
{
  protected $_defaultConfig = [
    'connection' => 'elastic',
    'host' => null,
    'port' => null,
    'index' => null,
    'type' => 'items',
    'requestQueryParam' => 'q'
  ];

  public $emptyResults = [
    'took' => 0,
  	'hits' => [
  		'total' => 0,
  		'max_score' => 0,
  		'hits' => []
    ]
  ];

  public function initialize(array $config)
  {
    parent::initialize($config);
    if( !empty($this->getConfig('connection')) )
    {
      $conn = ConnectionManager::get($this->getConfig('connection'));
      if(empty($this->getConfig('host'))) $this->setConfig('host', $conn->getConfig('host'));
      if(empty($this->getConfig('port'))) $this->setConfig('port', $conn->getConfig('port'));
      if(empty($this->getConfig('index'))) $this->setConfig('index', $conn->getConfig('index'));
    }
  }

  protected function _getUrl($config = [])
  {
    $config = array_merge($this->getConfig(), $config);
    $proto = $config['port'] == 443? 'https': 'http';
    $type = empty($config['type'])? '': '/'.$config['type'];
    return $proto.'://'.$config['host'].':'.$config['port'].'/'.$config['index'].$type;
  }

  protected function _getSearchUrl($config = [])
  {
    return $this->_getUrl($config).'/_search';
  }

  public function search($query, $config = [])
  {
    if(empty($this->_registry->getController()->request->getQuery($this->getConfig('requestQueryParam')))) return $this->emptyResults;

    // needle
    $needle = Needle::clean($this->_registry->getController()->request->getQuery($this->getConfig('requestQueryParam')));

    // query
    $query = str_replace('%needle%',$needle,json_encode($query));

    // search
    $http = new Client();
    $response = $http->post($this->_getSearchUrl($config), $query,['type' => 'json']);

    // format
    if($response->code != 200) throw new \Exception("Error Processing Request", 1);
    $response = json_decode($response->body(), true);
    if(!$response) throw new \Exception("Error Parsing response", 1);

    // reply
    return $response;
  }

}
