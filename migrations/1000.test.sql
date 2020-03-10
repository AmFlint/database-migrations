INSERT INTO messages (`content`) VALUES("thousand");

-- A comment to be ignored
UPDATE messages set content = 'updated content' WHERE content = 'thousand';

INSERT INTO messages 
  (`content`)
  VALUES(
    "testing the multiline"
  );