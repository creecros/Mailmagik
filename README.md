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

# :magic_wand: Mailmagik for Kanboard

Have you ever found yourself struggling with the process of creating a task in Kanboard via email? Maybe someone recommended the Mailgun Plugin, but you ended up feeling frustrated and out of pocket when you discovered its cost. Or perhaps someone suggested using Zapier and API, but you were left scratching your head and wondering what on earth they were talking about.

Well, fear not! We have the solution for you. Our plugin is easy to install and allows you to connect to an IMAP server of your choice. With just a few clicks, you'll be creating tasks like a pro in no time.

Say goodbye to confusion and frustration, and say hello to Mailmagik! Join us today and let's make task creation a breeze.

This plugin allows you to connect Kanboard directly to an IMAP server. Once connected, you can:

1. Send emails directly to a task
2. Send emails to a project to automatically be converted into a task within the project
3. Send emails to a task to automatically convert into a task comment
4. Adds a Trigger Event that can be called using the Crontab

**For #2 & #3**
- *Using the Daily Background Job for Tasks: You will need to setup the automatic actions in the project config, and there _MUST_ be at least 1 open task within the project*
- *Using the Mailmagik mail fetching trigger: You will need to setup the automatic actions in the project config*

**For #2**
- *The sender of the mail must be a member of the targeted project, with the permission to create new tasks.*

**For #4**
- *Trigger using: `./cli mailmagik:fetchmail`*
- *Example of adding to crontab to run independently of Daily Background Job:*
```
0 8 * * * cd /var/www/app && ./cli cronjob >/dev/null 2>&1
* * * * * cd /var/www/app && ./cli mailmagik:fetchmail
```

Since release 1.4.0, mail fetching can also be triggered by a webcron system.
Simply invoke the URL
```
https://<your_server>/?controller=FetchmailController&plugin=Mailmagik&action=run&token=<your_token>
```
You'll get
```
mailmagik:fetchmail executed
```
as a response.


Once installed, setup in `Settings > Email settings`:
<br>
<img src="https://user-images.githubusercontent.com/26339368/222557692-e0e3366d-7f2c-4044-909e-c20ecd0c509d.png" alt="image" width="65%">

The **Parse Option** allows you to choose how to parse the actions, using the `TO:` field or the `SUBJECT:` field

When using the `TO:` option, see examples below:
- to send an email directly to a task: `Task#1<myemail@email.com>`
- to send an email to a project for task conversion: `Project#1<myemail@email.com>`
- to send an email to a task for comment conversion: `CommentOnTask#1<myemail@email.com>`

When using the `SUBJECT:` option, see examples below:
- to send an email directly to a task: `[Task#1] ` should appear at the start of the Subject
- to send an email to a project for task conversion: `[Project#1] ` should appear at the start of the Subject
- to send an email to a task for comment conversion: `[CommentOnTask#1] ` should appear at the start of the Subject
- note: the action must be in brackets

Emails sent to a task can be found in the Task uner the "Task Email" icon on the sidebar:
<br>
<img src="https://user-images.githubusercontent.com/26339368/216670260-ddffad7f-62ee-4297-ad73-3ec03d9fb04e.png" alt="image" width="65%">


In order to setup automatic conversions of email, you will need to add the Actions to the project.

When sending emails for automatic Task Conversion, MailMagik can parse the subject line to add attributes to the Task:

|in Subject|Effect
|-|-
|d:YYYY-MM-DD| Set due date
|s:YYYY-MM-DD |Set start date
|p:n |Set priority n
|c:name| Set category 'name'
|t:name| Add tag 'name', may be repeated. Non-existing tags will be created, beware of typos.
|col:name| Set column 'name' to bypass default column

A subject line containing

> Test c:SQL p:3 d:2023-02-28 s:2023-02-20

will create a priority 3 task named "Test", category "SQL", start on Feb 20 and due on Feb 28.

Attribute values for category, column and tags must be quoted, in case they consist of multiple words. Quoting chars can be any of "", '', ‘’, “”, «» and „“. Example: col:"Work in progress".

![image](https://user-images.githubusercontent.com/2079289/217609719-e1fecea5-0616-4cb7-be31-7db6f2c418c1.png)

The plugin can also **parse the email body** for task attributes.
Use the following syntax: Enclose the assignment with

- _&@ @&_ for standard task attributes
- _$@ @$_ for metamagik fields

Examples:

    &@category_id=foo bar@&
    &@column_id=Done@&
    &@priority=3@&

    $@Custom_field="Lorem ipsum"@$

Optionally, this assignments can be removed from email body after processing.

**Plugin Authors:**
- _[creecros](https://github.com/creecros)_
- _[alfredbuehler](https://github.com/alfredbuehler)_

**Collaborators and Contributors:**
- _[aljawaid](https://github.com/aljawaid)_

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

1. Download the latest versions supplied zip file, it should be named `Mailmagik-x.xx.x.zip`
  - I advise not to install from source or master

2. Unzip to the plugins folder.
  - your folder structure should look like the following:
```
plugins
└── Mailmagik            <= Plugin name
    ├── Action  
    ├── Assets  
    ├── Controller  
    ├── Template
    ├── vendor
    ├── LICENSE
    ├── CHANGELOG.MD
    ├── Makefile
    ├── Plugin.php   
    ├── README.md
    ├── composer.json
    └── composer.lock
```

3.) Restart your server

## Troubleshooting

1. If you are getting `OP_READONLY` complaints about an undefined variable, then you are missing the php-imap requirement, install req, enable and restart server.
