Author: Li Dong <lenin.lee@gmail.com><br>
Website: http://sinolog.it<br>
Homepage: http://sinolog.it/?p=1617<br>
License: [http://www.opensource.org/licenses/bsd-license.php New BSD License]

*Dumperor* is a multi-database dumping toolkit. It dumps table structures and data from databases, and generates CREATE-TABLE SQL statements for table structures or INSERT SQL statements for data.

Part of the original intention for developing Dumperor is to check whether a migration of SQL scripts from one database to another is successful, you know, by comparing the differences between two files, one dumped before executing scripts and the other after. The second part is to ensure that upgrades to table structures or data not missing anything, similarly. The last part is to take samples of databases and put up development or testing environments with them, or even replace sensitive information with fake data.

Dumperor is written in PHP 5 and hosted on Google Code using the <a href="http://www.opensource.org/licenses/bsd-license.php">New BSD License</a>:

http://code.google.com/p/dumperor/

For the shortage of time, there must be some limitations and bugs in Dumperor. So reports from users are welcome, emails prefered. I will be very grateful if someone send their suggestions on Dumpeor to me.

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
  # Only the needed tables are to be exported if you like.
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

=Installation Instructions=

  # Download a stable release from <a href="http://code.google.com/p/dumperor/">Google Code</a> or the svn repo:<code>svn checkout http://dumperor.googlecode.com/svn/trunk/ dumperor-read-only</code>
  # Copy files to a folder which can be visited by the web server, e.g. www/dumperor.
  # Rename dumperor.ini.sample to dumperor.ini and change the settings to meet your needs.
  # Visit Dumperor from the web browser to start dumping.

=News=

 * 2011-03-14 v1.0.0 released. ([http://code.google.com/p/dumperor/wiki/Changes changes])
 * 2010-09-22 v0.1a released. ([http://code.google.com/p/dumperor/wiki/Changes changes])
