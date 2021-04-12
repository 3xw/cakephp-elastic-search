<?php
declare(strict_types=1);

namespace Trois\ElasticSearch\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;

class JsonItemQueryShell extends Shell
{
  public function main()
  {
    $json = '{
      "filter":{
        "operator":"or",
        "filters":[
          {
            "filter":{
              "type":"term",
              "value":"Documents"
            },
            "property":"model"
          }
        ],
      }
    }';
  }
}
