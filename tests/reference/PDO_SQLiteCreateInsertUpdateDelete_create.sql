CREATE TABLE IF NOT EXISTS "Employees" (
  "id" INTEGER NOT NULL, 
  "name" TEXT, 
  "gender" TEXT(1) NULL, 
  "salary" REAL(7, 2) NULL, 
  CONSTRAINT "pk_id" PRIMARY KEY ("id")
)
