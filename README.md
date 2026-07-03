![VExim Web UI Logo](https://raw.githubusercontent.com/MrSleeps/VExim-Web-UI/refs/heads/main/public/images/logo.svg)

# Mailman 3 plugin for the VExim Web UI

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mrsleeps/vexim-web-plugin-mailman3.svg?style=flat-square)](https://packagist.org/packages/mrsleeps/vexim-web-plugin-mailman3)

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

[More information about this plugin can be found in the Wiki.](https://github.com/MrSleeps/VExim-Web-UI/wiki)

[Please add any issues to the main VExim Web UI issues page on GitHub.](https://github.com/MrSleeps/VExim-Web-UI/issues)
