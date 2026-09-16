-- Runs once, on first initialization of the pgdata volume.
-- See docs/design.md §7.1: the test suite runs against real PostgreSQL in a
-- separate database so it can never touch development data.
CREATE DATABASE jobtrail_testing OWNER jobtrail;
