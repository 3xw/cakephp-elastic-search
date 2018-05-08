<?php
namespace Trois\ElasticSearch\Model\Document;

use Cake\ElasticSearch\Document;
use Cake\I18n\Time;

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

  public function _getCreated($created)
  {
    if(!$created) return new Time();
    else return $created;
  }

  public function _getModified($modified)
  {
    if(!$modified) return new Time();
    return $modified;
  }
}
