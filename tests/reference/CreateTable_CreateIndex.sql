CREATE INDEX [ns_unittests].[managed] ON [ns_unittests].[Hierarchy] (
  [ns_unittests].[Hierarchy].[manageeId]
) 
WHERE 
  [ns_unittests].[Hierarchy].[managerId] > 10