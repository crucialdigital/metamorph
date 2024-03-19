# Package of data models and dynamic forms managements

[![Latest Version on Packagist](https://img.shields.io/packagist/v/crucialdigital/metamorph.svg?style=flat-square)](https://packagist.org/packages/crucialdigital/metamorph)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/crucialdigital/metamorph/run-tests?label=tests)](https://github.com/crucialdigital/metamorph/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/crucialdigital/metamorph/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/crucialdigital/metamorph/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/crucialdigital/metamorph.svg?style=flat-square)](https://packagist.org/packages/crucialdigital/metamorph)

Metamorph is a Laravel package that implements a data model system based on mongodb.
This package provides a powerful system for managing dynamic forms for api development.  

Before going any further, consider that this package is intended for API development with Laravel and Mongodb

## Installation

You can install the package via composer:

```bash
composer require crucialdigital/metamorph
```

You must now publish the config file with:

```bash
php artisan vendor:publish --tag="metamorph-config"
```

## Usage

### 1. Creating Model, Repository and Data model

Create your data model files with artisan command:
```bash
php artisan metamorph:make-model Post -R
```
This command will create three files:  

* Eloquent model file

```xpath 
app/Models/Post.php 
```  
Is Laravel Eloquent model extends from _CrucialDigital\Metamorph\BaseModel.php_ class. 
You are free to create your model with [Laravel syntax](https://laravel.com/docs/11.x/eloquent#generating-model-classes) 
```bash
php artisan make:model Post
```
* Model repository file

```xpath
app/Repositories/PostRepositories.php
```

Is the repository class responsible for creating the model query builder. You can also create repositories with artisan command

```bash
php artisan metamorph:make-repository PostRepository --model=Post
```

* Data model form file  

```xpath
database/models/post.json
```
The json file describes the form that handles the model with all its inputs.
The json file structure looks like:

```json
{
    "name": "Post form",
    "ref": "post",
    "entity": "post",
    "readOnly": true,
    "inputs": [
        {
            "field": "name",
            "type": "text",
            "name": "Name",
            "description": "Name of the role",
            "placeholder": "Enter the name of the role",
            "required": true,
            "readOnly": true
        },
        {
            "field": "created_by",
            "type": "resource",
            "entity": "user",
            "name": "Create by",
            "description": "Role create by",
            "placeholder": "Select user",
            "required": false,
            "hidden": true,
            "readOnly": true
        }
    ],
    "columns": [
        "name",
        "user.name"
    ]
}
```
where required fields are **entity** and **inputs**.  

Each entry of **inputs** must have at least:  

    * field: The input field
    * name: The label of the input
    * type: The input type in list below
        * text
        * number
        * tel
        * email
        * date
        * datetime
        * radio
        * boolean
        * select
        * textarea
        * url
        * selectresource
        * resource
        * geopoint

For input of type select, **options** is required and is an array of object with **label** and **value**  

For input of type **selectresource** and **resource**, **entity** filed is required. The entity must be unique for the model around your application  

Other field are :
* required: boolean
* hidden: boolean
* readOnly: boolean
* rules: string (Laravel request rules pipe separated)
* description: string
* placeholder: string
* min: int
* max: int
* unique: boolean

>You are free to add any other field to the input that you can use in your frontend application

### 2. Configure data model into metamorph config file

To configure how metamorph maps model with repository, data model form, controller and routes, you have to indicate in metamorph config file
in models and repositories sections respectively the Eloquent model and model repository.  

Example :  

```php
// config/metamorph.php
[
    ....
    'repositories' => [
         'post' => \App\Repositories\PostRepository::class,
         'user' => \App\Repositories\UserRepository::class
    ],
    'models' => [
         'post' => \App\Models\Post::class,
         'user' => \App\Models\User::class
    ]
    ...
]
```



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Humbert DJAGLO](https://www.facebook.com/humbert.djaglo)
- [Mawaba BOTOSSI](https://www.facebook.com/tbotossi)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
