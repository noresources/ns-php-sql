CREATE 
OR REPLACE TABLE [ns_unittests].[Employees] (
  [id] INTEGER NOT NULL, 
  [name] CLOB, 
  [gender] VARCHAR(1) NULL, 
  [salary] NUMERIC(7, 2) NULL, 
  CONSTRAINT [pk_id] PRIMARY KEY ([id])
)
