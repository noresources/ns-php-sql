CREATE TABLE IF NOT EXISTS "ns_unittests"."Employees" (
  "id" INTEGER NOT NULL, 
  "name" TEXT, 
  "gender" TEXT, 
  "salary" REAL, 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)