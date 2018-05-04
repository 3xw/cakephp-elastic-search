<?php
namespace Trois\ElasticSearch\Model\Document;

use Cake\ElasticSearch\Document;

class Item extends Document
{
  public function _setContent($content)
  {
    return html_entity_decode(strip_tags($content));
  }

  public function _setTitle($title)
  {
    return html_entity_decode(strip_tags($title));
  }
}
