CREATE TABLE "ns_unittests"."types" (
  "base" text NULL, 
  "binary" bytea NULL DEFAULT '\x616263', 
  "boolean" boolean NULL DEFAULT TRUE, 
  "int" serial, 
  "large_int" numeric(12) NULL DEFAULT 123456789012, 
  "small_int" numeric(3) NULL, 
  "float" numeric NULL DEFAULT 1.23456, 
  "fixed_precision" numeric(4, 2) NULL DEFAULT 1.23, 
  "timestamp" timestamp without time zone NULL DEFAULT '2010-11-12T21:14:15', 
  "timestamp_tz" timestamp with time zone NULL DEFAULT '2010-11-12T13:14:15+0100', 
  CONSTRAINT "pk_types" PRIMARY KEY ("int")
)