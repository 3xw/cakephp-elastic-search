<?php
namespace Trois\ElasticSearch\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Trois\ElasticSearch\Utility\Needle;
use Cake\Datasource\ConnectionManager;

class ESComponent extends Component
{
  public $url = null;

  protected $_defaultConfig = [
    'connection' => 'elastic'
  ];


}
