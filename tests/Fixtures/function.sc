.flash filename="function.swf" bbox=1x1 fps=50

.frame 1
    .action:
        function myFunction(arg1, arg2) {
            var k = arg2 * arg1.length;
            var ret = new String();

            for (var i = 0; i < arg1.length; i = i + 1) {
                ret = ret + (arg1[i] ^ k);
            }

            return ret;
        }

        ret = myFunction("foo", 123);
    .end
.end
