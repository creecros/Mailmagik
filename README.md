## Checkout our latest project
[![](https://raw.githubusercontent.com/docpht/docpht/master/public/assets/img/logo.png)](https://github.com/docpht/docpht)

- With [DocPHT](https://github.com/docpht/docpht) you can take notes and quickly document anything and without the use of any database.
-----------
[![Latest release](https://img.shields.io/github/release/creecros/kbphpimap.svg)](https://github.com/creecros/MarkdownPlus/releases)
![GitHub license](https://img.shields.io/github/license/Naereen/StrapDown.js.svg)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/creecros/kbphpimap/graphs/contributors)
![Open Source Love](https://badges.frapsoft.com/os/v1/open-source.svg?v=103)
![Downloads](https://img.shields.io/github/downloads/creecros/kbphpimap/total.svg)

**:star: If you use it, you should star it on Github!**
*It's the least you can do for all the work put into it!*

# Kanboard PHP IMAP

This plugin allows you to connect Kanboard directly to an IMAP server. Once connected, you can:

1. Send emails directly to a task
2. Send emails to a project to automatically be converted into a task within the project
3. Send emails to a task to automatically convert into a task comment

Once installed, setup in config:
![image](https://user-images.githubusercontent.com/26339368/216668816-a7a00c09-7594-4fda-8d7a-2f59dc6c0028.png)

- Example to send an email directly to a task: `Task#1<myemail@email.com>`
- Example to send an email to a project for task conversion: `Project#1<myemail@email.com>`
- Example to send an email to a task for comment conversion: `CommentOnTask#1<myemail@email.com>`

Emails sent to a task can be found in the Task uner the "Task Email" icon on the sidebar:
![image](https://user-images.githubusercontent.com/26339368/216670260-ddffad7f-62ee-4297-ad73-3ec03d9fb04e.png)


In order to setup automatic conversions of email, you will need to add the Actions to the project.


**Plugin Author:** _[creecros](https://github.com/creecros)_

# Requirements

https://github.com/barbushin/php-imap#requirements

If you are using the docker containter of Kanboard, simply remote into it and:
```
apk update
apk add php-imap
apk add php81-fileinfo
```

**PHP 7.4**
Check:
```
php -m | grep -i imap
```

```
$ sudo apt-get install php-imap
$ sudo apt-get install php7.4-imap
$ sudo apt-get install -y php-fileinfo
```
Run both commands


# Install

## Automatically

1. If your Kanboard installation is configured to install from the app, simply find it in the plugins directory and choose install.
2. Restart your server 


## Manually

1. Download the latest versions supplied zip file, it should be named `kbphpimap-x.xx.x.zip`
  - I advise not to install from source or master

![image](https://user-images.githubusercontent.com/26339368/58711319-45ba2d00-838c-11e9-9d07-71a526ba5b74.png)

2. Unzip to the plugins folder.
  - your folder structure should look like the following:
```
plugins
└── Kbphpimap            <= Plugin name
    ├── Controller  
    ├── Template
    ├── vendor
    ├── LICENSE
    ├── Plugin.php   
    ├── README.md
    ├── composer.json
    └── composer.lock
```

3.) Restart your server
