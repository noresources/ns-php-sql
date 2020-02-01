CREATE TABLE IF NOT EXISTS "ns_unittests"."Employees" (
  "id" integer NOT NULL, 
  "name" TEXT, 
  "gender" TEXT, 
  "salary" real(7), 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)