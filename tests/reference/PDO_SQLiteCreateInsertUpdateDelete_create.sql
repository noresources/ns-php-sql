CREATE TABLE IF NOT EXISTS "Employees" (
  "id" INTEGER NOT NULL, 
  "name" TEXT, 
  "gender" TEXT, 
  "salary" REAL, 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)