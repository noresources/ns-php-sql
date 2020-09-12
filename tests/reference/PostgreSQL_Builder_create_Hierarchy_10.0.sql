CREATE TABLE IF NOT EXISTS "ns_unittests"."Hierarchy" (
  "managerId" bigint NOT NULL, 
  "manageeId" bigint NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  FOREIGN KEY ("managerId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)