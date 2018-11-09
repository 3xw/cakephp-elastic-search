<?
namespace Trois\ElasticSearch\Model\Index;

use Cake\ElasticSearch\Index;
use Cake\Utility\Text;

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
