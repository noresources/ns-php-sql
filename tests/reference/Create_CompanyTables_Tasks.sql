CREATE TABLE [ns_unittests].[Tasks] (
  [id] INTEGER AUTO INCREMENT, 
  [name] TEXT, 
  [creationDateTime] TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
  [priority] INTEGER, 
  [category] INTEGER, 
  [creator] INTEGER DEFAULT NULL, 
  [assignedTo] INTEGER DEFAULT NULL, 
  CONSTRAINT [pk_tid] PRIMARY KEY ([id]), 
  CONSTRAINT [fk_creator] FOREIGN KEY ([creator]) REFERENCES [ns_unittests].[Employees] ([id]) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ([assignedTo]) REFERENCES [ns_unittests].[Employees] ([id]) ON UPDATE CASCADE ON DELETE CASCADE
)