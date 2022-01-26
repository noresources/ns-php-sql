CREATE TABLE "ns_unittests"."Employees_backup" (
  "id" bigint NOT NULL, 
  "name" text, 
  "gender" character varying(1) NULL, 
  "salary" numeric(7, 2) NULL
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
  "managerId" bigint NOT NULL, "manageeId" bigint NOT NULL
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
  "id" bigint NOT NULL, 
  "name" character varying(32) NULL, 
  "creationDateTime" timestamp with time zone NULL DEFAULT CURRENT_TIMESTAMP, 
  "priority" bigint NULL, 
  "category" bigint NULL, 
  "creator" bigint NULL, 
  "assignedTo" bigint NULL
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
  "id" bigint NOT NULL, 
  "fullName" text, 
  "gender" character varying(1) NULL, 
  "salary" numeric(7, 2) NULL, 
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
  "managerId" bigint NOT NULL, 
  "manageeId" bigint NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  CONSTRAINT "hierarchy_managerId_foreignkey" FOREIGN KEY ("managerId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
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
  "id" serial, 
  "name" character varying(32) NULL, 
  "creationDateTime" timestamp with time zone NULL DEFAULT CURRENT_TIMESTAMP, 
  "priority" bigint NULL, 
  "category" bigint NULL, 
  "creator" bigint NULL DEFAULT NULL, 
  "assignedTo" bigint NULL DEFAULT NULL, 
  CONSTRAINT "pk_tid" PRIMARY KEY ("id"), 
  CONSTRAINT "fk_creator" FOREIGN KEY ("creator") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("assignedTo") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
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
CREATE INDEX "index_employees_name" ON "ns_unittests"."Employees" ("fullName")
;
