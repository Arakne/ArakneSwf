.flash filename="array.swf" bbox=1x1 fps=50

.frame 1
    .action:
        arr1 = new Array();
        arr1_length = arr1.length;
        arr2 = new Array(5);
        arr2_length = arr2.length;
        arr3 = new Array(1, 2, 3);
        arr3_length = arr3.length;
        arr4 = new Array(1, 2, 3);
        arr4.length = 10;
        arr4_length = arr4.length;
        arr5 = new Array(1, 2, 3);
        arr5.length = 1;
        arr5_length = arr5.length;
        arr6 = new Array();
        arr6[0] = 41;
        arr6[1] = 42;
        arr6[2] = 43;
        arr6_length = arr6.length;
    .end
.end
