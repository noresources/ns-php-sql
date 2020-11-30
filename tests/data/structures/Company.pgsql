-- ns-xml database schema to SQL translation
CREATE SCHEMA IF NOT EXISTS "ns_unittests";
-- Company employees
CREATE TABLE "ns_unittests"."Employees"
(
"id" INTEGER NOT NULL,
"name" TEXT,
"gender" VARCHAR(1),
"salary" REAL,
CONSTRAINT "pk_id" PRIMARY KEY ("id")
);
CREATE INDEX "index_employees_name" ON "ns_unittests"."Employees" ("name");
CREATE TABLE "ns_unittests"."Hierarchy"
(
"managerId" INTEGER NOT NULL,
"manageeId" INTEGER NOT NULL,
PRIMARY KEY ("managerId", "manageeId"),
FOREIGN KEY ("managerId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
FOREIGN KEY ("manageeId") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE TABLE "ns_unittests"."Tasks"
(
"id" SERIAL,
"name" VARCHAR(32),
-- Creation timestamp
"creationDateTime" timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
"priority" INTEGER,
"category" INTEGER,
"creator" INTEGER DEFAULT NULL,
"assignedTo" INTEGER DEFAULT NULL,
CONSTRAINT "pk_tid" PRIMARY KEY ("id"),
CONSTRAINT "fk_creator" FOREIGN KEY ("creator") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE, 
FOREIGN KEY ("assignedTo") REFERENCES "ns_unittests"."Employees" ("id") ON UPDATE CASCADE ON DELETE CASCADE
);
CREATE TABLE "ns_unittests"."types"
(
"base" TEXT,
"binary" BYTEA DEFAULT E'\x61\x62\x63',
"boolean" BOOLEAN DEFAULT true,
"int" SERIAL,
-- A large int with size spec.
"large_int" INTEGER DEFAULT 123456789012,
-- A quite small int with size spec.
"small_int" INTEGER,
"float" REAL DEFAULT 1.23,
"timestamp" timestamp without time zone DEFAULT '2010-11-12T13:14:15+01:00',
"timestamp_tz" timestamp with time zone DEFAULT '2010-11-12T13:14:15+01:00',
CONSTRAINT "pk_types" PRIMARY KEY ("int")
);
