# Crucial Digital Metamorph

[![Latest Version on Packagist](https://img.shields.io/packagist/v/crucialdigital/metamorph.svg?style=flat-square)](https://packagist.org/packages/crucialdigital/metamorph)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/crucialdigital/metamorph/run-tests?label=tests)](https://github.com/crucialdigital/metamorph/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/crucialdigital/metamorph/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/crucialdigital/metamorph/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/crucialdigital/metamorph.svg?style=flat-square)](https://packagist.org/packages/crucialdigital/metamorph)

**Metamorph** is a Laravel package (v3.x) that provides a complete, schema-driven REST API layer on top of **MongoDB**. Instead of hand-writing controllers, form requests, and routes for every resource, you describe your data models once — in a JSON file — and Metamorph automatically exposes a fully functional CRUD API with filtering, pagination, search, file upload, export, and policy enforcement.

## Key features

| Feature | Description |
|---|---|
| **JSON Data Models** | Declare form structure, field types, and validation rules in a single `.json` file |
| **Auto CRUD API** | `POST / GET / PUT / PATCH / DELETE` endpoints generated automatically per entity |
| **Advanced Querying** | 25+ filter operators (`=`, `like`, `in`, `between`, `geoWithin`, …) with `and`/`or` grouping |
| **Soft Delete & Restore** | Built-in support for `withTrashed`, `onlyTrashed`, `restore`, and `forceDelete` |
| **File & Photo Upload** | Automatic upload handling, storage, and thumbnail generation via Intervention Image |
| **Data Export** | Export any entity to CSV, XLSX, XLS, ODS, or PDF via Maatwebsite Excel |
| **Dynamic Relations** | Eager-load any Eloquent relation on the fly using the `relations` query parameter |
| **Repository Pattern** | Override the default query builder per entity with a custom `DataRepositoryBuilder` |
| **Middleware & Policies** | Apply global or per-model middleware and Laravel Gate policies from the config file |
| **Artisan Scaffolding** | Generate models, repositories, and data model stubs with a single command |
| **Cache Service** | Optional tenant-aware, per-entity result caching with Redis or tag-based invalidation |

## Requirements

- PHP **^8.1**
- Laravel **^10 \| ^11 \| ^12**
- MongoDB driver — [`mongodb/laravel-mongodb`](https://github.com/mongodb/laravel-mongodb) **^5.4**
- A configured MongoDB connection in your Laravel application

> **Note** — This package is designed exclusively for **API development with Laravel and MongoDB**. It does not support SQL databases.
## Table of contents

- [Installation](#installation)
- [Usage](#usage)
  - [Step 1 — Scaffold files with Artisan](#step-1--scaffold-files-with-artisan)
  - [Step 2 — The Eloquent Model (BaseModel)](#step-2--the-eloquent-model-basemodel)
  - [Step 3 — The Data Repository](#step-3--the-data-repository)
  - [Step 4 — The JSON Data Model](#step-4--the-json-data-model)
  - [Step 5 — Register in config/metamorph.php](#step-5--register-in-configmetamorphphp)
  - [Step 6 — Sync data models to the database](#step-6--sync-data-models-to-the-database)
  - [Make API requests](#make-api-requests)
    - [1. Entity CRUD — master/{entity}](#1-entity-crud--masterentity)
    - [2. Search & List — search/{entity}](#2-search--list--searchentity)
    - [3. Bulk Resource Lookup — many/search](#3-bulk-resource-lookup--manysearch)
    - [4. Data Export — exports/{entity}/{form}](#4-data-export--exportsentityform)
    - [5. Form Management — forms](#5-form-management--forms)
    - [6. Form Inputs — form-inputs](#6-form-inputs--form-inputs)
    - [7. Form Data (Draft Workflow) — form-data](#7-form-data-draft-workflow--form-data)
    - [8. Resources — resources](#8-resources--resources)
- [Advanced](#advanced)
  - [Global Middleware](#global-middleware)
  - [Model Middleware](#model-middleware)
  - [Policies](#policies)
  - [Cache Service](#cache-service)
  - [File & Photo Upload](#file--photo-upload)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Installation

### Prerequisites

Before installing Metamorph, make sure your Laravel application has:

- A **MongoDB** instance accessible from your server
- The **PHP MongoDB extension** installed (`ext-mongodb`)
- The **`mongodb/laravel-mongodb`** package configured as your default or secondary database connection

If you have not set up the MongoDB driver yet, follow the [official Laravel MongoDB documentation](https://www.mongodb.com/docs/drivers/php/laravel-mongodb/).

---

### 1. Install via Composer

```bash
composer require crucialdigital/metamorph
```

The service provider `MetamorphServiceProvider` is automatically registered via Laravel's package discovery — no manual registration needed.

---

### 2. Configure MongoDB connection

In your `.env` file, add (or update) the MongoDB connection variables:

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=your_database_name
DB_USERNAME=
DB_PASSWORD=
```

Then ensure `config/database.php` includes a MongoDB connection entry:

```php
// config/database.php
'connections' => [

    'mongodb' => [
        'driver'   => 'mongodb',
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', 27017),
        'database' => env('DB_DATABASE', 'homestead'),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
        'options'  => [],
    ],

],

'default' => env('DB_CONNECTION', 'mongodb'),
```

---

### 3. Publish the configuration file

```bash
php artisan vendor:publish --tag="metamorph-config"
```

This creates `config/metamorph.php` in your application with all available options and their default values. See [Step 5 — Register in config/metamorph.php](#step-5--register-in-configmetamorphphp) for a full reference.

---

### 4. Run the install command *(optional)*

```bash
php artisan metamorph:install
```

This command creates the required directories (`database/models`, `app/Repositories`) if they do not already exist and verifies that the MongoDB connection is reachable.

---

### Package dependencies

Metamorph automatically pulls the following packages via Composer:

| Package | Version | Purpose |
|:--------|:--------|:--------|
| `mongodb/laravel-mongodb` | `^5.4` | MongoDB Eloquent driver |
| `intervention/image` | `^2.7` | Photo resizing & thumbnail generation |
| `maatwebsite/excel` | `^3.1` | CSV / XLSX / PDF data export |
| `spatie/laravel-package-tools` | `^1.13` | Package service provider utilities |

## Usage

The typical workflow for adding a new resource to your API has four steps:

```
1. Scaffold  →  2. Configure JSON data model  →  3. Register in config  →  4. Sync to database
```

---

### Step 1 — Scaffold files with Artisan

The fastest way to get started is the all-in-one command:

```bash
# Creates the Eloquent model, its DataRepository AND the JSON data model in one shot
php artisan metamorph:make-model Post -R
```

This generates **three files**:

| File | Path | Purpose |
|:-----|:-----|:--------|
| Eloquent Model | `app/Models/Post.php` | MongoDB Eloquent model extending `BaseModel` |
| Repository | `app/Repositories/PostRepository.php` | Custom query builder |
| Data Model | `database/models/post.json` | JSON form definition |

You can also scaffold each file individually:

```bash
# Model only
php artisan metamorph:make-model Post

# Repository only (model name is required)
php artisan metamorph:make-repository PostRepository --model=Post

# JSON data model only
php artisan metamorph:make-data-model Post
```

---

### Step 2 — The Eloquent Model (`BaseModel`)

Every Metamorph model must extend `CrucialDigital\Metamorph\Models\BaseModel` and implement two abstract methods:

```php
<?php

namespace App\Models;

use CrucialDigital\Metamorph\Models\BaseModel;

class Post extends BaseModel
{
    /**
     * Fields used for full-text search via the `term` query parameter.
     * These are matched with a LIKE query.
     */
    public static function searchField(): array
    {
        return ['title', 'content'];
    }

    /**
     * The model attribute used as the human-readable label
     * when this model is referenced by another entity (resource / selectresource).
     */
    public static function label(): string
    {
        return 'title';
    }

    // Optional overrides ↓

    /**
     * The attribute returned as the `value` in resource dropdowns.
     * Defaults to 'id' if not overridden.
     */
    public static function labelValue(): string
    {
        return 'id';
    }

    /**
     * Extra dot-notation fields to include in data exports
     * beyond what the data model JSON defines.
     * e.g. 'author.name' to resolve a nested relation field.
     */
    public static function exportsFields(): array
    {
        return ['author.name'];
    }

    /**
     * Additional fields accepted by the CRUD endpoints
     * that are not declared in the JSON data model.
     */
    public static function extraFields(): array
    {
        return ['internal_ref'];
    }
}
```

`BaseModel` automatically:
- Sets `$primaryKey = 'id'` and appends it to serialized output
- Enables `timestamps` (`created_at`, `updated_at`, `deleted_at`)
- Serializes embedded `EmbedsOne` / `EmbedsMany` relations correctly
- Derives the MongoDB collection name from the class name (snake_plural) unless `$collection` is explicitly set

---

### Step 3 — The Data Repository

A **repository** is optional but recommended. It lets you customize the base query builder used by all search, list, and export endpoints for a given entity — for example, to add default scopes, tenant isolation, or joins.

```php
<?php

namespace App\Repositories;

use App\Models\Post;
use CrucialDigital\Metamorph\DataRepositoryBuilder;
use MongoDB\Laravel\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PostRepository extends DataRepositoryBuilder
{
    /**
     * Return the base query builder for the Post entity.
     * Metamorph will apply all filters, search, pagination, and ordering on top of this.
     */
    public function builder(): Builder
    {
        // Example: always scope to the authenticated user's school
        return Post::where('ecole_id', Auth::user()->ecole_id);
    }
}
```

> If no repository is registered for an entity, Metamorph falls back to `Model::where('id', 'exists', true)`.

---

### Step 4 — The JSON Data Model

The JSON file at `database/models/{entity}.json` is the central schema for an entity. It describes every form field, its validation rules, and the columns to display in list views.

#### Full schema reference

```json
{
    "name":     "Post form",
    "ref":      "post",
    "entity":   "post",
    "readOnly": false,
    "inputs": [ ],
    "columns":  ["title", "author.name", "created_at"]
}
```

| Field      | Required | Type    | Description |
|:-----------|:---------|:--------|:------------|
| `entity`   | ✅        | string  | Unique entity key. Must match the key used in `config/metamorph.php` |
| `inputs`   | ✅        | array   | List of form field definitions (see below) |
| `name`     | ❌        | string  | Human-readable form name |
| `ref`      | ❌        | string  | Unique slug used as the database key for `updateOrCreate` sync |
| `readOnly` | ❌        | boolean | If `true`, the form cannot be edited via the forms API |
| `columns`  | ❌        | array   | Default list of fields returned by the show endpoint (dot-notation supported) |

#### Input field definition

Each object in the `inputs` array has the following properties:

| Property      | Required | Type    | Description |
|:--------------|:---------|:--------|:------------|
| `field`       | ✅        | string  | The MongoDB document attribute name |
| `name`        | ✅        | string  | Human-readable label (used in validation error messages and export headers) |
| `type`        | ✅        | string  | Input type — see the full list below |
| `required`    | ❌        | boolean | If `true`, adds Laravel `required` rule; otherwise adds `nullable` |
| `readOnly`    | ❌        | boolean | Informational flag for frontend rendering |
| `hidden`      | ❌        | boolean | Informational flag for frontend rendering |
| `unique`      | ❌        | boolean | Adds a `unique:{ModelClass},{field}` validation rule |
| `min`         | ❌        | int     | Adds Laravel `min:{n}` rule |
| `max`         | ❌        | int     | Adds Laravel `max:{n}` rule |
| `description` | ❌        | string  | Helper text for the frontend |
| `placeholder` | ❌        | string  | Placeholder text for the frontend |
| `options`     | ❌*       | array   | **Required for `select` type.** Array of `{ "label": "…", "value": "…" }` objects |
| `entity`      | ❌*       | string  | **Required for `resource` and `selectresource` types.** The referenced entity key |
| `rules`       | ❌        | object/string | Extra Laravel validation rules. Can be a pipe-separated string or an object `{ "store": "…", "update": "…" }` for separate create/update rules |

> You may add any additional custom keys to an input object (e.g. `"icon"`, `"group"`, `"hint"`). They are stored and returned as-is, allowing your frontend to consume them freely.

#### Supported input types

| Type             | Validation applied            | Notes |
|:-----------------|:------------------------------|:------|
| `text`           | `string`                      | |
| `longtext`       | `string`                      | |
| `richtext`       | `string`                      | |
| `address`        | `string`                      | |
| `number`         | `numeric`                     | |
| `currency`       | `numeric`                     | |
| `tel`            | `string`                      | |
| `email`          | `string`, `email`             | |
| `date`           | `date`                        | |
| `datetime`       | `date`                        | |
| `boolean`        | —                             | |
| `radio`          | —                             | |
| `select`         | `in:{option values}`          | Requires `options` array |
| `multiselect`    | `array`                       | |
| `resource`       | `string`                      | Requires `entity`. Stores the referenced document ID |
| `selectresource` | `string`                      | Requires `entity`. Like `resource` but rendered as a select |
| `multiresource`  | `array`                       | Requires `entity`. Stores an array of IDs |
| `file`           | `file`, `max:2048` (KB)       | Stored via Laravel Storage |
| `photo`          | `file`, `max:1536` (KB)       | Resized to max 1000px height; thumbnail generated at 300px |
| `geopoint`       | Custom `GeoPointRule`         | Expects a `"lat,lng"` string |
| `polygon`        | `array`                       | Array of coordinate pairs |
| `stringArray`    | `array`                       | |

#### Complete example

```json
{
    "name":     "Blog Post",
    "ref":      "post",
    "entity":   "post",
    "readOnly": false,
    "inputs": [
        {
            "field":       "title",
            "type":        "text",
            "name":        "Title",
            "placeholder": "Enter post title",
            "required":    true,
            "unique":      true
        },
        {
            "field":   "status",
            "type":    "select",
            "name":    "Status",
            "required": true,
            "options": [
                { "label": "Draft",     "value": "draft" },
                { "label": "Published", "value": "published" }
            ]
        },
        {
            "field":       "author_id",
            "type":        "resource",
            "entity":      "user",
            "name":        "Author",
            "placeholder": "Select an author",
            "required":    true
        },
        {
            "field":    "cover",
            "type":     "photo",
            "name":     "Cover image",
            "required": false
        },
        {
            "field":    "published_at",
            "type":     "datetime",
            "name":     "Published at",
            "required": false,
            "rules":    { "store": "required_if:status,published", "update": "nullable" }
        }
    ],
    "columns": ["title", "status", "author.name", "published_at"]
}
```

---

### Step 5 — Register in `config/metamorph.php`

Map each entity key to its Eloquent model class and (optionally) its repository class. The entity key **must be identical** across the config, the JSON `entity` field, and all API routes.

```php
// config/metamorph.php
return [

    // Global API route prefix  →  api/metamorph/...
    'route_prefix' => 'metamorph',

    // Directory where .json data models are stored
    'data_model_base_dir' => database_path('models'),

    // Eloquent model registry: entity key => FQCN
    'models' => [
        'post' => \App\Models\Post::class,
        'user' => \App\Models\User::class,
    ],

    // Repository registry: entity key => FQCN (optional per entity)
    'repositories' => [
        'post' => \App\Repositories\PostRepository::class,
    ],

    // Entities available as resource dropdowns in data model inputs
    'resources' => [
        ['label' => 'Users',      'entity' => 'user'],
        ['label' => 'Categories', 'entity' => 'category'],
    ],

    // Global middleware applied to all Metamorph routes
    'middlewares' => ['auth:sanctum'],

    // Per-model middleware (see Advanced section)
    'model_middlewares' => [],

    // Laravel Gate policies to enforce per model (see Advanced section)
    'policies' => [],

    // Upload path prefix inside Laravel Storage
    'upload_path' => env('APP_NAME', 'metamorph'),

    // Redis cache groups per entity (see Advanced section)
    'caches' => [],
];
```

---

### Step 6 — Sync data models to the database

After creating or modifying any `.json` file in `database/models`, run:

```bash
# Sync all data models
php artisan metamorph:models

# Sync a single model by file name (without .json extension)
php artisan metamorph:models --name=post
```

This command performs an **upsert** on `MetamorphForm` and `MetamorphFormInput` records in MongoDB. Run it every time you change a `.json` file — it is idempotent and safe to re-run.

> **Important** — Configure your MongoDB database connection in `config/database.php` before running this command.

### Make API requests

Metamorph exposes a REST API under the `api/metamorph` prefix (configurable via `route_prefix`). All routes are protected by the global middleware defined in `config/metamorph.php`. Routes are grouped into four logical areas:

---

#### 1. Entity CRUD — `master/{entity}`

These are the core write endpoints. Each request must include a `form_id` (or `entity`) field in the body so that Metamorph can resolve the correct data model and apply its validation rules.

| Method    | Endpoint                                          | Action                  | Auth / Policy         |
|:----------|:--------------------------------------------------|:------------------------|:----------------------|
| `POST`    | `api/metamorph/master/{entity}`                   | Create a new record     | Gate: `create`        |
| `GET`     | `api/metamorph/master/{entity}/{id}`              | Fetch a single record   | Gate: `view`          |
| `PUT/PATCH` | `api/metamorph/master/{entity}/{id}`            | Update a record         | Gate: `update`        |
| `DELETE`  | `api/metamorph/master/{entity}/{id}`              | Soft-delete a record    | Gate: `delete`        |
| `DELETE`  | `api/metamorph/master/force-delete/{entity}/{id}` | Permanently delete      | Gate: `forceDelete`   |
| `POST`    | `api/metamorph/master/restore/{entity}/{id}`      | Restore a soft-deleted record | Gate: `restore` |

**Create / Update request body**

```json
{
  "form_id": "<metamorph_form_id>",
  "entity": "post",
  "title": "Hello World",
  "category": "news",
  "author_id": "<user_id>"
}
```

> The body fields are validated dynamically against the rules declared in the data model (`.json`).  
> `form_id` or `entity` is **required** — it is used to locate the form definition.

**Show response** — in addition to all model attributes, a `meta_data` array is appended that resolves the human-readable labels for every `resource` / `selectresource` field:

```json
{
  "id": "664abc...",
  "title": "Hello World",
  "author_id": "664xyz...",
  "meta_data": [
    { "label": "author_id", "value": "John Doe" }
  ]u
}
```

**Additional query parameters for `GET {entity}/{id}`**

| Parameter   | Type   | Description                                          |
|:------------|:-------|:-----------------------------------------------------|
| `columns`   | string | Pipe-separated list of fields to return. e.g. `title\|created_at` |
| `relations` | string | Comma-separated Eloquent relations to eager-load. e.g. `comments,author` |

---

#### 2. Search & List — `search/{entity}`

`POST api/metamorph/search/{entity}`

Returns a **paginated** (or flat) list of records for the given entity. Uses the entity's `DataRepositoryBuilder` if registered, otherwise queries the model directly.

**Request body**

```json
{
  "filters": [
    { "field": "status", "operator": "=", "value": "active" },
    { "field": "created_at", "operator": "dateafter", "value": "2024-01-01" }
  ],
  "search": [],
  "per_page": 20,
  "page": 1,
  "order_by": "created_at",
  "order_direction": "DESC",
  "relations": ["author"],
  "columns": ["*"]
}
```

**Paginated response**

```json
{
  "current_page": 1,
  "data": [ { "id": "...", "title": "..." } ],
  "per_page": 20,
  "total": 145,
  "last_page": 8,
  "next_page_url": "...",
  "prev_page_url": null
}
```

Set `paginate=0` in the query string to get a flat array instead of a paginated response.

##### Search & filter parameters

| Parameter         | Type        | Default      | Description |
|:------------------|:------------|:-------------|:------------|
| `term`            | string      | —            | Full-text search term; matched against the fields declared in `searchField()` of the model using `LIKE` |
| `paginate`        | int / bool  | `1`          | `1` = paginated, `0` = flat collection |
| `per_page`        | int         | `15`         | Number of records per page |
| `limit`           | int         | —            | Hard limit on results (non-paginated) |
| `order_by`        | string      | `created_at` | Field(s) to sort by. Use `\|` to separate multiple fields: `name\|created_at` |
| `order_direction` | string      | `ASC`        | Direction(s) matching `order_by`. Use `\|` separator: `ASC\|DESC` |
| `randomize`       | int / bool  | `0`          | Return a random sample of `per_page` records (uses MongoDB `$sample`). **Incompatible with `paginate`** |
| `with_trash`      | int / bool  | `0`          | Include soft-deleted records in results |
| `only_trash`      | int / bool  | `0`          | Return only soft-deleted records |
| `columns`         | array       | `['*']`      | Fields to select |
| `relations`       | array/string| —            | Comma-separated or array of Eloquent relation names to eager-load |
| `no_cache`        | bool        | `false`      | Force cache invalidation before executing the query |
| `filters`         | object[]    | `[]`         | Advanced filter criteria (see below) |
| `search`          | object[]    | `[]`         | Alias for `filters` |

##### Filter object structure

Each entry in `filters` / `search` is an object with the following shape:

```json
{
  "field": "title",
  "operator": "like",
  "value": "lorem",
  "coordinator": "and",
  "group": "or_mygroup"
}
```

| Key           | Required | Description |
|:--------------|:---------|:------------|
| `field`       | ✅        | The model attribute to filter on. Supports dot-notation for relations: `author.name` |
| `operator`    | ✅        | One of the operators listed below |
| `value`       | ✅        | The value to compare against. Can be a scalar, an array, or a date string |
| `coordinator` | ❌        | `and` (default) or `or` — maps to `where()` / `orWhere()` |
| `group`       | ❌        | Groups filters into a sub-query closure. Must start with `and_` or `or_` (e.g. `and_statusGroup`) |

**Available operators**

| Operator        | SQL equivalent              | Notes |
|:----------------|:----------------------------|:------|
| `=`             | `WHERE field = value`       | |
| `!=`            | `WHERE field != value`      | |
| `<`             | `WHERE field < value`       | |
| `>`             | `WHERE field > value`       | |
| `like`          | `WHERE field LIKE %value%`  | Case-insensitive substring match |
| `in`            | `WHERE field IN (…)`        | `value` must be an array |
| `notin`         | `WHERE field NOT IN (…)`    | `value` must be an array |
| `between`       | `WHERE field BETWEEN a AND b` | `value` must be `[a, b]` |
| `notbetween`    | `WHERE field NOT BETWEEN a AND b` | |
| `date`          | `WHERE DATE(field) = value` | |
| `datebefore`    | `WHERE DATE(field) < value` | |
| `dateafter`     | `WHERE DATE(field) > value` | |
| `datebeforeq`   | `WHERE DATE(field) <= value`| |
| `dateaftereq`   | `WHERE DATE(field) >= value`| |
| `datenot`       | `WHERE DATE(field) != value`| |
| `datebetween`   | Date range inclusive        | `value`: `["2024-01-01","2024-12-31"]` |
| `datenotbetween`| Inverse date range          | |
| `datetimebefore` / `datetimeafter` / `datetimebeforeq` / `datetimeaftereq` | Full datetime comparisons | |
| MongoDB operators: `all`, `exists`, `elemMatch`, `size`, `regexp`, `type`, `mod`, `near`, `geoWithin`, `geoIntersects` | — | Passed directly to the MongoDB driver |

> See the [MongoDB query operators documentation](https://www.mongodb.com/docs/rapid/reference/operator/query/) for the full list.

**Filter grouping example** — find active records whose title or description contains "laravel":

```json
{
  "filters": [
    { "field": "status",      "operator": "=",    "value": "active",  "coordinator": "and" },
    { "field": "title",       "operator": "like",  "value": "laravel", "coordinator": "or", "group": "or_keyword" },
    { "field": "description", "operator": "like",  "value": "laravel", "coordinator": "or", "group": "or_keyword" }
  ]
}
```

---

#### 3. Bulk Resource Lookup — `many/search`

`POST api/metamorph/many/search`

Resolves the human-readable labels for a set of resource IDs across multiple entities in a single request. Useful for displaying related resource names without extra round-trips.

**Request body**

```json
{
  "resources": [
    { "entity": "user",     "field": "created_by", "value": "664abc,664def" },
    { "entity": "category", "field": "category_id", "value": ["664xyz"] }
  ]
}
```

**Response**

```json
{
  "created_by":  "John Doe, Jane Smith",
  "category_id": "Technology"
}
```

---

#### 4. Data Export — `exports/{entity}/{form}`

`POST api/metamorph/exports/{entity}/{form}`

Exports all records matching the current search/filter parameters to a downloadable file. The form definition is used to map field names to column headers and to resolve resource labels.

| Parameter | Location   | Description |
|:----------|:-----------|:------------|
| `entity`  | URL        | The mapped entity name |
| `form`    | URL        | The `MetamorphForm` ID to use for column mapping |
| `format`  | Body       | Output format: `CSV` (default), `XLSX`, `XLS`, `ODS`, `PDF` |
| + all search/filter params | Body | Same parameters as `search/{entity}` |

```http
POST api/metamorph/exports/post/664formId
Content-Type: application/json

{
  "format": "XLSX",
  "filters": [{ "field": "status", "operator": "=", "value": "published" }]
}
```

---

#### 5. Form Management — `forms`

These endpoints manage the `MetamorphForm` documents stored in MongoDB (the in-database representation of your JSON data models).

| Method      | Endpoint                        | Description |
|:------------|:--------------------------------|:------------|
| `GET`       | `api/metamorph/forms`           | List all forms. Filter by entity with `?type={entity}` |
| `POST`      | `api/metamorph/forms`           | Create a new form |
| `GET`       | `api/metamorph/forms/{form}`    | Get a single form by ID |
| `PUT/PATCH` | `api/metamorph/forms/{form}`    | Update form metadata (`name`, `visibility`, `owners`, `readOnly`) |
| `DELETE`    | `api/metamorph/forms/{form}`    | Delete a form |
| `POST`      | `api/metamorph/form/{entity}`   | Get the latest form for a given entity |

---

#### 6. Form Inputs — `form-inputs`

Manage individual input definitions attached to a form.

| Method      | Endpoint                               | Description |
|:------------|:---------------------------------------|:------------|
| `POST`      | `api/metamorph/form-inputs`            | Create a new input on an existing form |
| `GET`       | `api/metamorph/form-inputs/{id}`       | Fetch a single input |
| `PUT/PATCH` | `api/metamorph/form-inputs/{id}`       | Update an input definition |
| `DELETE`    | `api/metamorph/form-inputs/{id}`       | Delete an input from a form |

---

#### 7. Form Data (Draft Workflow) — `form-data`

`MetamorphFormData` provides a **staging layer** where form submissions can be reviewed and either validated (promoted to a real model record) or rejected before being persisted.

| Method      | Endpoint                                  | Description |
|:------------|:------------------------------------------|:------------|
| `GET`       | `api/metamorph/form-data`                 | List draft submissions. Filter by `?form={form_id}` or `?rejected=1` |
| `POST`      | `api/metamorph/form-data`                 | Submit a new draft |
| `GET`       | `api/metamorph/form-data/{id}`            | Get a single draft |
| `PUT/PATCH` | `api/metamorph/form-data/{id}`            | Update a draft |
| `DELETE`    | `api/metamorph/form-data/{id}`            | Delete a draft |
| `POST`      | `api/metamorph/validate/form-data/{id}`   | **Promote** a draft — creates the final model record and deletes the draft |
| `PATCH`     | `api/metamorph/reject/form-data/{id}`     | **Reject** a draft with a mandatory observation |

**Reject request body**

```json
{
  "rejection_observations": "The submitted address is incomplete (minimum 20 characters required)."
}
```

---

#### 8. Resources — `resources`

Helper endpoints used by frontends to populate `resource` / `selectresource` dropdowns.

| Method | Endpoint                               | Description |
|:-------|:---------------------------------------|:------------|
| `POST` | `api/metamorph/resources/entities`     | List all registered resource entities (from `config('metamorph.resources')`) sorted by label |
| `POST` | `api/metamorph/resources/entity/{name}` | Fetch `{ value, label }` pairs for the given entity, searchable via `?term=` |

**Response of `resources/entity/{name}`**

```json
[
  { "value": "664abc...", "label": "John Doe" },
  { "value": "664def...", "label": "Jane Smith" }
]
```

The `label` field is resolved from the model's `label()` method, and `value` from `labelValue()`.

---

>**NOTE**  
>`field` in filters supports nested dot-notation: e.g. `comments.user.id` — Metamorph will automatically detect whether the relation is an embedded document (`EmbedsOne` / `EmbedsMany`) or a referenced relation and build the correct MongoDB sub-query.  
>`coordinator`: one of `and`, `or` to indicate `where()` / `orWhere()`.  
>`group`: groups filter criteria into a sub-query closure; must start with `and_` or `or_`.

## Advanced

### Global Middleware

Apply middleware to **every** Metamorph route by adding entries to the `middlewares` array in `config/metamorph.php`. These are applied after the built-in `api` middleware group.

```php
// config/metamorph.php
'middlewares' => [
    'auth:sanctum',   // Require authenticated API token
    'verified',       // Require email verification
    'throttle:60,1',  // Rate limit: 60 requests per minute
],
```

> **Note** — If you are using [Laravel Sanctum](https://laravel.com/docs/11.x/sanctum) for authentication, you **must** add `auth:sanctum` here. Without it, the `Gate::authorize()` calls inside Metamorph controllers will always operate as a guest, breaking the [Policies](#policies) system.

---

### Model Middleware

Beyond the global middleware, you can attach middleware to individual entity routes and restrict them to specific controller actions.

```php
// config/metamorph.php
'model_middlewares' => [

    'post' => [
        // Apply to ALL CRUD actions on the "post" entity
        \App\Http\Middleware\EnsureUserIsSubscribed::class => '*',

        // Apply ONLY to destroy and update actions
        \App\Http\Middleware\EnsureUserIsOwner::class => ['destroy', 'update'],
    ],

    'user' => [
        \App\Http\Middleware\AdminOnly::class => ['store', 'destroy'],
    ],
],
```

The **action names** map to the controller methods as follows:

| Config action | Controller method | HTTP verb + route |
|:--------------|:------------------|:------------------|
| `store`       | `store()`         | `POST /master/{entity}` |
| `show`        | `show()`          | `GET /master/{entity}/{id}` |
| `update`      | `update()`        | `PUT/PATCH /master/{entity}/{id}` |
| `destroy`     | `destroy()`       | `DELETE /master/{entity}/{id}` |
| `search`      | `search()`        | `POST /search/{entity}` |
| `*`           | All of the above  | All routes |

---

### Policies

Metamorph integrates with [Laravel Gate / Policy](https://laravel.com/docs/11.x/authorization) authorization. When a policy action is listed for an entity, Metamorph will call `Gate::authorize()` before executing the corresponding controller logic.

**1. Register policy actions in the config**

```php
// config/metamorph.php
'policies' => [
    'post' => ['viewany', 'view', 'create', 'update', 'delete', 'forcedelete', 'restore'],
    'user' => ['viewany', 'view', 'create', 'update', 'delete'],
],
```

**2. Create and register the Policy class**

```bash
php artisan make:policy PostPolicy --model=Post
```

```php
// app/Policies/PostPolicy.php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Everyone authenticated can list postsu
    }

    public function view(User $user, Post $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('editor');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->author_id || $user->hasRole('admin');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->author_id || $user->hasRole('admin');
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->hasRole('admin');
    }
}
```

**3. Register the policy in `AuthServiceProvider`**

```php
// app/Providers/AuthServiceProvider.php
use App\Models\Post;
use App\Policies\PostPolicy;

protected $policies = [
    Post::class => PostPolicy::class,
];
```

**Policy action mapping**

| Config key     | Gate method called           | Triggered by |
|:---------------|:-----------------------------|:-------------|
| `viewany`      | `Gate::authorize('viewAny')` | `POST /search/{entity}` and `POST /exports/...` |
| `view`         | `Gate::authorize('view')`    | `GET /master/{entity}/{id}` |
| `create`       | `Gate::authorize('create')`  | `POST /master/{entity}` |
| `update`       | `Gate::authorize('update')`  | `PUT/PATCH /master/{entity}/{id}` |
| `delete`       | `Gate::authorize('delete')`  | `DELETE /master/{entity}/{id}` |
| `forcedelete`  | `Gate::authorize('forceDelete')` | `DELETE /master/force-delete/{entity}/{id}` |
| `restore`      | `Gate::authorize('restore')` | `POST /master/restore/{entity}/{id}` |

> Only the policy actions **listed** in the config array are enforced. Omit an action to skip its authorization check entirely.

---

### Cache Service

Metamorph ships with a `MetamorphCacheService` class that provides tenant-aware, per-entity result caching. It uses a flexible tenant resolution system, making it safe for multi-tenant applications (where a user can work across multiple schools or tenants).

**Enable and configure caching**

```php
// config/metamorph.php
'cache' => [
    'enabled' => true,
    'ttl'     => 3600, // Global TTL (1 hour)
    
    // Multi-tenant configuration
    // 'auto' checks header first, then user attribute, then 'global'
    'tenant_mode'   => 'auto',
    'tenant_header' => 'X-Tenant-Id',
    'tenant_field'  => 'ecole_id',

    // Enable caching specifically for the following entities
    'entities' => [
        'post'     => true,  // Enabled with global TTL
        'category' => 7200,  // Enabled with specific TTL (2 hours)
        'user'     => false, // Explicitly disabled
    ],
],
```

**Cache key format**

```
metamorph:{tenantId}:{entity}:{md5(normalizedParams)}
```

The cache key is derived from a normalized subset of the request parameters:
`columns`, `filters`, `limit`, `only_trash`, `order_by`, `order_direction`, `page`, `paginate`, `per_page`, `randomize`, `relations`, `search`, `term`, `with_trash`.

**Automatic cache invalidation**

Metamorph automatically invalidates the search cache for a given entity and tenant whenever a `store`, `update`, `destroy`, `delete`, or `restore` operation occurs on that entity.

**Forcing cache invalidation per request**

Pass `no_cache: true` in the search request body to bypass and invalidate the cache before the query runs:

```json
{
  "no_cache": true,
  "filters": [{ "field": "status", "operator": "=", "value": "published" }]
}
```

**Backend requirements**

| Driver  | Invalidation strategy |
|:--------|:----------------------|
| Redis   | Key-pattern scan + `DEL` via `clearRedisPattern()` |
| Others  | Laravel cache tags (`Cache::tags([...])->flush()`) — requires a tag-compatible driver (Memcached, Redis) |

---

### File & Photo Upload

Metamorph handles file and photo uploads automatically for any input of type `file` or `photo`. You do not need to write any upload logic — it is managed inside `Metamorph::mapFormRequestFiles()`.

**How it works**

1. The uploaded file is temporarily moved to `public/tmp/`
2. For `photo` inputs, Intervention Image resizes it to a maximum height of **1000 px** (preserving aspect ratio)
3. The file is then stored via Laravel Storage at:
   ```
   {upload_path}/{entity}/{month-year}/{field}/{filename}.{ext}
   ```
4. For `photo` inputs, a **thumbnail** (max 300 px height) is generated and stored at:
   ```
   {upload_path}/{entity}/{month-year}/{field}/thumbnails/thumbnail_{id}
   ```
5. The temporary file is deleted and the storage paths are saved on the model

**Configure the storage disk**

Metamorph uses Laravel's default `Storage` facade. Make sure your filesystem disk is configured in `config/filesystems.php` and that the disk is publicly accessible if you need to serve files over HTTP.

**File size limits (enforced by validation)**

| Type    | Max size |
|:--------|:---------|
| `file`  | 2048 KB (2 MB) |
| `photo` | 1536 KB (1.5 MB) |

Override these by adding an explicit `rules` key to the input definition in your JSON data model:

```json
{
  "field": "attachment",
  "type": "file",
  "name": "Attachment",
  "rules": "max:5120"
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Creditsu

- [Humbert DJAGLO](https://www.facebook.com/humbert.djaglo)
- [Mawaba BOTOSSI](https://www.facebook.com/tbotossi)
- [All Contributors](../../contributors)

## License
u
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
