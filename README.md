# Laravel 5 Mail Extras

*Add some extra functionality to your mail flow*

This package extends Laravel 5 Mail by adding:

* Attempt to retry sending mail X times

I'm sure I will be adding more in the future, but that's it for now!

## Installation

Require this package  

```php
composer require "foxxmd/laravel-mail-extras"
```

After adding the package, add the ServiceProvider to the providers array in `config/app.php`

```php
\FoxxMD\LaravelMailExtras\LaravelMailExtrasServiceProvider::class,
```

## Configuration

Simply add this new key/value anywhere in `config/mail.php` to set the number of retry attempts:

```php
'retries' => 3
```

It defaults to 0 if not present (normal behavior)

## Contributing

New functionality is welcomed! Create an issue or submit a PR :)

## License

This package is licensed under the [MIT license](https://github.com/FoxxMD/laravel-mail-extras/blob/master/LICENSE.txt).