<?php
namespace Trois\ElasticSearch\Shell;

use Cake\Console\Shell;

class ElasticShell extends Shell
{
  public $tasks = ['Trois/ElasticSearch.Delete','Trois/ElasticSearch.Create'];

  public function getOptionParser()
  {
    $parser = parent::getOptionParser()
    ->addSubcommand('delete', [
      'help' => 'Execute The News Task.',
      'parser' => $this->Delete->getOptionParser(),
    ])
    ->addSubcommand('create', [
      'help' => 'Execute The News Task.',
      'parser' => $this->Create->getOptionParser(),
    ]);

    return $parser;
  }

  public function main()
  {
    $this->out($this->OptionParser->help());
  }
}
