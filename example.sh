# 1. Create a table called messages with PK ID auto increment, and a content
echo 'Creating base table "messages" for the example'
docker-compose exec database mysql --database=ecs --user=ecs --password=ecs --execute="CREATE TABLE IF NOT EXISTS messages (id int(11) NOT NULL AUTO_INCREMENT, content varchar(155) NOT NULL, PRIMARY KEY(id));"

# 2. Run Migration script
echo '\nRunning the migration script for the first time, should import SQL scripts'
docker-compose exec php ./migrator.php migrations ecs database:3306 ecs ecs

# 3. Run the same migration script, to make sure that database is already up-to-date with the latest version
echo '\nRunning the migration script for the second time, should not import SQL scripts as Database already reached latest version'
docker-compose exec php ./migrator.php migrations ecs database:3306 ecs ecs

# 4. Create a new SQL Script, and re-run migration script to make sure the latest version gets updated
echo '\n\nAdding a new Migration script with highest version number, and re-running migrations'
echo 'INSERT INTO messages (content) VALUES("it works, does it not?");' > migrations/2341.new_file.sql
docker-compose exec php ./migrator.php migrations ecs database:3306 ecs ecs