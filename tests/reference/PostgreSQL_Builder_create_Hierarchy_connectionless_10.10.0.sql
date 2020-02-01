CREATE TABLE IF NOT EXISTS "ns_unittests"."Hierarchy" (
  "managerId" integer NOT NULL, 
  "manageeId" integer NOT NULL, 
  PRIMARY KEY ("managerId", "manageeId"), 
  FOREIGN KEY ("managerId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ("manageeId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
)