CREATE 
OR REPLACE TABLE [ns_unittests].[Tasks] (
  [id] INTEGER AUTO INCREMENT, 
  [name] VARCHAR(32) NULL, 
  [creationDateTime] TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, 
  [priority] INTEGER NULL, 
  [category] INTEGER NULL, 
  [creator] INTEGER NULL DEFAULT NULL, 
  [assignedTo] INTEGER NULL DEFAULT NULL, 
  CONSTRAINT [pk_tid] PRIMARY KEY ([id]), 
  CONSTRAINT [fk_creator] FOREIGN KEY ([creator]) REFERENCES [Employees] ([id]) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ([assignedTo]) REFERENCES [Employees] ([id]) ON UPDATE CASCADE ON DELETE CASCADE
)
