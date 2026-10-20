.flash filename="add2.swf" bbox=1x1 fps=50 version=7

.frame 1
    .action:
        integers = 3 + 4;
        float_and_integer = 1.5 + 2;
        strings = "foo" + "bar";
        numeric_string_and_number = "3" + 4;
        number_and_numeric_string = 4 + "3";
        float_and_string = 1.5 + "x";
        boolean_and_number = true + 1;
        null_and_number = null + 1;
        null_and_string = null + "a";
        boolean_and_string = true + "a";
        chained = 1 + 2 + "3";

        obj = {a: 1};
        object_and_number = obj + 1;
        inline_arr1 = [1, 2];
        inline_arr2 = [3];
        inline_arrays = inline_arr1 + inline_arr2;
        arr = new Array(1, 2, 3);
        array_and_string = arr + "";
    .end
.end
