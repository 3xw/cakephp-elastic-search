<?php
namespace Trois\ElasticSearch\Shell\Task;

class CreateTask extends ElasticeSearchConnectTask
{

  public function main($connection = null, $index = null, $mappingFile = null)
  {
    debug($this->params);
    //parent::main($connection, $index, $mappingFile);
  }
}
