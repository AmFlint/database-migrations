# Database Migration Script

- **Time taken**: ~3 hours and 10 minutes
- **Author**: Antoine Masselot
- **Language**: PHP 7.4 (I'm more fluent with Node.JS and Golang, but these languages were not accepted for this test)
- **End-Result**: [migrator.php](./migrator.php) script

This document aims to describe the Database migration script provided in this repository, the way it works and how to use it.

Here is a reminder of the task:
```
Use Case:
A database upgrade requires the execution of numbered SQL scripts stored in a specified folder, named such as '045.createtable.sql'
The scripts may contain any simple SQL statement(s) to any table of your choice, e.g. 'INSERT INTO testTable VALUES("045.createtable.sql");'
There may be gaps in the SQL file name numbering and there isn't always a . (dot) after the beginning number
The database upgrade is based on looking up the current version in the database and comparing this number to the numbers in the script names
The table where the current db version is stored is called 'versionTable', with a single row for the version, called 'version'
If the version number from the db matches the highest number from the scripts then nothing is executed
I'll scripts that contain a number higher than the current db version will be executed against the database in numerical order
In addition, the database version table is updated after the script execution with the executed script's number
Your script will be executed automatically via a program, and must satisfy these command line input parameters exactly in order to run:
 - './your-script.your-lang directory-with-sql-scripts username-for-the-db db-host db-name db-password'

Requirements:
Supported Languages: Bash, Python2.7, PHP, Shell, Ruby, Powershell - No other languages will be accepted
You will have to use a MySQL 5.7 database
How would you implement this in order to create an automated solution to the above requirements?
Please send us your script(s) and any associated notes for our review and we will come back to you asap regarding next steps.
```

## Requirements

If you have **Docker** installed with **docker-compose**, you will be able to use the [provided docker-compose.yml file](./docker-compose.yml) to test the script. Otherwhise, you might want to install the following programs:
- MySQL version 5.7
- PHP (this script is based on version 7.4) with the following extensions:
  - `pdo`
  - `pdo_mysql`

## How to use

### With Provided docker-compose file

This repository contains a [docker-compose manifest](./docker-compose.yml) to help you test the script, it will create:
- 1 Container with MySQL version 5.7 (User and Database are configured via `environment variables`)
- 1 Container with PHP version 7.4 and required extensions (pdo, pdo_mysql), built via [this Dockerfile](./Dockerfile)
- 1 Network to allow both containers to communicate

To run the stack:
```bash
docker-compose up -d
```

The directory containing this repository will be mounted inside the PHP container, which means you can create your folder containing your SQL scripts in here, and it will be available inside the container to run your migrations.

A **full example** Shell script is provided, to help you run a test scenario on the PHP script provided in this repository.

You may run a complete example from the [example.sh script provided in this repository](./example.sh), this script will:
- Connect to MySQL container to create a table called `messages`, containing `id` (Primary Key, auto increment) and `content` (varchar => 155)
- Run the migration script inside PHP Container, providing the database simple configuration defined inside `docker-compose.yml`, running migrations contained [in the migrations sample directory](./migrations), which will create/update/delete records on the `messages` table.
- Re-run the same migration command, to make sure that the SQL scripts are not run-again (Database version is already `latest`).
- Add a new SQL script with highest version number with a simple INSERT statement, and re-run the script to import the latest SQL statements.

```bash
sh example.sh

# Outputs
Creating base table "messages" for the example
mysql: [Warning] Using a password on the command line interface can be insecure.

Running the migration script for the first time, should import SQL scripts

Importing SQL script file: 28.t.sql
Importing SQL script file: 045.insert_messages.sql
Importing SQL script file: 047.insert_other_messages.sql
Importing SQL script file: 53insert-db.sql
Importing SQL script file: 0280.test.sql
Importing SQL script file: 0920_test.sql
Importing SQL script file: 1000.test.sql
Importing SQL script file: 2341.new_file.sql

Running the migration script for the second time, should not import SQL scripts as Database already reached latest version

Database is up-to-date

Adding a new Migration script with highest version number, and re-running migrations

Database is up-to-date
```

Then, you should check your MySQL Database, to make sure that migration scripts did run properly.

### Without provided docker-compose

- Make sure that the php binary used in [main script](./migrator.php) is the right PHP executable on your system.
