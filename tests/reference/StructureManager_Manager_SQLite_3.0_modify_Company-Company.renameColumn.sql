CREATE TABLE "ns_unittests"."Employees_backup" (
  "id" INTEGER NOT NULL, 
  "name" TEXT, 
  "gender" TEXT(1) NULL, 
  "salary" REAL(7, 2) NULL
)
;
INSERT INTO "ns_unittests"."Employees_backup" ("id", "name", "gender", "salary") 
SELECT 
  "ns_unittests"."Employees"."id", 
  "ns_unittests"."Employees"."name", 
  "ns_unittests"."Employees"."gender", 
  "ns_unittests"."Employees"."salary" 
FROM 
  "ns_unittests"."Employees"
;
CREATE TABLE "ns_unittests"."Hierarchy_backup" (
  "managerId" INTEGER NOT NULL, "manageeId" INTEGER NOT NULL
)
;
INSERT INTO "ns_unittests"."Hierarchy_backup" ("managerId", "manageeId") 
SELECT 
  "ns_unittests"."Hierarchy"."managerId", 
  "ns_unittests"."Hierarchy"."manageeId" 
FROM 
  "ns_unittests"."Hierarchy"
;
CREATE TABLE "ns_unittests"."Tasks_backup" (
  "id" INTEGER NULL, 
  "name" TEXT(32) NULL, 
  "creationDateTime" TIMESTAMPTEXT NULL DEFAULT CURRENT_TIMESTAMP, 
  "priority" INTEGER NULL, 
  "category" INTEGER NULL, 
  "creator" INTEGER NULL DEFAULT NULL, 
  "assignedTo" INTEGER NULL DEFAULT NULL
)
;
INSERT INTO "ns_unittests"."Tasks_backup" (
  "id", "name", "creationDateTime", 
  "priority", "category", "creator", 
  "assignedTo"
) 
SELECT 
  "ns_unittests"."Tasks"."id", 
  "ns_unittests"."Tasks"."name", 
  "ns_unittests"."Tasks"."creationDateTime", 
  "ns_unittests"."Tasks"."priority", 
  "ns_unittests"."Tasks"."category", 
  "ns_unittests"."Tasks"."creator", 
  "ns_unittests"."Tasks"."assignedTo" 
FROM 
  "ns_unittests"."Tasks"
;
DROP 
  INDEX IF EXISTS "ns_unittests"."index_employees_name"
;
DROP 
  TABLE IF EXISTS "ns_unittests"."Hierarchy"
;
DROP 
  TABLE IF EXISTS "ns_unittests"."Tasks"
;
DROP 
  TABLE IF EXISTS "ns_unittests"."Employees"
;
CREATE TABLE "ns_unittests"."Employees" (
  "id" INTEGER NOT NULL, 
  "fullName" TEXT, 
  "gender" TEXT(1) NULL, 
  "salary" REAL(7, 2) NULL, 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)
;
INSERT INTO "ns_unittests"."Employees" (
  "fullName", "id", "gender", "salary"
) 
SELECT 
  "ns_unittests"."Employees_backup"."name", 
  "ns_unittests"."Employees_backup"."id", 
  "ns_unittests"."Employees_backup"."gender", 
  "ns_unittests"."Employees_backup"."salary" 
FROM 
  "ns_unittests"."Employees_backup"
;
DROP 
  TABLE IF EXISTS "ns_unittests"."Employees_backup"
;
CREATE TABLE "ns_unittests"."Hierarchy" (
  "managerId" INTEGER NOT NULL, 
  "manageeId" INTEGER NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  CONSTRAINT "hierarchy_managerId_foreignkey" FOREIGN KEY ("managerId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)
;
INSERT INTO "ns_unittests"."Hierarchy" ("managerId", "manageeId") 
SELECT 
  "ns_unittests"."Hierarchy_backup"."managerId", 
  "ns_unittests"."Hierarchy_backup"."manageeId" 
FROM 
  "ns_unittests"."Hierarchy_backup"
;
DROP 
  TABLE IF EXISTS "ns_unittests"."Hierarchy_backup"
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
INSERT INTO "ns_unittests"."Tasks" (
  "id", "name", "creationDateTime", 
  "priority", "category", "creator", 
  "assignedTo"
) 
SELECT 
  "ns_unittests"."Tasks_backup"."id", 
  "ns_unittests"."Tasks_backup"."name", 
  "ns_unittests"."Tasks_backup"."creationDateTime", 
  "ns_unittests"."Tasks_backup"."priority", 
  "ns_unittests"."Tasks_backup"."category", 
  "ns_unittests"."Tasks_backup"."creator", 
  "ns_unittests"."Tasks_backup"."assignedTo" 
FROM 
  "ns_unittests"."Tasks_backup"
;
DROP 
  TABLE IF EXISTS "ns_unittests"."Tasks_backup"
;
CREATE INDEX "ns_unittests"."index_employees_name" ON "Employees" ("fullName")
;
