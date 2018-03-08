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
Follow offical doc [native elasticsearch mapping format](https://www.elastic.co/guide/en/elasticsearch/reference/1.5/mapping.html) to create your own index mapping or use default one which the prompt proposes you: vendor/3xw/cakephp-elastic-search/templates/mapping.json

	{
	  "mappings": {
	    "items": {
	      "properties": {
	
	        "locale": { "type": "string", "index": "not_analyzed"},
	        "model": { "type": "string", "index": "not_analyzed"},
	        "foreign_key": { "type": "string", "index": "not_analyzed" },
	
	        "title": { "type": "string"},
	        "slug": { "type": "string"},
	        "content": { "type": "string"}
	      }
	    }
	  }
	}

Optionally you can add 'settings', 'aliases' and 'warmers' keys and objects to cutomize your indexes. The shell will forfill settings for you with [-r 1] and [-s 5] params as follow:

	"settings" : {
	    "index" : {
		    "number_of_shards" : 5,
		    "number_of_replicas" : 1
	    }
    }

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
	
	3xwantoine-3:cinebulletin.ch antoine$ 
	
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
	
	3xwantoine-3:cinebulletin.ch antoine$ 
	
	
	
