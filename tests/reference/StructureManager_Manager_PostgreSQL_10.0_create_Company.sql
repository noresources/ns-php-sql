CREATE SCHEMA "ns_unittests"
;
CREATE TABLE "ns_unittests"."Employees" (
  "id" bigint NOT NULL, 
  "name" text, 
  "gender" character varying(1) NULL, 
  "salary" numeric(7, 2) NULL, 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)
;
CREATE INDEX "index_employees_name" ON "ns_unittests"."Employees" ("name")
;
CREATE TABLE "ns_unittests"."Hierarchy" (
  "managerId" bigint NOT NULL, 
  "manageeId" bigint NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  CONSTRAINT "hierarchy_managerId_foreignkey" FOREIGN KEY ("managerId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)
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
CREATE TABLE "ns_unittests"."types" (
  "base" text NULL, 
  "binary" bytea NULL DEFAULT '\x616263', 
  "boolean" boolean NULL DEFAULT TRUE, 
  "int" serial, 
  "large_int" numeric(12) NULL DEFAULT 123456789012, 
  "small_int" numeric(3) NULL, 
  "float" numeric NULL DEFAULT 1.23456, 
  "fixed_precision" numeric(4, 2) NULL DEFAULT 1.23, 
  "timestamp" timestamp without time zone NULL DEFAULT '2010-11-12T13:14:15', 
  "timestamp_tz" timestamp with time zone NULL DEFAULT '2010-11-12T13:14:15+0100', 
  CONSTRAINT "pk_types" PRIMARY KEY ("int")
)
;
