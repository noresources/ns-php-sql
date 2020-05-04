CREATE TABLE [ns_unittests].[Hierarchy] (
  [managerId] INTEGER NOT NULL, 
  [manageeId] INTEGER NOT NULL, 
  PRIMARY KEY ([managerId], [manageeId]), 
  FOREIGN KEY ([managerId]) REFERENCES [ns_unittests].[Employees] ([id]) ON UPDATE CASCADE ON DELETE CASCADE, 
  FOREIGN KEY ([manageeId]) REFERENCES [ns_unittests].[Employees] ([id]) ON UPDATE CASCADE ON DELETE CASCADE
)