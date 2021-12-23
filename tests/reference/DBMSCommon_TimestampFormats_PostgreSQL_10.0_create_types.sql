CREATE TABLE IF NOT EXISTS "ns_unittests"."types" (
  "base" text NULL, 
  "binary" bytea NULL DEFAULT '\x616263', 
  "boolean" boolean NULL DEFAULT TRUE, 
  "int" serial, 
  "large_int" bigint NULL DEFAULT 123456789012, 
  "small_int" smallint NULL, 
  "float" double precision NULL DEFAULT 1.23, 
  "timestamp" timestamp without time zone NULL DEFAULT '2010-11-12T13:14:15', 
  "timestamp_tz" timestamp with time zone NULL DEFAULT '2010-11-12T13:14:15+0100', 
  CONSTRAINT "pk_types" PRIMARY KEY ("int")
)