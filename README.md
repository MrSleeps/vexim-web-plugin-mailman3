![VExim Web UI Logo](https://raw.githubusercontent.com/MrSleeps/VExim-Web-UI/refs/heads/main/public/images/logo.svg)
# Mailman 3 plugin for the VExim Web UI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mrsleeps/vexim-mailman3.svg?style=flat-square)](https://packagist.org/packages/mrsleeps/vexim-mailman3)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/mrsleeps/vexim-mailman3/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mrsleeps/vexim-mailman3/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/mrsleeps/vexim-mailman3/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/mrsleeps/vexim-mailman3/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/mrsleeps/vexim-mailman3.svg?style=flat-square)](https://packagist.org/packages/mrsleeps/vexim-mailman3)



This is a plugin for the VExim Web UI that allows you to manage your Mailman 3 lists.

## Installation
You will need Mailman 3 up and running (obviously) before installing this, easiest way is with Docker.

You will also need to make sure you have run the Mailman 3 VExim sql migration.

Once you have a working version of Mailman 3 and the database table is present, you need to install the package via composer:

```bash
composer require mrsleeps/vexim-web-plugin-mailman3
```
Once installed, you need to edit your .env file and add the following

    MAILMAN_HOST=
    MAILMAN_PORT=
    MAILMAN_USERNAME=
    MAILMAN_PASSWORD=
    MAILMAN_API_VERSION=3.1
    MAILMAN_TIMEOUT=30

Login to your VExim Web UI and you will see a menu item called Mailman Lists, you can add your lists from there.

First time of running? Click the sync button and it will sync your current Mailman Lists with the web ui.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Mr Sleeps](https://github.com/MrSleeps)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
