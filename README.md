# cakephp-elastic-search plugin for CakePHP
This plugin allows you deal with ES

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

	composer require 3xw/elastic-search

Load it in your config/boostrap.php

	Plugin::load('Trois/ElasticSearch');

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