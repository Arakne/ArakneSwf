.flash filename="objects.swf" bbox=1x1 fps=50

.frame 1
    .action:
        bag = new Object();
        bag.a = 1;
        bag.b = false;

        arr = new Array();
        arr[0] = 1;
        arr[1] = 2;

        inlined_object = {c: 1.3, d: "hello"};
        inlined_array = [1, 2, 3];

        get_member = bag.a;
        array_access = arr[1];
        get_member_str = bag["b"];
    .end
.end
