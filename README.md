# pragmaloader

A securing, delegating php autoloader.

## Rationale

Some PHP applications bandwidth heavy due to legacy outgrowth and throughput coding style. In these instances, the migration to modern OOP techniques will be fraught with a time of indeterminate compliance with OOP security due to argument type checking.

In response to this, we present a securing autoloader, with support for Unix user/group based security.

Under the MIT license, this embryonic and slightly moronic autoloader is free to copy and fork. It marks a starting point for development of a delegating autoloader that must lock specific code areas from use by other code areas / processes.

## Suggested Algorithm

Without specifying in security conscious non-functional requirements, that dynamic languages (at a particular stage of compliant evolution) should be hamstrung in certain ways, it is difficuly to preserve an ACID transformation of the code.

Whilst globals are being transformed, the linterman's job will be complex. This autoloader is intended to make such transformations simpler, more complete more rapidly.

N.B. This code was 'complete' in a very short space of time, and this is only because of an argument over lazy-loading.
