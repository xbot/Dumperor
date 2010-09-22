Author: Li Dong <lenin.lee@gmail.com>
Website: http://sinolog.it
License: New BSD License, http://www.opensource.org/licenses/bsd-license.php

*Dumperor* is a multi-database dumping toolkit. It dumps table structures and data from databases, and generates CREATE-TABLE SQL statements for table structures or INSERT SQL statements for data.

Part of the original intention for developing Dumperor is to check whether a migration of SQL scripts from one database to another is successful, you know, by comparing the differences between two files, one dumped before executing scripts and the other after. The second part is to ensure that upgrades to table structures or data not miss anything, similarly. The last part is to take samples of databases and put up development or testing environments with them, or even replace sensitive information with fake data.

Dumperor is hosted on Google Code:

http://code.google.com/p/dumperor/

=Features=

  # Currently support Microsoft SQL Server, Oracle and MySQL.
  # Dump table structures and generate CREATE-TABLE SQL statements.
  # Dump data and generate INSERT SQL statements.
  # Save dumped information to user-specified files.
  # Display table structures with web page tables in the web browser.
  # Options controlling which information should be displayed in the web browser.
  # Options controlling which information should be and should not be dumped from the database.
  # A limit number can be set to take sample of a huge database.
  # Needless tables or columns can be prevented from appearing in dumped results.
  # Only the needed tables to be exported if you like.
  # Sensitive columns can be dumped with fake data.
  # Conditions can be added to data querying statements.
  # With PDO support by default but extensive to many kinds of database toolkits.
  # More in the future ...

=Requirements=

  # A web server configured with PHP5 support.
  # PDO feature of PHP.
  # PHP 5.x, notice that some versions in 5.3.x series have a <a href="http://bugs.php.net/bug.php?id=47332">bug in function parse_ini_file(), which may cause trouble.

=Limitations (Known Issues)=

  # Conditions for data querying only support numeric columns and equation relation.
  # Support for auto increment columns has not been implemented.
  # Sensitive columns must be prefixed with table names.
  # Needless columns must be specified only with column names, nothing more.
  # Data types which have not been tested may fail dumping.

=Installation Instructions=
*Important: Dumperor is buggy at the moment, and you take all the risks with yourself while trying it.*

  # Download Dumperor.<code>svn checkout http://dumperor.googlecode.com/svn/trunk/ dumperor-read-only</code>
  # Copy files to a folder which can be visited by the web server, e.g. www/dumperor.
  # Rename dumperor.ini.sample to dumperor.ini and change the settings to meet your needs.
  # Visit Dumperor from the web browser to start dumping.

=Change Log=

*2010-09-22 Wednesday _the Mid-autumn Day of Chinese_*

v0.1a, Initial Release.
