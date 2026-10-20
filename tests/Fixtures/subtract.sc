.flash filename="subtract.swf" bbox=1x1 fps=50 version=7

.frame 1
    .action:
        integers = 10 - 3;
        operand_order = 3 - 10;
        float_and_integer = 3.5 - 1;
        numeric_strings = "10" - "4.5";
        numeric_string_and_integer = "10" - 3;
        non_numeric_string = "abc" - 5;
        boolean_and_integer = true - 1;
        null_and_integer = null - 1;
        chained = 10 - 3 - 2;
        concat_then_subtract = "1" + 2 - 3;

        arr = [5];
        array_and_integer = arr - 1;
        obj = {a: 1};
        object_and_integer = obj - 1;
    .end
.end
