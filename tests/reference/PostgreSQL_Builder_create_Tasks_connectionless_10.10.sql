CREATE TABLE IF NOT EXISTS "ns_unittests"."Tasks" (
  "id" serial, 
  "name" text, 
  "creationDateTime" timestamp with time zone DEFAULT CURRENT_TIMESTAMP, 
  "priority" bigint, 
  "category" bigint, 
  "creator" bigint DEFAULT NULL, 
  "assignedTo" bigint DEFAULT NULL, 
  CONSTRAINT "pk_tid" PRIMARY KEY ("id"), 
  CONSTRAINT "fk_creator" FOREIGN KEY ("creator") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("assignedTo") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)