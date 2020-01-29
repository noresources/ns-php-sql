CREATE TABLE "ns_unittests"."types" (
  "base" TEXT, 
  "binary" bytea DEFAULT E 'abc', 
  "boolean" boolean DEFAULT TRUE, 
  "int" integer DEFAULT 3, 
  "float" real DEFAULT 1.23, 
  "timestamp" timestamp without time zone DEFAULT '2010-11-12T13:14:15+0100', 
  "timestamp_tz" timestamp with time zone DEFAULT '2010-11-12T13:14:15+0100', 
  CONSTRAINT "pk_types" PRIMARY KEY ("base", "int")
)