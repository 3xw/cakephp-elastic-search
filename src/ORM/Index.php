<?php
namespace Trois\ElasticSearch\ORM;

use Elastica\Query\AbstractQuery;
use Elastica\Query\MultiMatch;

use Cake\Utility\Hash;
use Cake\ElasticSearch\Query;
use Cake\ElasticSearch\QueryBuilder;
use Cake\ElasticSearch\Index as BaseIndex;

use Trois\ElasticSearch\ORM\Query as CustomQuery;

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

  public function findSearch(Query $query, array $options)
  {
    // filters
    if(Hash::check($options, 'query.filter')) $query->where($this->getFilter(new QueryBuilder(), Hash::get($options, 'query.filter')));

    // nested
    if(Hash::check($options, 'query.nested')) $this->getNested($query, Hash::get($options, 'query.nested'));

    // query
    if(Hash::check($options, 'query.query')) $query->queryMust($this->getFilter(new QueryBuilder(), Hash::get($options, 'query.query')));

    //debug($query->compileQuery());
    //die();

    return $query;
  }

  public function getNested(Query $query, $nested = [])
  {
    // nested.nested
    if(Hash::check($nested, 'nested'))
    {
      if(!is_array(Hash::get($nested, 'nested'))) throw new \Exception('nested.nested must be an array');
      foreach(Hash::get($nested, 'nested') as $n) $this->getNested($query, $n);
      return;
    }

    // check
    $toCheck = ['path','query.filter','query.operator'];
    foreach($toCheck as $check) if(!Hash::check($nested, $check)) throw new \Exception('Nested needs a '.$check);

    // args
    $path = Hash::get($nested, 'path');
    $filter = Hash::get($nested, 'query.filter');
    $operator = Hash::get($nested, 'query.operator');
    $builder = new QueryBuilder();
    $builder = $builder->nested($path, $this->getFilter($builder, $filter));

    // query
    switch($operator)
    {
      case 'and':
      $query->queryMust($builder);
      break;

      case 'or':
      $query->queryShould($builder);
      break;

      default:
      throw new \Exception('Nested: this operator "'.$operator.'" is unhadeled');
    }
  }

  public function getFilter(QueryBuilder $builder = null, $filter = [])
  {
    // check
    $toCheck = ['operator'];
    foreach($toCheck as $check) if(!Hash::check($filter, $check)) throw new \Exception('Filters needs an '.$check);

    // operator
    $operator = Hash::get($filter, 'operator');

    if(Hash::check($filter, 'filters'))
    {
      $filters = Hash::get($filter, 'filters');
      return $this->getFilters($filters, $operator);
    }

    // value Based
    $value = Hash::get($filter, 'value');

    if( Hash::check($filter, 'property'))
    {
      $property = Hash::get($filter, 'property');
      switch($operator)
      {
        case 'term':
        return $builder->term($property, $value);

        case 'match':
        return $builder->match($property, $value);

        default:
        throw new \Exception('Filter: this operator "'.$operator.'" is unhadeled');
      }
    }
    elseif(Hash::check($filter, 'properties') && is_array(Hash::get($filter, 'properties')))
    {
      $properties = Hash::get($filter, 'properties');
      switch($operator)
      {
        case 'match':
        return (new MultiMatch())->setQuery($value)->setFields($properties);

        default:
        throw new \Exception('Filter: this operator "'.$operator.'" is unhadeled');
      }
    }
    else throw new \Exception('getFilter unhandled');
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
