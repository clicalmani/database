# Tonka PHP Framework Database ORM

![Tonka Logo](https://clicalmani.github.io/tonka/logo-dark.png)

## Overview

The Tonka PHP Framework Database ORM (Object-Relational Mapping) provides a powerful and efficient way to interact with databases in PHP applications. It simplifies database operations by allowing you to work with PHP objects instead of writing raw SQL queries.

## Features

- **Active Record Implementation**: Easy to map database tables to PHP classes.
- **Query Builder**: Build complex queries using a fluent interface.
- **Relationships**: Support for one-to-one, one-to-many, and many-to-many relationships.
- **Migration Support**: Manage database schema changes with ease.
- **Transactions**: Handle database transactions effectively.

## Installation

You can install the Tonka ORM via Composer:

```bash
composer require clicalmani/database
```

## Usage

Here’s a basic example of how to use the Elegant ORM:

### Defining a Model

```php
use Clicalmani\Database\Factory\Models\Elegant;

class User extends Elegant
{
    /**
     * Model database table 
     *
     * @var string $table Table name
     */
    protected $table = "users";

    /**
     * Model entity
     * 
     * @var string
     */
    protected string $entity = \Database\Entities\UserEntity::class;

    /**
     * Table primary key(s)
     * Use an array if the key is composed with more than one attributes.
     *
     * @var string|array $primary_keys Table primary key.
     */
    protected $primaryKey = "id";

    public function __construct($id = null)
    {
        parent::__construct($id);
    }
}
```

### Creating a Record

```php
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();
```

### Querying Records

```php
$users = User::where('active = ?', [1])->fetch();
foreach ($users as $user) {
    echo $user->name;
    echo $user->isOnline();
}
```

### Relationships

#### One-to-Many Relationship

```php
<?php

namespace App\Models;

use Clicalmani\Database\Factory\Models\Elegant;

class Post extends Elegant
{
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
```

**Database Schema**

```php
namespace Database\Entities;

use Clicalmani\Database\DataTypes\Integer;
use Clicalmani\Database\DataTypes\VarChar;
use Clicalmani\Database\DataTypes\Text;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;

class PostEntity extends Entity
{
    #[Property(
        length: 10,
        unsigned: true,
        nullable: true,
        autoIncrement: true
    ), PrimaryKey]
    public Integer $id;

    #[Property(
        length: 255,
        nullable: false
    )]
    public VarChar $title;

    #[Property(
        length: 255,
        nullable: false
    )]
    public Text $content;
}
```

```php
namespace Database\Entities;

use Clicalmani\Database\DataTypes\Integer;
use Clicalmani\Database\DataTypes\VarChar;
use Clicalmani\Database\DataTypes\Text;
use Clicalmani\Database\Factory\Entity;
use Clicalmani\Database\Factory\PrimaryKey;
use Clicalmani\Database\Factory\Property;
use Clicalmani\Database\Factory\Index;

#[Index(
    name: 'fk_comments_posts1_idx',
    key: 'post_id',
    constraint: 'fk_comments_posts1',
    references: \App\Models\Post::class
)]
class CommentEntity extends Entity
{
    #[Property(
        length: 10,
        unsigned: true,
        nullable: true,
        autoIncrement: true
    ), PrimaryKey]
    public Integer $id;

    #[Property(
        length: 255,
        nullable: false
    )]
    public Text $content;

    #[Property(
        length: 10,
        unsigned: true,
        nullable: false
    )]
    public Integer $post_id;
}
```

#### Migration

To perform a migration in Tonka, you need to create entities that represent the changes you want to make to your database schema. These entities are defined using Tonka's schema definition language, which allows you to specify the structure and relationships of your data. Once you have defined your entities, you can generate migration files that will apply these changes to your database. Each migration file contains the necessary instructions to update your database schema in a controlled and reversible manner.

The generated migration files are saved in the `database/migrations` directory. This directory serves as a version-controlled repository of all the changes made to your database schema. By organizing your migrations in this way, Tonka ensures that you can easily track and manage the evolution of your database over time. Additionally, if you need to roll back a migration, Tonka provides tools to reverse the changes, helping you maintain data integrity and minimize downtime during updates.

To perform a fresh database migration, use the `migrate:fresh` command in the console:

```bash
php tonka migrate:fresh migration-file-name
```

The `migrate:fresh` command also supports additional options such as `seed` and `creating routines`. You can use the following options:

- `--seed`: Automatically seed the database after running the migrations.
- `--create-routines`: Create necessary routines after running the migrations.
- `migration-file-name` is the name of the migration file for version control.

```bash
php tonka migrate:fresh migration-file-name --seed --create-routines
```

To save the generated SQL statement, you can specify the `--output` option with the `migrate:fresh` command. This option allows you to define the directory where the SQL statements will be saved.

Example usage:

```bash
php tonka migrate:fresh migration-file-name --output=output-file-name
```

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

1. Fork the repository.
2. Create your feature branch: git checkout -b feature/my-new-feature
3. Commit your changes: git commit -m 'Add some feature'
4. Push to the branch: git push origin feature/my-new-feature
5. Open a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.