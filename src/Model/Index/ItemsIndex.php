<?
namespace Trois\ElasticSearch\Model\Index;

use Trois\ElasticSearch\ORM\Index;

class ItemsIndex extends Index
{

  public function getName()
  {
    return 'items';
  }

  public function getType()
  {
    return 'items';
  }

}
