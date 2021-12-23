ATTACH DATABASE 'ns_unittests.sqlite' AS "ns_unittests"
;
CREATE TABLE "ns_unittests"."Employees" (
  "id" INTEGER NOT NULL, 
  "name" TEXT, 
  "gender" TEXT(1) NULL, 
  "salary" REAL(7, 2) NULL, 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)
;
CREATE INDEX "ns_unittests"."index_employees_name" ON "Employees" ("name")
;
CREATE TABLE "ns_unittests"."Hierarchy" (
  "managerId" INTEGER NOT NULL, 
  "manageeId" INTEGER NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  CONSTRAINT "hierarchy_managerId_foreignkey" FOREIGN KEY ("managerId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)
;
CREATE TABLE "ns_unittests"."Tasks" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT, 
  "name" TEXT(32) NULL, 
  "creationDateTime" TIMESTAMPTEXT NULL DEFAULT CURRENT_TIMESTAMP, 
  "priority" INTEGER NULL, 
  "category" INTEGER NULL, 
  "creator" INTEGER NULL DEFAULT NULL, 
  "assignedTo" INTEGER NULL DEFAULT NULL, 
  CONSTRAINT "fk_creator" FOREIGN KEY ("creator") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("assignedTo") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)
;
CREATE TABLE "ns_unittests"."types" (
  "base" TEXT NULL, 
  "binary" BLOB NULL DEFAULT X'616263', 
  "boolean" BOOLEAN NULL DEFAULT 1, 
  "int" INTEGER PRIMARY KEY AUTOINCREMENT, 
  "large_int" INTEGER(12) NULL DEFAULT 123456789012, 
  "small_int" UNSIGNED INTEGER(3) NULL, 
  "float" REAL NULL DEFAULT 1.23456, 
  "fixed_precision" REAL(14, 2) NULL DEFAULT 1.23, 
  "timestamp" DATETIMETEXT NULL DEFAULT '2010-11-12T12:14:15', 
  "timestamp_tz" TIMESTAMPTEXT NULL DEFAULT '2010-11-12 13:14:15+01:00'
)
;
