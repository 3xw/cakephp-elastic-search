<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;

class CreateTask extends ElasticeSearchConnectTask
{
  public function main($index = null, $mappingFile = null)
  {
    // set index
    if($index == null) $index = $this->in('What is the name of the index you would like to create ?');
    $index = Inflector::slug($index);

    // set mapping
  }
}
