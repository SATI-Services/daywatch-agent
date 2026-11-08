---
title: ExceptionTrace formats function arguments by type only, never their values
tags: [exception, records, safety]
status: verified 2026-11-08 auto
source: [src/Records/ExceptionTrace.php]
as_of: ba25e5cf0 2026-11-08
---
ExceptionTrace::argType() renders each argument as its type — `int`, `float`, `bool`, `null` for the integer/double/boolean/NULL cases, `gettype()` otherwise — and an object argument as its short class name, with no namespace. The argument value itself is never rendered; args are joined with `, `.

**Why:** formatSource() builds the frame's `source` as `$class.$type.$signature`, where `$type` is the frame's own connector — `->` for a method call, `::` for a static call — so a static frame renders `Class::method(argTypes)` and a trace carries the invoked signature without the values passed to it.
