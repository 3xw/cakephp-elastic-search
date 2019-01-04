<?php
namespace Trois\ElasticSearch\ORM;

use Cake\ElasticSearch\Index as BaseIndex;

class Index extends BaseIndex
{
  public function isDev()
  {
    $dev = true;
    $conn = $this->_connection->getConfig();
    if(array_key_exists('dev', $conn) && $conn['dev'] === false) $dev = false;
    if(array_key_exists('prod', $conn) && $conn['prod'] === true) $dev = false;
    return $dev;
  }
}
