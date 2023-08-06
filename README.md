# Craft Prompt DB

An experimental use of ChatGPT to turn natural language into database queries and Yii and Craft code examples.

## Requirements

- This plugin requires Craft CMS 4.4.0 or later, and PHP 8.0.2 or later.
- This plugin uses PHPs YAML extension to create a very compact database schema file DDL.

On macOS, you can install PHPs YAML extension like this

```bash
brew install libyaml # installs libyaml
sudo find /opt/homebrew/opt -name libyaml -d | pbcopy # copies libyaml path to clipboard
pecl install yaml # when prompted for yaml path, paste the path from the clipboard
```

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
