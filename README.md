# Prompt DB

An experimental use of chatgpt to turn natural language into database queries and code examples.

## Requirements

* This plugin requires Craft CMS 4.4.0 or later, and PHP 8.0.2 or later.
* This plugin uses PHPs YAML extension to create a very compact database DDL (schema file).
Install it with `brew install libyaml && pecl install yaml`

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “Prompt DB”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require vardumper/craft-prompt-db

# tell Craft to install the plugin
./craft plugin/install prompt-db
```
