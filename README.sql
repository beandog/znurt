Create an SQL file to drop the database and recreate it:
pg_dump -C -x -s -O -f portage.sql

Quickly flush (remove all records) from a table.  If there are foreign keys, delete the children as well.  Using TRUNCATE is exponentially faster than DELETE.

TRUNCATE TABLE table_name CASCADE DELETE;
