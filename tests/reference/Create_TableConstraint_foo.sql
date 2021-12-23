CREATE TABLE [metavariables].[foo] (
  [id] INTEGER NOT NULL AUTO INCREMENT, 
  [angle] REAL NOT NULL DEFAULT 3.1415926535898, 
  CONSTRAINT [pirmary_foo] PRIMARY KEY ([id]), 
  CONSTRAINT [pi_boundary] CHECK (
    [metavariables].[foo].[angle] BETWEEN -3.1415926535898 
    AND 3.1415926535898
  )
)
