<?php
namespace Trois\ElasticSearch\Model\Document;

use Cake\ElasticSearch\Document;
use Cake\Utility\Text;

class Item extends Document
{
  public function _setContent($content)
  {
    return str_replace(["\r", "\n", "\t"], '', html_entity_decode(strip_tags($content)));
  }

  public function _setTitle($title)
  {
    return str_replace(["\r", "\n", "\t"], '', html_entity_decode(strip_tags($title)));
  }
}
