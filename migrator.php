#!/usr/local/bin/php

<?php

if (count($argv) !== 6)
{
  print("Incorrect number of arguments, the script might be call in the following manners: \nphp migrator.php directory-with-sql-scripts username-for-the-db db-host db-name db-password'");
  exit(1);
}

$sql_script_directory = $argv[1];
$database_username = $argv[2];
$database_host = $argv[3];
$database_name = $argv[4];
$database_password = $argv[5];

// List files in SQL script directory
if (!is_dir($sql_script_directory))
{
  print("Unable to list SQL scripts inside directory $sql_script_directory, directory does not exist");
  exit(1);
}

$sql_files_with_directories = scandir($sql_script_directory);
$sql_files = array_diff($sql_files_with_directories, array('.', '..'));

// Get latest version in table, if table does not exist -> Create versionTable
try
{
  $pdo = new PDO("mysql:host=$database_host;dbname=$database_name;", $database_username, $database_password);
}
catch (Exception $error)
{
  print("Unable to open connection to the database, got error: {$error->getMessage()}");
  exit(1);
}

$create_table_statement = 'CREATE TABLE IF NOT EXISTS `versionTable` (version int NOT NULL);';
$create_table_query = $pdo->prepare($create_table_statement);
if (!$create_table_query->execute())
{
  print("unable to manage version table, got error: {$create_table_query->errorInfo()}");
  exit(1);
}

// Get latest version in database
$latest_version_statement = 'SELECT `version` FROM `versionTable` ORDER BY `version` DESC LIMIT 1';
$latest_version_query = $pdo->prepare($latest_version_statement);
if (!$latest_version_query->execute())
{
  print("Unable to retrieve the latest version in database, got error: {$latest_version_query->errorInfo()}");
  exit(1);
}

// If this is the first migration
$latest_version_record = $latest_version_query->fetch(PDO::FETCH_ASSOC);
if (!$latest_version_record)
{
  $latest_version = 0;
}
else
{
  $latest_version = intval($latest_version_record['version']);
}

// Get SQL Files in order (> previous Version)
const GET_VERSION_REGEX = '/0?([0-9]+).?[a-zA-Z0-9._\-]+sql$/';
$sql_files_to_apply = [];
$new_latest_version = $latest_version;
foreach($sql_files as $sql_file)
{
  // Ignore directories
  if (is_dir("$sql_script_directory/$sql_file"))
  {
    continue;
  }

  // Get the version number for SQL Script with a REGEX
  preg_match(GET_VERSION_REGEX, $sql_file, $matches);
  if (count($matches) !== 2 || $matches[1] <= $latest_version)
  {
    continue;
  }

  $version_number = $matches[1];

  $sql_files_to_apply[$version_number] = $sql_file;
  if ($version_number > $new_latest_version)
  {
    $new_latest_version = $version_number;
  }
}

if (!count($sql_files_to_apply))
{
  print("Database is up-to-date");
  exit(0);
}

// Sort list of SQL Scripts by Version
// avoids an issue with 01000.myfile.sql being executed before 0291.myfile.sql
ksort($sql_files_to_apply);

// Transaction => Commit every SQL Script files, if an error occurs => rollBack
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try
{
  $pdo->beginTransaction();

  // Import every SQL Script and run it
  foreach($sql_files_to_apply as $sql_file)
  {
    print("Importing SQL script file: $sql_file\n");

    // Read SQL Script file, check every line in the script to execute SQL statements accordingly
    $lines_in_file = file("$sql_script_directory/$sql_file");
    $full_line = '';
    foreach($lines_in_file as $line)
    {
      // If line start with "--" (SQL comment) or line is empty, then we should ignore the instruction
      if (substr($line, 0, 2) == '--' || trim($line) == '')
      {
        continue;
      }

      // Append current line to full_line, allows us to run multi-line statements
      $full_line .= $line;
      // Check if we reached the end of the statement (line ending with ';')
      // If we did, then execute the SQL statement
      if (substr(trim($line), -1, 1) == ';')
      {
        $statement = $pdo->prepare($full_line);
        $statement->execute();
        // reset full line for next statement
        $full_line = '';
      }
    }
  }

  // Last instruction in transaction => Update version for latest version found
  $update_version_query = "INSERT INTO `versionTable` (`version`) VALUES(:version);";
  $update_version_statement = $pdo->prepare($update_version_query);
  $update_version_statement->bindValue(':version', $new_latest_version);
  $update_version_statement->execute();

  $pdo->commit();
}
catch (PDOException $error)
{
  print("Unable to import SQL Migration scripts, got error: {$error->getMessage()}");
  $pdo->rollBack();

  exit(1);
}
