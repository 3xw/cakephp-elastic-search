<?php
namespace Trois\ElasticSearch\ORM;

use Cake\ElasticSearch\QueryBuilder;
use Cake\ElasticSearch\ResultSet;
use Cake\ElasticSearch\Query as BaseQuery;

class Query extends BaseQuery
{
  public function getPart($name)
  {
    return $this->_queryParts[$name];
  }

  public function setPart($name, $value)
  {
    return $this->_queryParts[$name] = $value;
  }
}
