.flash filename="swf4_add.swf" bbox=1x1 fps=50 version=4

.frame 1
    .action:
        add_integers = 3 + 4;
        add_float_and_integer = 1.5 + 2;
        add_numeric_strings = "3" + "4.5";
        add_non_numeric_string = "abc" + 5;
        string_add_strings = "foo" & "bar";
        string_add_integers = 3 & 4;
        string_add_float = "x" & 1.5;
    .end
.end
