Author: Li Dong <lenin.lee@gmail.com>
Website: http://sinolog.it
License: New BSD License, http://www.opensource.org/licenses/bsd-license.php

Dumperor is a multi-database dumping toolkit. It dumps table structures and data from databases, and generates CREATE-TABLE SQL statements for table structures or INSERT SQL statements for data.

Part of the original intention for developing Dumperor is to check whether a migration of SQL scripts from one database to another is successful, you know, by comparing the differences between two files, one dumped before executing scripts and the other after. The second part is to ensure that upgrades to table structures or data not miss anything, similarly. The last part is to take samples of databases and put up development or testing environments with them, or even replace sensitive information with fake data.

For shortage of time and limited test environment, there must be some limitations or even bugs in Dumperor currently. So reports from users are welcome, email prefered. Meanwhile, I will be very grateful if users send their ideas or suggestions about Dumperor to me.

Dumperor is written in PHP5 and currently relies on PDO.

## Features

1. Currently support Microsoft SQL Server, Oracle and MySQL.
1. Dump table structures and generate CREATE-TABLE SQL statements.
1. Dump data and generate INSERT SQL statements.
1. Save dumped information to user-specified files.
1. Display table structures with web page tables in the web browser.
1. Options controlling which information should be displayed in the web browser.
1. Options controlling which information should be and should not be dumped from the database.
1. A limit number can be set to take sample of a huge database.
1. Needless tables or columns can be prevented from appearing in dumped results.
1. Only the needed tables to be exported if you like.
1. Sensitive columns can be dumped with fake data.
1. Conditions can be added to data querying statements.
1. With PDO support by default but extensive to many kinds of database toolkits.
1. More in the future ...

## Requirements

1. A web server configured with PHP5 support.
1. PDO feature of PHP.
1. PHP 5.x, notice that some versions in 5.3.x series have a <a href="http://bugs.php.net/bug.php?id=47332">bug in function parse_ini_file(), which may cause trouble.

## Limitations (Known Issues)

1. Conditions for data querying only support numeric columns and equation relation.
1. Support for auto increment columns has not been implemented.
1. Sensitive columns must be prefixed with table names.
1. Needless columns must be specified only with column names, nothing more.
1. Data types which have not been tested may fail dumping.

## Installation Instructions

1. Download Dumperor.
1. Copy files to a folder which can be visited by the web server, e.g. www/dumperor.
1. Modify config.ini to meet your needs.
1. Visit Dumperor from the web browser to start dumping.

## Change Log

>2010-09-12 Sun
>v0.1, Initial Release.
