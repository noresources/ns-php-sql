CREATE TABLE IF NOT EXISTS "ns_unittests"."Tasks" (
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