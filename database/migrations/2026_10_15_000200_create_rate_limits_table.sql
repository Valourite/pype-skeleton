CREATE TABLE rate_limits (
    "key"    TEXT PRIMARY KEY,
    hits     INTEGER NOT NULL,
    reset_at INTEGER NOT NULL
);
