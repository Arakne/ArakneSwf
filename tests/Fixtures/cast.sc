.flash filename="cast.swf" bbox=1x1 fps=50

.frame 1
    .action:
        str_to_number = Number("1234");
        float_to_str = String(1234.5678);
        int_to_bool = Boolean(1);
    .end
.end
