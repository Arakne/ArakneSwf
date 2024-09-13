<?php

namespace Arakne\Swf\Parser\Structure\Action;

enum Type: int
{
    /** Null-terminated string */
    case String = 0;
    /** 32 bits float */
    case Float = 1;
    case Null = 2;
    case Undefined = 3;
    /** Register number. Unsigned 8 bits */
    case Register = 4;
    case Boolean = 5;
    /** 64 bits float */
    case Double = 6;
    /** 32 bits signed integer */
    case Integer = 7;
    /** 8 bits constant id. Reference to constant pool. */
    case Constant8 = 8;
    /** 16 bits constant id. Reference to constant pool. */
    case Constant16 = 9;
}
