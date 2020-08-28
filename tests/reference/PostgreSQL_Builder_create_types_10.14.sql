CREATE TABLE IF NOT EXISTS "ns_unittests"."types" (
  "base" text, 
  "binary" bytea DEFAULT '\x616263', 
  "boolean" boolean DEFAULT TRUE, 
  "int" serial, 
  "large_int" bigint DEFAULT 123456789012, 
  "small_int" smallint, 
  "float" double precision DEFAULT 1.23, 
  "timestamp" timestamp without time zone DEFAULT '2010-11-12T13:14:15+0100', 
  "timestamp_tz" timestamp with time zone DEFAULT '2010-11-12T13:14:15+0100', 
  CONSTRAINT "pk_types" PRIMARY KEY ("int")
)