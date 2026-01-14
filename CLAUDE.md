# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a CakePHP 5 plugin (`3xw/cakephp-elastic-search`) that provides Elasticsearch integration for CakePHP applications. The plugin enables automatic synchronization between CakePHP ORM entities and Elasticsearch documents, with support for search functionality and index management.

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
   - Handles afterSave and afterDelete events via event listeners
   - Supports internationalization with locale-specific documents (requires `I18n.languages` config)
   - Configurable field mapping and data transformation
   - Key method: `saveOrUpadteES()` handles document creation/updates; `buildQuery()` constructs Elasticsearch queries

2. **SyncRelatedWithESBehavior** (`src/ORM/Behavior/SyncRelatedWithESBehavior.php`)
   - Extends sync functionality for related entities
   - Useful for keeping Elasticsearch documents in sync when associated entities change

3. **ESComponent** (`src/Controller/Component/ESComponent.php`)
   - Controller component for search functionality
   - Handles search requests and query cleaning via `search()` method
   - Manages Elasticsearch HTTP connections using CakePHP's HTTP client
   - Supports query templates with `%needle%` placeholder replacement

4. **ElasticShell** (`src/Shell/ElasticShell.php`)
   - CLI interface for index management
   - Delegates to specific tasks (Create, Delete, Info, Import) in `src/Shell/Task/`

5. **CompletionConstructor** (`src/ORM/CompletionConstructor.php`)
   - Builds Elasticsearch completion field data with context support
   - Used in behavior mapping for autocomplete functionality
   - Supports both field arrays and callable input sources

6. **AssociationsConstructor** (`src/ORM/AssociationsConstructor.php`)
   - Handles indexing of related entity data
   - Supports dot notation for deep associations (e.g., `Users.Profile.Avatar`)
   - Creates nested objects in Elasticsearch documents from CakePHP associations

### Key Utilities

- **Needle** (`src/Utility/Needle.php`): Escapes Elasticsearch special characters in search queries
- **CakeORM** (`src/Utility/CakeORM.php`): Helper functions for dot notation associations, entity extraction, and callable value resolution

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
    'primaryKey' => 'foreign_key',  // Can be string or callable
    'translate' => false,  // Set to property name (e.g., 'locale') for i18n support
    'staticMatching' => ['model' => 'Posts'],  // Static fields added to all documents
    'mapping' => [
        // Completion field with context
        'title' => new \Trois\ElasticSearch\ORM\CompletionConstructor(['title'], [
            'contexts' => ['model' => 'Posts']
        ]),
        // Simple field concatenation
        'content' => ['header', 'content'],
        // Static value or callable
        'status' => 'published',
        // Related entities
        'tags' => new \Trois\ElasticSearch\ORM\AssociationsConstructor([
            'Tags' => ['name' => 'name', 'slug' => 'slug']
        ])
    ],
    'deleteDocument' => true,  // Delete ES document when entity is deleted
    'separator' => ' - '  // Separator for concatenated fields
]);
```

## Important Implementation Details

### Field Mapping Types
The `mapping` configuration accepts three types of values:
1. **Array of field names**: Fields are concatenated with the separator (e.g., `['header', 'content']`)
2. **CompletionConstructor**: For Elasticsearch completion suggester fields with optional contexts
3. **AssociationsConstructor**: For indexing related entity data with support for deep associations
4. **String or callable**: Static value or function that receives the entity

### Internationalization Support
When `translate` is set to a property name (e.g., `'locale'`):
- Requires `I18n.languages` array in CakePHP config
- First language must equal `App.defaultLocale`
- Creates separate documents for each locale
- Uses `_translations` property from entity for translated data

## Dependencies

- CakePHP 5.0+
- PHP 8.0+
- cakephp/elastic-search: ^5.0
- PHPUnit for testing