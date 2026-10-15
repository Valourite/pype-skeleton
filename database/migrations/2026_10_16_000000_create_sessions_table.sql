-- Browser sessions (used by the 'web' stack). The id column holds a sha256 hash of the cookie value.
CREATE TABLE sessions (
    id         TEXT PRIMARY KEY,
    payload    TEXT NOT NULL,
    expires_at INTEGER NOT NULL
);

CREATE INDEX sessions_expires_at ON sessions (expires_at);
