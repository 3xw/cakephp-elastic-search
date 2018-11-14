# cakephp-elastic-search plugin for CakePHP
This plugin allows you deal with ES

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require 3xw/elastic-search

Load it in your config/boostrap.php

	Plugin::load('Trois/ElasticSearch');

## Setup
in app.php add at least one connection:

	'Datasources' => [
	    'elastic' => [
	      'className' => 'Cake\ElasticSearch\Datasource\Connection',
	      'driver' => 'Cake\ElasticSearch\Datasource\Connection',
	      'host' => 'localhost',
	      'port' => 6379,
	    ],
		...
	]

## Mapping
The shell will ask you a mapping template.
Follow offical doc [native elasticsearch mapping format](https://www.elastic.co/guide/en/elasticsearch/reference/6.x/mapping.html) to create your own index mapping or use default one which the prompt proposes you: vendor/3xw/cakephp-elastic-search/templates/mapping.json

	{
		"settings": {
	    "analysis": {
	      "filter": {
	        "french_elision": {
	          "type":"elision",
	          "articles_case": true,
	          "articles": [
	            "l", "m", "t", "qu", "n", "s",
	            "j", "d", "c", "jusqu", "quoiqu",
	            "lorsqu", "puisqu","t"
	          ]
	        },
	        "french_stop": {
	          "type":       "stop",
	          "stopwords":  "_french_"
	        },
	        "french_keywords": {
	          "type":       "keyword_marker",
	          "keywords":   [
	            "Prométerre",
	            "Agrivit","Ecoprest","Estimapro","Fidasol","Fiprom",
	            "Fondation rurale de prévoyance","FRP",
	            "Mandaterre",
	            "Office de crédit agricole","OCA",
	            "Proconseil","Proterroir","SAD","SRPJ","Sofia SA"
	          ]
	        },
	        "french_stemmer": {
	          "type":       "stemmer",
	          "language":   "light_french"
	        }
	      },
	      "analyzer": {
	        "french": {
	          "tokenizer":  "standard",
	          "filter": [
	            "french_elision",
	            "lowercase",
	            "french_stop",
	            "french_keywords",
	            "french_stemmer"
	          ]
	        }
	      }
	    }
	  },
	  "mappings": {
	    "items": {
	      "properties": {
	        "foreign_key": { "type": "integer"},
	        "model": { "type": "keyword"},
	        "title": {"type": "completion", "analyzer":"french", "max_input_length": 255},
	        "content": {"type": "text"}
	      }
	    }
	  }
	}


## Behavior
set up your behavior as you wish

	$this->addBehavior(\Trois\ElasticSearch\ORM\Behavior\SyncWithESBehavior::class,[
      'index' => 'App\Model\Index\ItemsIndex',
      'primaryKey' => 'foreign_key', // string or callable
      'translate' => false, // property name if yes ex: locale
      'staticMatching' => [
        'model' => 'Posts'
      ], // or [keyN => valueN/callableN]
      'mapping' => [ // properties => 1. Array: entity field(s) || properties => 2. String: static value or callable
        'title' => new \Trois\ElasticSearch\ORM\CompletionConstructor(['title'],[
	      'contexts' => [
	        'model' => 'Posts'
	      ]
        'content' => ['header','content']
      ],
      'deleteDocument' => true,
      'separator' => ' - '
    ]);
    
## Shell
Create indexes

	bin/cake elastic create [indexName] [mappingFile.json]

	------
	Execute create index w/ mapping file Task.

	Usage:
	cake trois/elastic_search.elastic create [-c] [-h] [-q] [-r 1] [-s 5] [-v]

	Options:

	--connection, -c  The Elestic Search connection to use
	--help, -h        Display this help.
	--quiet, -q       Enable quiet output.
	--replicas, -r    The index number of replicas. Default is 1
	                  (default: 1)
	--shards, -s      The index number of shards. Default is 5
	                  (default: 5)
	--verbose, -v     Enable verbose output.

Get index info

	bin/cake elastic info [indexName]

	------
	Execute info on index Task.

	Usage:
	cake trois/elastic_search.elastic info [-c] [-h] [-q] [-v]

	Options:

	--connection, -c  The Elestic Search connection to use
	--help, -h        Display this help.
	--quiet, -q       Enable quiet output.
	--verbose, -v     Enable verbose output.

Delete index

	bin/cake elastic delete [indexName]

	------
	Execute delete index Task.

	Usage:
	cake trois/elastic_search.elastic delete [-c] [-h] [-q] [-v]

	Options:

	--connection, -c  The Elestic Search connection to use
	--help, -h        Display this help.
	--quiet, -q       Enable quiet output.
	--verbose, -v     Enable verbose output.

Import entire table

	bin/cake elastic import [indexName] [tableName]
	------
	Execute import on index with table.

	Usage:
	cake trois/elastic_search.elastic import [-c] [-h] [-q] [-v]

	Options:

	--connection, -c  The Elestic Search connection to use
	--help, -h        Display this help.
	--quiet, -q       Enable quiet output.
	--verbose, -v     Enable verbose output.
