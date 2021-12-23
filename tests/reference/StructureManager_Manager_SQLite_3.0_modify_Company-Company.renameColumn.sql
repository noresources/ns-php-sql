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
DROP 
  INDEX IF EXISTS "ns_unittests"."index_employees_name"
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
CREATE INDEX "ns_unittests"."index_employees_name" ON "Employees" ("fullName")
;
