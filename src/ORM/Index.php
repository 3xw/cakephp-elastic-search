<?php
namespace Trois\ElasticSearch\ORM;

use Elastica\Query\AbstractQuery;

use Cake\Utility\Hash;
use Cake\ElasticSearch\Query;
use Cake\ElasticSearch\QueryBuilder;
use Cake\ElasticSearch\Index as BaseIndex;

use Trois\ElasticSearch\ORM\Query as CustomQuery;

class Index extends BaseIndex
{
  public function query()
  {
    return new CustomQuery($this);
  }

  public function isDev()
  {
    $dev = true;
    $conn = $this->_connection->getConfig();
    if(array_key_exists('dev', $conn) && $conn['dev'] === false) $dev = false;
    if(array_key_exists('prod', $conn) && $conn['prod'] === true) $dev = false;
    return $dev;
  }

  public function findSearch(Query $query, array $options)
  {
    //debug($options);

    // filters
    if(Hash::check($options, 'query.filter')) $query->where($this->getFilter(null, Hash::get($options, 'query.filter')));

    // nested
    if(Hash::check($options, 'query.nested')) $query->queryMust($this->getNested(Hash::get($options, 'query.nested')));
    // query
    if(Hash::check($options, 'query.query')) $query->queryMust($this->getFilter(null, Hash::get($options, 'query.query')));

    /*
    debug($query);
    debug(json_encode($query->compileQuery()->toArray()));
    die();
    */
    
    return $query;
  }

  public function getNested($nested = [])
  {
    // check
    $toCheck = ['path','query.filter'];
    foreach($toCheck as $check) if(!Hash::check($nested, $check)) throw new \Exception('Nested needs a '.$check);

    // args
    $path = Hash::get($nested, 'path');
    $filter = Hash::get($nested, 'query.filter');
    $builder = new QueryBuilder();

    // query
    return $builder->nested($path, $this->getFilter($builder, $filter));
  }

  public function getFilter(QueryBuilder $builder = null, $filter = [])
  {
    // check
    $toCheck = ['operator'];
    foreach($toCheck as $check) if(!Hash::check($filter, $check)) throw new \Exception('Filters needs an '.$check);

    // operator
    $operator = Hash::get($filter, 'operator');

    switch(true)
    {
      case Hash::check($filter, 'filters'):
      $filters = Hash::get($filter, 'filters');
      return $this->getFilters($filters, $operator);

      case Hash::check($filter, 'property') && Hash::check($filter, 'value'):
      $property = Hash::get($filter, 'property');
      $value = Hash::get($filter, 'value');
      switch($operator)
      {
        case 'term':
        return $builder->term($property, $value);

        case 'match':
        return $builder->match($property, $value);

        default:
        throw new \Exception('Filter: this operator "'.$operator.'" is unhadeled');
      }
      return $this->getClosure($filters, $operator);

      default:
      throw new \Exception('getFilter unhandled');
    }
  }

  public function getFilters($filters, $operator)
  {
    return function($builder) use ($filters, $operator)
    {
      $args = [];
      foreach($filters as $f) $args[] = $this->getFilter($builder, $f);
      switch($operator)
      {
        case 'or':
        case 'and':
        break;

        default:
        throw new \Exception('filterQuery: this operator "'.$operator.'" is unhadeled');
      }
      return call_user_func_array([$builder, $operator], $args);
    };
  }
}
