CREATE 
OR REPLACE TABLE [ns_unittests].[Employees] (
  [id] INTEGER NOT NULL, 
  [name] CLOB, 
  [gender] CLOB, 
  [salary] NUMERIC(7, 2), 
  CONSTRAINT [pk_id] PRIMARY KEY ([id])
)