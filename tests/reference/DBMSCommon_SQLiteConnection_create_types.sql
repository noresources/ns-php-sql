CREATE TABLE IF NOT EXISTS "ns_unittests"."types" (
  "base" TEXT, 
  "binary" BLOB DEFAULT X '616263', 
  "boolean" INTEGER DEFAULT 1, 
  "int" INTEGER DEFAULT 3, 
  "large_int" INTEGER, 
  "small_int" INTEGER, 
  "float" REAL DEFAULT 1.23, 
  "timestamp" TEXT DEFAULT '2010-11-12 13:14:15+01:00', 
  "timestamp_tz" TEXT DEFAULT '2010-11-12 13:14:15+01:00', 
  CONSTRAINT "pk_types" PRIMARY KEY ("base", "int")
)