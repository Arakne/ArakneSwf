.flash filename="undefined.swf" bbox=1x1 fps=50

.frame 1
    .action:
        not_exists = not_exists;
        get_member = not_exists.member;
        get_member2 = other.member;
        other.member = 123;

        o = new Object();
        ret = o.badMethod();
        get_member3 = o.member;
        get_member4 = o.member.member.member;
    .end
.end
