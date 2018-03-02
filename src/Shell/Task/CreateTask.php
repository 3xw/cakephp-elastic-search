<?php
namespace Trois\ElasticSearch\Shell\Task;

use Cake\Utility\Inflector;
use Cake\Filesystem\File;
use Cake\Network\Http\Client;

class CreateTask extends ElasticeSearchConnectTask
{
  public function getOptionParser()
  {
    $parser = parent::getOptionParser()
    ->addOptions([
      'shards' => ['short' => 's', 'help' => 'The index number of shards. Default is 5','default' => 5]
    ])
    ->addOptions([
      'replicas' => ['short' => 'r', 'help' => 'The index number of replicas. Default is 1','default' => 1]
    ]);

    return $parser;
  }

  public function main($index = null, $mappingFile = null)
  {
    debug($this->connection);
    // set index
    if($index == null)
    {
      $proposal = Inflector::slug(substr(ROOT, strrpos(ROOT, '/')),'_');
      $index = $this->in('Index name to create ?',[$proposal,'dev_'.$proposal],'dev_'.$proposal);
    }
    $index = Inflector::slug($index,'_');

    // set mapping
    $this->getMappingFile($index, $mappingFile);
  }

  public function getMappingFile($index, $mappingFile = null)
  {
    $template = ROOT.'/vendor/3xw/cakephp-elastic-search/templates/mapping.json';
    if($mappingFile == null) $mappingFile = $this->in('Path to mapping file ?', $template, $template);
    $mappingFile = new File($mappingFile);

    // check file
    if(!$mappingFile->exists())
    {
      $this->err($mappingFile->path.' doses not exists!');
      return $this->getMappingFile($index, null);
    }

    // go check JSON now
    $this->getJsonMapping($index, $mappingFile);
  }

  public function getJsonMapping($index, $file)
  {
    // check json
    $json = $file->read();
    try {
      $jsonObject = json_decode($json, true);
    } catch (\Exception $e) {
      return $this->err($file->path.' is not valid JSON!');
    }

    if(empty($jsonObject['mappings'])) return $this->err('Json file needs a "mappings" key!');

    if(empty($jsonObject['settings']))
    {
      $jsonObject['settings'] = [
        'index' => [
          'number_of_shards' => $this->params['shards'],
          'number_of_replicas' => $this->params['replicas']
          ]
      ];
    }

    $this->createIndex($index, json_encode($jsonObject));
  }

  public function createIndex($index, $validJsonString)
  {
    debug($validJsonString);
  }
}
