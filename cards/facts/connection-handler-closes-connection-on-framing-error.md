---
title: ConnectionHandler calls `onError` with the parser's message when FrameParser reports an error
tags: [daemon, protocol, framing]
status: verified 2026-11-02 auto
source: [src/Daemon/ConnectionHandler.php]
as_of: 1e203597e 2026-11-02
---
When `FrameParser::errored()` returns true after a `push()`, `ConnectionHandler::feed()` invokes `onError` with `(string) $this->parser->errorMessage()`. Malformed framing closes the connection.

The `errored()` branch itself writes no log line. `log()` is reached only from the feed's `catch` block (`connection feed failed: …`), from `process()` on unknown frame version and on token-hash mismatch, and from `respondWithStats()` on a stats-reply failure. A missing feed-failure log line does not mean the framing-error path did not run.

**Why:** The class docblock states malformed framing closes the connection, so the parser's error message is surfaced to the injected `onError` callback rather than swallowed — and an agent debugging the absence of a log line needs to know that branch is silent by design.
