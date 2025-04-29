.flash filename="methods.swf" bbox=1x1 fps=50

.frame 1
    .action:
        ret = myObject.method("foo", 123);
    .end
.end
