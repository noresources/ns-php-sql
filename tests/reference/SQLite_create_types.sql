CREATE TABLE IF NOT EXISTS "ns_unittests"."types" (
  "base" TEXT, 
  "binary" BLOB DEFAULT X'616263', 
  "boolean" BOOLEAN DEFAULT 1, 
  "int" INTEGER PRIMARY KEY AUTOINCREMENT, 
  "large_int" INTEGER(12) DEFAULT 123456789012, 
  "small_int" UNSIGNED INTEGER(3), 
  "float" REAL(16, 2) DEFAULT 1.23, 
  "timestamp" DATETIMETEXT DEFAULT '2010-11-12T12:14:15', 
  "timestamp_tz" TIMESTAMPTEXT DEFAULT '2010-11-12 13:14:15+01:00'
)
