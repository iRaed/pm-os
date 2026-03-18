-- PM-OS PostgreSQL Initialization
-- Enable PostGIS extension for spatial queries
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS pg_trgm;   -- For fuzzy text search
CREATE EXTENSION IF NOT EXISTS "uuid-ossp"; -- UUID generation
