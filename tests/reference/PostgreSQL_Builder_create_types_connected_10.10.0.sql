CREATE TABLE IF NOT EXISTS "ns_unittests"."types" (
  "base" text, 
  "binary" bytea DEFAULT E '\x616263', 
  "boolean" boolean DEFAULT TRUE, 
  "int" integer DEFAULT 3, 
  "float" real DEFAULT 1.23, 
  "timestamp" abstime DEFAULT '2010-11-12T13:14:15+0100', 
  "timestamp_tz" timestamp with time zone DEFAULT '2010-11-12T13:14:15+0100', 
  CONSTRAINT "pk_types" PRIMARY KEY ("base", "int")
)