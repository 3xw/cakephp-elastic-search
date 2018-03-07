<?php
namespace Trois\ElasticSearch\Shell;

use Cake\Console\Shell;

class ElasticShell extends Shell
{
  public $tasks = ['Trois/ElasticSearch.Delete','Trois/ElasticSearch.Create','Trois/ElasticSearch.Info','Trois/ElasticSearch.Import'];

  public function getOptionParser()
  {
    $parser = parent::getOptionParser()
    ->addSubcommand('delete', [
      'help' => 'Execute delete index Task.',
      'parser' => $this->Delete->getOptionParser(),
    ])
    ->addSubcommand('create', [
      'help' => 'Execute create index w/ mapping file Task.',
      'parser' => $this->Create->getOptionParser(),
    ])
    ->addSubcommand('info', [
      'help' => 'Execute info on index Task.',
      'parser' => $this->Info->getOptionParser(),
    ])
    ->addSubcommand('import', [
      'help' => 'Execute info on index Task.',
      'parser' => $this->Info->getOptionParser(),
    ]);

    return $parser;
  }

  public function main()
  {
    $this->out($this->OptionParser->help());
  }
}
