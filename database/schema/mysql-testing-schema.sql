-- This file exists to support Laravel's schema loading for the `mysql-testing` connection.
-- Laravel will automatically look for `database/schema/{connection}-schema.sql`.
--
-- Keep the real schema dump in one place:
--   /var/www/html/database/schema/mysql-schema.sql
--
-- MySQL client commands (like `source`) are supported in batch mode when the schema is loaded via:
--   mysql ... < "path"
source /var/www/html/database/schema/mysql-schema.sql;

