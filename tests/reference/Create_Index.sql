CREATE INDEX [managed] ON [ns_unittests].[Hierarchy] ([manageeId]) 
WHERE 
  [ns_unittests].[Hierarchy].[managerId] > 10