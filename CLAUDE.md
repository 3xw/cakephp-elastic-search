# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a CakePHP 4 plugin (`3xw/cakephp-elastic-search`) that provides Elasticsearch integration for CakePHP applications. The plugin enables automatic synchronization between CakePHP ORM entities and Elasticsearch documents, with support for search functionality and index management.

## Development Commands

### Running Tests
```bash
vendor/bin/phpunit
```

### Testing Individual Components
- Test specific component: `vendor/bin/phpunit tests/TestCase/Controller/Component/ESComponentTest.php`
- Test specific behavior: `vendor/bin/phpunit tests/TestCase/Model/Behavior/SyncWithESBehaviorTest.php`
- Test shell commands: `vendor/bin/phpunit tests/TestCase/Shell/ElasticShellTest.php`

### Elasticsearch Management Commands
```bash
# Create index with mapping
bin/cake elastic create [indexName] [mappingFile.json]

# Get index information
bin/cake elastic info [indexName]

# Delete index
bin/cake elastic delete [indexName]

# Import entire table to Elasticsearch
bin/cake elastic import [indexName] [tableName]
```

## Architecture Overview

### Core Components

1. **SyncWithESBehavior** (`src/ORM/Behavior/SyncWithESBehavior.php`)
   - Main behavior that automatically syncs CakePHP entities with Elasticsearch
   - Handles afterSave and afterDelete events
   - Supports internationalization with locale-specific documents
   - Configurable field mapping and data transformation

2. **ESComponent** (`src/Controller/Component/ESComponent.php`)
   - Controller component for search functionality
   - Handles search requests and query cleaning
   - Manages Elasticsearch HTTP connections

3. **ElasticShell** (`src/Shell/ElasticShell.php`)
   - CLI interface for index management
   - Delegates to specific tasks (Create, Delete, Info, Import)

4. **CompletionConstructor** (`src/ORM/CompletionConstructor.php`)
   - Builds Elasticsearch completion field data
   - Supports input fields and context mapping

### Key Utilities

- **Needle** (`src/Utility/Needle.php`): Search query sanitization
- **CakeORM** (`src/Utility/CakeORM.php`): ORM utility functions

### Plugin Structure

```
src/
├── Controller/Component/     # CakePHP components
├── Model/                   # Legacy models (Document, Index)
├── ORM/                     # Modern ORM behaviors and utilities
├── Shell/                   # CLI commands and tasks
└── Utility/                 # Helper utilities
```

## Configuration

The plugin expects Elasticsearch connection configuration in `config/app.php`:

```php
'Datasources' => [
    'elastic' => [
        'className' => 'Cake\ElasticSearch\Datasource\Connection',
        'driver' => 'Cake\ElasticSearch\Datasource\Connection',
        'host' => 'localhost',
        'port' => 6379,
    ],
]
```

## Behavior Configuration Example

```php
$this->addBehavior(\Trois\ElasticSearch\ORM\Behavior\SyncWithESBehavior::class, [
    'index' => 'App\Model\Index\ItemsIndex',
    'primaryKey' => 'foreign_key',
    'translate' => false,
    'staticMatching' => ['model' => 'Posts'],
    'mapping' => [
        'title' => new \Trois\ElasticSearch\ORM\CompletionConstructor(['title'], [
            'contexts' => ['model' => 'Posts']
        ]),
        'content' => ['header', 'content']
    ],
    'deleteDocument' => true,
    'separator' => ' - '
]);
```

## Dependencies

- CakePHP 4.0+
- PHP 8.0+
- cakephp/elastic-search: 3.2.1
- PHPUnit for testing