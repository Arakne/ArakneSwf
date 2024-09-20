.flash filename="func_call.swf" bbox=1x1 fps=50

.frame 1
    .action:
        arg_1 = "foo";
        ret = myFunction(arg_1, 123);
    .end
.end
