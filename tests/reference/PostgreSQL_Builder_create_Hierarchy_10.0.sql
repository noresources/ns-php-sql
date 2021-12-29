CREATE TABLE IF NOT EXISTS "ns_unittests"."Hierarchy" (
  "managerId" bigint NOT NULL, 
  "manageeId" bigint NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  CONSTRAINT "hierarchy_managerId_foreignkey" FOREIGN KEY ("managerId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)
