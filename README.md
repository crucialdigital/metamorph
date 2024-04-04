# Crucial Digital Metamorph

[![Latest Version on Packagist](https://img.shields.io/packagist/v/crucialdigital/metamorph.svg?style=flat-square)](https://packagist.org/packages/crucialdigital/metamorph)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/crucialdigital/metamorph/run-tests?label=tests)](https://github.com/crucialdigital/metamorph/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/crucialdigital/metamorph/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/crucialdigital/metamorph/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/crucialdigital/metamorph.svg?style=flat-square)](https://packagist.org/packages/crucialdigital/metamorph)

Metamorph is a Laravel package that implements a data model system based on mongodb.
This package provides a powerful system for managing dynamically models for api development.  

Before going any further, consider that this package is intended for API development with Laravel and Mongodb
## Table of contents
- [Installation](#installation)
- [Usage](#usage)
  - [Creating Model, Repository and data model](#creating-model-repository-and-data-model)
  - [Configure data model into metamorph config file](#configure-data-model-into-metamorph-config-file)
  - [Run your data models](#run-your-data-models)
  - [Make API requests](#make-api-requests)
- [Advanced](#advanced) 
  - [Global Middleware](#global-middleware)
  - [Model Middleware](#model-middleware)
  - [Policies](#policies)
- [Changelog](#changelog)  
- [Contributing](#contributing)  
- [Security Vulnerabilities](#security-vulnerabilities)  
- [Credits](#credits)  
- [License](#license)  


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

### Creating Model Repository and data model

Create your data model files with artisan command:
```bash
php artisan metamorph:make-model Post -R
```
This command will create three files:  

* Eloquent model file

```xpath 
app/Models/Post.php
```  
Is Laravel Eloquent model extends from `CrucialDigital\Metamorph\BaseModel.php` class. 
You are free to create your model with [Laravel syntax](https://laravel.com/docs/11.x/eloquent#generating-model-classes)
and extends `CrucialDigital\Metamorph\BaseModel.php`
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

### Configure data model into metamorph config file

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
### Run your data models
After creating your data models in .json files, you have to persist into your database with artisan command.
```bash
php artisan metamorph:models
```
This artisan command persists data models into the database. Every time you
modify .json file in `database\models`, update data with this command. You can specify the name of the .json file with `--name` parameter 

Consider configuring the mongodb database connection before.

### Make API requests

Metamorph provides various endpoint de Create, Read, Update en Delete. 
Available endpoint are :

| Methods   | Endpoints                              | Description                    | Parameters                                                    |
|:----------|:---------------------------------------|:-------------------------------|:--------------------------------------------------------------|
| POST      | api/metamorph/exports/{entity}/{form}  | Export data with selected form | `entity`: mapped model entity<br/> `form`: selected data form |
| GET, HEAD | api/metamorph/form-data                |                                |                                                               |
| POST      | api/metamorph/form-data                |                                |                                                               |
| GET,HEAD  | api/metamorph/form-data/{form_datum}   |                                |                                                               |
| PUT,PATCH | api/metamorph/form-data/{form_datum}   |                                |                                                               |
| DELETE    | api/metamorph/form-data/{form_datum}   |                                |                                                               |
| POST      | api/metamorph/form-inputs              |                                |                                                               |
| GET,HEAD  | api/metamorph/form-inputs/{form_input} |                                |                                                               |
| PUT,PATCH | api/metamorph/form-inputs/{form_input} |                                |                                                               |
| DELETE    | api/metamorph/form-inputs/{form_input} |                                |                                                               |
| POST      | api/metamorph/form/{entity}            |                                |                                                               |
| GET,HEAD  | api/metamorph/forms                    |                                |                                                               |
| POST      | api/metamorph/forms                    |                                |                                                               |
| GET,HEAD  | api/metamorph/forms/{form}             |                                |                                                               |
| PUT,PATCH | api/metamorph/forms/{form}             |                                |                                                               |
| DELETE    | api/metamorph/forms/{form}             |                                |                                                               |
| POST      | api/metamorph/many/search              |                                |                                                               |
| POST      | api/metamorph/master/{entity}          | Create model entry             | `entity`: mapped model entity                                 |
| GET,HEAD  | api/metamorph/master/{entity}/{id}     | Get model entry                | `entity`: mapped model entity <br/> `id`: model entity id     |
| PUT,PATCH | api/metamorph/master/{entity}/{id}     | Update model entry             | `entity`: mapped model entity <br/> `id`: model entity id     |
| DELETE    | api/metamorph/master/{entity}/{id}     | Delete model entry             | `entity`: mapped model entity <br/> `id`: model entity id     |
| PATCH     | api/metamorph/reject/form-data/{id}    |                                |                                                               |
| POST      | api/metamorph/resources/entities       |                                |                                                               |
| POST      | api/metamorph/resources/entity/        |                                |                                                               |
| POST      | api/metamorph/search/{entity}          | Lists models entries           | [See table below](#model-entry-list-request-parameters)       |
| POST      | api/metamorph/validate/form-data/{id}  |                                |                                                               |

<br />

#### Model entry list request parameters

<br />


| Parameter         | Description                                                                                                                                                                                                                                                                                                                                                                                            | Parameter type | Value type     | Default value |
|:------------------|:-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:---------------|:---------------|:--------------|
| `entity`          | mapped model entity                                                                                                                                                                                                                                                                                                                                                                                    | string         | url part param | _created_at_  |
| `paginate`        | whether paginate request or not _i.e: 0,1_                                                                                                                                                                                                                                                                                                                                                             | string, int    | query param    | _1_           |
| `per_page`        | number of element per page _default                                                                                                                                                                                                                                                                                                                                                                    | int            | query param    | _15_          |
| `order_by`        | order field                                                                                                                                                                                                                                                                                                                                                                                            | string         | query param    | _created_at_  |
| `order_direction` | order direction `order_by` _i.e : ASC, DESC_                                                                                                                                                                                                                                                                                                                                                           | string         | query param    | _ASC_         |
| `randomize`       | whether result is randomize __Incompatible with paginate__                                                                                                                                                                                                                                                                                                                                             | int, string    | query param    | _0_           |
| `with_trash`      | whether result is with trashed entries                                                                                                                                                                                                                                                                                                                                                                 | int, string    | query param    | _0_           |
| `only_trash`      | whether result is only trashed entries                                                                                                                                                                                                                                                                                                                                                                 | int, string    | query param    | _0_           |
| `search`          | search criteria for the request <br/> _i.e: ```[{field: 'title', operator: 'like', value: 'lorem' }]```_ <br/> *available operator:*<br/> _=, !=, <, >, like, in, all, exists, elemMatch, size, regexp, type, mod, near, geoWithin, geoIntersects_<br/> **See [Mongodb query and projection operators documentation](https://www.mongodb.com/docs/rapid/reference/operator/query/) for more operator** | object[ ]      | query param    | _[ ]_         |
| `filter`          | Same as search                                                                                                                                                                                                                                                                                                                                                                                         | object[ ]      | query param    | _[ ]_         |


## Advanced

### Global Middleware

To define global middleware for all metamorph routes, in metamorph config file, `config/metamorph.php`
fill the ``middlewares`` array with your middlewares
```
//config/metamorph.php
...
'middlewares' => ['auth:sanctum', 'verified'],
...
```
>If you are using [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum) for authentification,
>don't forget to add the middleware `auth:sanctum` to avoid trouble with [Metamorph authorisation system](#policies)

### Model Middleware
Beyond global middleware you can't define individual middleware for every model route and for each controller action
in metamorph config file, `config/metamorph.php`
fill the ``model_middlewares`` array with your middlewares
```
//config/metamorph.php
...
'model_middlewares' => [
    'post' => [
        'App\Http\Middleware\EnsureUserIsOwner::class' => '*', //Protect all CRUD action with the middleware for posts
        'isOwner' => ['destroy', 'update'] //Prevent non owner from deleting and updating posts
    ]
],
...
```
### Policies
To authorize model controller action with police authorization,
in metamorph config file, `config/metamorph.php`
fill the ``policies`` array with the policy actions associate with your models
```
//config/metamorph.php
...
'policies' => [
    'post' => ['viewany', 'view', 'create', 'update', 'delete'],
    'user' => ['viewany', 'view', 'create', 'update', 'delete'],
    ...
 ],
...
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
