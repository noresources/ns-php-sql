CREATE TABLE IF NOT EXISTS "ns_unittests"."Employees" (
  "id" bigint NOT NULL, 
  "name" text, 
  "gender" character varying(1), 
  "salary" numeric(7, 2), 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)