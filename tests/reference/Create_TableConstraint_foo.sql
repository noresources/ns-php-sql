CREATE TABLE [metavariables].[foo] (
  [id] INTEGER NOT NULL AUTO INCREMENT, 
  [angle] REAL DEFAULT 3.1415926535898 NOT NULL, 
  CONSTRAINT [pirmary_foo] PRIMARY KEY ([id]), 
  CONSTRAINT [pi_boundary] CHECK (
    [metavariables].[foo].[angle] BETWEEN -3.1415926535898 
    AND 3.1415926535898
  )
)
